<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Tests\Unit\Verification;

use CaptchaFox\CaptchaFoxTypo3\Verification\VerificationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VerificationResultTest extends TestCase
{
    #[Test]
    public function onlySuccessTrueIsValid(): void
    {
        $result = VerificationResult::fromResponse(200, '{"success":true}');

        self::assertSame(VerificationResult::VALID, $result->status);
        self::assertSame([], $result->errorCodes);
    }

    /**
     * @return array<string, array{string, string[]}>
     */
    public static function rejections(): array
    {
        return [
            'with error codes' => ['{"success":false,"error-codes":["timeout-or-duplicate","bad-request"]}', ['timeout-or-duplicate', 'bad-request']],
            'without error codes' => ['{"success":false}', []],
            'error codes not a list' => ['{"success":false,"error-codes":"bad-request"}', []],
            'non-string error codes dropped' => ['{"success":false,"error-codes":[42,"bad-request"]}', ['bad-request']],
            'success as string' => ['{"success":"true"}', []],
            'success as number' => ['{"success":1}', []],
            'success null' => ['{"success":null}', []],
        ];
    }

    /**
     * An explicit answer of CaptchaFox other than "success": true is always a rejection.
     *
     * @param string[] $expectedCodes
     */
    #[Test]
    #[DataProvider('rejections')]
    public function anyOtherSuccessValueIsARejection(string $body, array $expectedCodes): void
    {
        $result = VerificationResult::fromResponse(200, $body);

        self::assertSame(VerificationResult::INVALID, $result->status);
        self::assertSame($expectedCodes, $result->errorCodes);
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function unusableAnswers(): array
    {
        return [
            'empty body' => [200, '', 'response is not valid JSON'],
            'not JSON' => [200, '<html>Bad Gateway</html>', 'response is not valid JSON'],
            'JSON without success' => [200, '{"error-codes":[]}', 'response has no "success" field'],
            'JSON scalar' => [200, 'true', 'response has no "success" field'],
            'server error' => [500, '{"success":true}', 'HTTP status 500'],
            'not found' => [404, '', 'HTTP status 404'],
            'redirect' => [301, '', 'HTTP status 301'],
            'informational' => [100, '', 'HTTP status 100'],
        ];
    }

    /**
     * An answer that cannot be evaluated is an outage, never a decision of CaptchaFox.
     */
    #[Test]
    #[DataProvider('unusableAnswers')]
    public function unusableAnswersAreUnavailable(int $statusCode, string $body, string $reason): void
    {
        $result = VerificationResult::fromResponse($statusCode, $body);

        self::assertSame(VerificationResult::UNAVAILABLE, $result->status);
        self::assertSame($reason, $result->reason);
    }

    #[Test]
    public function successfulStatusCodesOtherThan200AreEvaluated(): void
    {
        self::assertSame(VerificationResult::VALID, VerificationResult::fromResponse(204, '{"success":true}')->status);
    }
}
