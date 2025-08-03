<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Services;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CaptchaService
{

    protected array $configuration = [];

    protected ExtensionConfiguration $extensionConfiguration;

    protected RequestFactory $requestFactory;

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        RequestFactory         $requestFactory
    )
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->requestFactory = $requestFactory;
        $this->initialize();
    }

    protected function initialize(): void
    {
        $this->configuration = $this->extensionConfiguration->get('captchafox_official');
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function validate(string $value = ''): array
    {
        if (!$this->getShowCaptcha()) {
            return [
                'verified' => true,
                'error' => '',
            ];
        }

        if (empty($value) || !is_string($value)) {
            return [
                'verified' => false,
                'error' => 'internal-required',
            ];
        }

        $secret_key = $this->configuration['secret_key'];
        $site_key = $this->configuration['site_key'];

        $request = [
            'secret' => $secret_key,
            'sitekey' => $site_key,
            'remoteIp' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            'response' => trim(
                !empty($value) ? $value : (string)($this->getRequest()->getParsedBody()['cf-captcha-response'] ?? '')
            ),
        ];


        $result = [
            'verified' => false,
            'error' => '',
        ];

        if (empty($request['response'])) {
            $result['error'] = 'missing-input-response';
        } else {
            $response = $this->verify($request);

            if (!$response) {
                $result['error'] = 'validation-server-not-responding';
            }

            if ($response['success']) {
                $result['verified'] = true;
            } else {
                $result['error'] = (string)(
                is_array($response['error-codes']) ?
                    reset($response['error-codes']) :
                    $response['error-codes']
                );
            }
        }

        return $result;
    }

    protected function verify(array $data): array
    {

        $verifyServerInfo = @parse_url($this->configuration['verify_server']);

        if (empty($verifyServerInfo)) {
            return [
                'success' => false,
                'error-codes' => 'captchafox-not-reachable',
            ];
        }

        $params = ltrim(GeneralUtility::implodeArrayForUrl('', $data), '&');

        $options = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $params,
        ];

        $response = $this->requestFactory->request($this->configuration['verify_server'], 'POST', $options);

        $body = (string)$response->getBody();
        return $body ? json_decode($body, true) : [];
    }


    public function getShowCaptcha(): bool
    {
        return !$this->isInRobotMode()
            && (
                ApplicationType::fromRequest($this->getRequest())->isBackend()
                || !$this->isDevelopmentMode()
                || $this->isEnforceCaptcha()
            );
    }

    protected function isInRobotMode(): bool
    {
        return (bool)($this->configuration['robotMode'] ?? false);
    }

    protected function isEnforceCaptcha(): bool
    {
        return (bool)($this->configuration['enforceCaptcha'] ?? false);
    }

    protected function isDevelopmentMode(): bool
    {
        return Environment::getContext()->isDevelopment();
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}