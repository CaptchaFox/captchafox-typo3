<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Tests\Unit\Services;

use CaptchaFox\CaptchaFoxTypo3\Services\CaptchaService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\AbstractLogger;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

final class CaptchaServiceTest extends TestCase
{
    private const TOKEN = '11111111000000001111111100000000';

    /**
     * @var array<int, array{method: string, options: array<string, mixed>}>
     */
    private array $requests = [];

    /**
     * @var array<int, array{level: string, message: string}>
     */
    private array $logs = [];

    protected function setUp(): void
    {
        $this->initializeEnvironment('Production');
    }

    #[Test]
    public function sendsOneFormEncodedRequestWithSecretTokenSiteKeyAndRemoteIp(): void
    {
        $service = $this->createService(['site_key' => 'sk_site', 'secret_key' => 'ok_secret'], $this->answer(200, '{"success":true}'));

        self::assertSame(['verified' => true, 'error' => ''], $service->validate(' ' . self::TOKEN . ' ', $this->request()));

        self::assertCount(1, $this->requests);
        self::assertSame('POST', $this->requests[0]['method']);
        $options = $this->requests[0]['options'];
        self::assertSame(
            ['secret' => 'ok_secret', 'response' => self::TOKEN, 'sitekey' => 'sk_site', 'remoteIp' => '192.0.2.10'],
            $options['form_params']
        );
        self::assertSame(CaptchaService::TIMEOUT_SECONDS, $options['timeout']);
        self::assertFalse($options['http_errors']);
    }

    #[Test]
    public function rejectsAnEmptyTokenWithoutAskingCaptchaFox(): void
    {
        $service = $this->createService([], $this->answer(200, '{"success":true}'));

        self::assertSame(['verified' => false, 'error' => 'internal-required'], $service->validate('  ', $this->request()));
        self::assertSame([], $this->requests);
    }

    #[Test]
    public function reportsTheFirstErrorCodeOfARejection(): void
    {
        $service = $this->createService([], $this->answer(200, '{"success":false,"error-codes":["timeout-or-duplicate","bad-request"]}'));

        self::assertSame(['verified' => false, 'error' => 'timeout-or-duplicate'], $service->validate(self::TOKEN, $this->request()));
    }

    #[Test]
    public function rejectsSuccessFalseWithoutErrorCodes(): void
    {
        $service = $this->createService([], $this->answer(200, '{"success":false}'));

        self::assertSame(['verified' => false, 'error' => 'default'], $service->validate(self::TOKEN, $this->request()));
    }

    #[Test]
    public function letsTheFormThroughAndLogsAWarningWhenTheApiIsUnavailable(): void
    {
        $service = $this->createService([], $this->answer(500, ''));

        self::assertSame(['verified' => true, 'error' => ''], $service->validate(self::TOKEN, $this->request()));
        self::assertSame('warning', $this->logs[0]['level'] ?? null);
        self::assertStringContainsString('HTTP status 500', $this->logs[0]['message']);
        self::assertStringContainsString('let through', $this->logs[0]['message']);
    }

    #[Test]
    public function blocksTheFormWhenConfiguredAndTheApiIsUnavailable(): void
    {
        $service = $this->createService(['apiUnavailable' => 'block'], null, new \RuntimeException('Connection refused'));

        self::assertSame(['verified' => false, 'error' => 'service-unavailable'], $service->validate(self::TOKEN, $this->request()));
        self::assertStringContainsString('request failed: Connection refused', $this->logs[0]['message']);
        self::assertStringContainsString('blocked', $this->logs[0]['message']);
    }

    #[Test]
    public function robotModeSwitchesTheCheckOff(): void
    {
        $service = $this->createService(['robotMode' => '1'], $this->answer(200, '{"success":false}'));

        self::assertSame(CaptchaService::DISABLED_ROBOT_MODE, $service->getDisabledReason($this->request()));
        self::assertSame(['verified' => true, 'error' => ''], $service->validate('', $this->request()));
        self::assertSame([], $this->requests);
    }

    #[Test]
    public function theDevelopmentContextSwitchesTheCheckOffUnlessEnforced(): void
    {
        $this->initializeEnvironment('Development');

        self::assertSame(CaptchaService::DISABLED_DEVELOPMENT, $this->createService([])->getDisabledReason($this->request()));
        self::assertSame('', $this->createService(['enforceCaptcha' => '1'])->getDisabledReason($this->request()));
    }

    #[Test]
    public function usesTheExtensionConfigurationWithoutSiteAndTheTestKeysWithoutConfiguration(): void
    {
        self::assertSame('sk_configured', $this->createService(['site_key' => 'sk_configured'])->getSiteKey($this->request()));
        self::assertSame('sk_11111111000000001111111100000000', $this->createService([])->getSiteKey($this->request()));
    }

    #[Test]
    public function usesTheConfiguredWidgetLanguage(): void
    {
        self::assertSame('de', $this->createService(['lang' => ' de '])->getWidgetLanguage($this->request()));
        self::assertNull($this->createService([])->getWidgetLanguage($this->request()));
    }

    #[Test]
    public function loadsTheScriptForExplicitRendering(): void
    {
        self::assertSame(
            'https://cdn.captchafox.com/api.js?render=explicit&onload=captchaFoxTypo3OnLoad',
            $this->createService([])->getScriptUrl()
        );
    }

    /**
     * @param array<string, string> $configuration
     */
    private function createService(array $configuration, ?ResponseInterface $response = null, ?\Throwable $exception = null): CaptchaService
    {
        $extensionConfiguration = $this->createStub(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn($configuration);

        $requestFactory = $this->createStub(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(
            function (string $uri, string $method, array $options) use ($response, $exception): ResponseInterface {
                self::assertSame(CaptchaService::VERIFY_URL, $uri);
                $this->requests[] = ['method' => $method, 'options' => $options];
                if ($exception !== null) {
                    throw $exception;
                }
                return $response ?? $this->answer(200, '{"success":true}');
            }
        );

        $service = new CaptchaService($extensionConfiguration, $requestFactory);
        $logs = &$this->logs;
        $service->setLogger(new class ($logs) extends AbstractLogger {
            /**
             * @param array<int, array{level: string, message: string}> $logs
             */
            public function __construct(private array &$logs) {}

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logs[] = ['level' => (string)$level, 'message' => (string)$message];
            }
        });

        return $service;
    }

    private function answer(int $statusCode, string $body): ResponseInterface
    {
        $response = new Response('php://temp', $statusCode);
        $response->getBody()->write($body);

        return $response;
    }

    private function request(): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/form', 'POST'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams(
                ['REMOTE_ADDR' => '192.0.2.10', 'HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/index.php'],
                ['reverseProxyIP' => '', 'reverseProxyHeaderMultiValue' => 'none', 'reverseProxyPrefix' => '', 'reverseProxySSL' => '', 'reverseProxyPrefixSSL' => '']
            ));
    }

    private function initializeEnvironment(string $context): void
    {
        Environment::initialize(
            new ApplicationContext($context),
            true,
            true,
            sys_get_temp_dir(),
            sys_get_temp_dir() . '/public',
            sys_get_temp_dir() . '/var',
            sys_get_temp_dir() . '/config',
            sys_get_temp_dir() . '/public/index.php',
            'UNIX'
        );
    }
}
