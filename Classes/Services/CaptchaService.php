<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Services;

use CaptchaFox\CaptchaFoxTypo3\Language\LanguageMapper;
use CaptchaFox\CaptchaFoxTypo3\Verification\VerificationResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Extension configuration, widget settings and server-side verification of CaptchaFox answers.
 *
 * Site key and secret key can be set per site (site settings captchafox.siteKey and captchafox.secretKey),
 * e.g. for several domains in one installation; each falls back to the extension configuration.
 */
class CaptchaService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const VERIFY_URL = 'https://api.captchafox.com/siteverify';
    public const SCRIPT_URL = 'https://cdn.captchafox.com/api.js';

    /**
     * Global function the widget script calls once it is loaded (defined in form.js).
     */
    public const ONLOAD_CALLBACK = 'captchaFoxTypo3OnLoad';

    /**
     * Seconds to wait for /siteverify. A slower answer counts as unavailable; without a limit a
     * hanging API would hold the request until the PHP time limit.
     */
    public const TIMEOUT_SECONDS = 5;

    public const DISABLED_ROBOT_MODE = 'robotMode';
    public const DISABLED_DEVELOPMENT = 'development';

    private const DEFAULTS = [
        'site_key' => 'sk_11111111000000001111111100000000',
        'secret_key' => 'ok_11111111000000001111111100000000',
        'lang' => '',
        'apiUnavailable' => 'allow',
        'robotMode' => '0',
        'enforceCaptcha' => '0',
    ];

    private array $configuration;

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        private readonly RequestFactory $requestFactory
    ) {
        try {
            $configuration = $extensionConfiguration->get('captchafox_official');
        } catch (\Throwable) {
            $configuration = [];
        }
        $this->configuration = array_replace(self::DEFAULTS, is_array($configuration) ? $configuration : []);
    }

    public function getSiteKey(ServerRequestInterface $request): string
    {
        return $this->getSiteSetting($request, 'siteKey') ?? trim((string)$this->configuration['site_key']);
    }

    private function getSecretKey(ServerRequestInterface $request): string
    {
        return $this->getSiteSetting($request, 'secretKey') ?? trim((string)$this->configuration['secret_key']);
    }

    /**
     * A value from the settings of the current site, or null if the site sets none.
     */
    private function getSiteSetting(ServerRequestInterface $request, string $name): ?string
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return null;
        }

        $value = $site->getSettings()->get('captchafox.' . $name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function getScriptUrl(): string
    {
        // Explicit rendering: form.js renders each widget into its own form element. A "lang"
        // parameter here would be global and is ignored with render=explicit anyway.
        return self::SCRIPT_URL . '?render=explicit&onload=' . self::ONLOAD_CALLBACK;
    }

    /**
     * Why the widget is neither shown nor checked, or an empty string if CaptchaFox is active.
     */
    public function getDisabledReason(ServerRequestInterface $request): string
    {
        if ((bool)$this->configuration['robotMode']) {
            return self::DISABLED_ROBOT_MODE;
        }

        if (
            Environment::getContext()->isDevelopment()
            && !(bool)$this->configuration['enforceCaptcha']
            && !ApplicationType::fromRequest($request)->isBackend()
        ) {
            return self::DISABLED_DEVELOPMENT;
        }

        return '';
    }

    /**
     * The configured widget language, or the one of the current site language. Null lets the widget
     * follow the browser language.
     */
    public function getWidgetLanguage(ServerRequestInterface $request): ?string
    {
        $configured = trim((string)$this->configuration['lang']);
        if ($configured !== '') {
            return $configured;
        }

        $language = $request->getAttribute('language');

        return $language instanceof SiteLanguage ? LanguageMapper::fromLocale((string)$language->getLocale()) : null;
    }

    /**
     * @return array{verified: bool, error: string} The error is a key suffix for the language file.
     */
    public function validate(string $token, ServerRequestInterface $request): array
    {
        if ($this->getDisabledReason($request) !== '') {
            return ['verified' => true, 'error' => ''];
        }

        $token = trim($token);
        if ($token === '') {
            return ['verified' => false, 'error' => 'internal-required'];
        }

        $result = $this->verify($token, $request);

        if ($result->status === VerificationResult::VALID) {
            return ['verified' => true, 'error' => ''];
        }

        if ($result->status === VerificationResult::INVALID) {
            $this->logger?->info('Answer rejected by CaptchaFox: ' . (implode(', ', $result->errorCodes) ?: 'no error code'));

            return ['verified' => false, 'error' => $result->errorCodes[0] ?? 'default'];
        }

        // An outage at CaptchaFox must not lock visitors out of the site's forms, so only an explicit
        // "block" setting rejects the form; the outage is logged either way.
        $block = $this->configuration['apiUnavailable'] === 'block';
        $this->logger?->warning(sprintf(
            'CaptchaFox API unavailable (%s), form %s.',
            $result->reason,
            $block ? 'blocked' : 'let through'
        ));

        return $block ? ['verified' => false, 'error' => 'service-unavailable'] : ['verified' => true, 'error' => ''];
    }

    /**
     * Sends the token to /siteverify. There is no retry: real tokens are single-use, so a second
     * attempt could only fail.
     */
    private function verify(string $token, ServerRequestInterface $request): VerificationResult
    {
        $parameters = [
            'secret' => $this->getSecretKey($request),
            'response' => $token,
            'sitekey' => $this->getSiteKey($request),
        ];

        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!$normalizedParams instanceof NormalizedParams) {
            $normalizedParams = NormalizedParams::createFromRequest($request);
        }
        // Behind a reverse proxy this is only the visitor's address if TYPO3 knows the proxy
        // ($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']).
        $remoteAddress = $normalizedParams->getRemoteAddress();
        if ($remoteAddress !== '') {
            $parameters['remoteIp'] = $remoteAddress;
        }

        try {
            $response = $this->requestFactory->request(self::VERIFY_URL, 'POST', [
                'form_params' => $parameters,
                'timeout' => self::TIMEOUT_SECONDS,
                'connect_timeout' => self::TIMEOUT_SECONDS,
                'http_errors' => false,
            ]);
        } catch (\Throwable $exception) {
            return VerificationResult::unavailable('request failed: ' . $exception->getMessage());
        }

        return VerificationResult::fromResponse($response->getStatusCode(), (string)$response->getBody());
    }
}
