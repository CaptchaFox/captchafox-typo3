<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Services;

use CaptchaFox\CaptchaFoxTypo3\Verification\VerificationResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Extension configuration, widget settings and server-side verification of CaptchaFox answers.
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
        'lang' => 'de',
        'apiUnavailable' => 'allow',
        'robotMode' => '0',
        'enforceCaptcha' => '0',
    ];

    private array $configuration;

    private RequestFactory $requestFactory;

    public function __construct(ExtensionConfiguration $extensionConfiguration, RequestFactory $requestFactory)
    {
        try {
            $configuration = $extensionConfiguration->get('captchafox_official');
        } catch (\Throwable $exception) {
            $configuration = [];
        }
        $this->configuration = array_replace(self::DEFAULTS, is_array($configuration) ? $configuration : []);
        $this->requestFactory = $requestFactory;
    }

    public function getSiteKey(): string
    {
        return trim((string)$this->configuration['site_key']);
    }

    /**
     * The configured widget language. Null lets the widget follow the browser language.
     */
    public function getWidgetLanguage(): ?string
    {
        $language = trim((string)$this->configuration['lang']);

        return $language !== '' ? $language : null;
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

        if ($result->getStatus() === VerificationResult::VALID) {
            return ['verified' => true, 'error' => ''];
        }

        if ($result->getStatus() === VerificationResult::INVALID) {
            $codes = $result->getErrorCodes();
            $this->log('info', 'Answer rejected by CaptchaFox: ' . ($codes === [] ? 'no error code' : implode(', ', $codes)));

            return ['verified' => false, 'error' => $codes[0] ?? 'default'];
        }

        // An outage at CaptchaFox must not lock visitors out of the site's forms, so only an explicit
        // "block" setting rejects the form; the outage is logged either way.
        $block = $this->configuration['apiUnavailable'] === 'block';
        $this->log('warning', sprintf(
            'CaptchaFox API unavailable (%s), form %s.',
            $result->getReason(),
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
            'secret' => trim((string)$this->configuration['secret_key']),
            'response' => $token,
            'sitekey' => $this->getSiteKey(),
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

    private function log(string $level, string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $message);
        }
    }
}
