<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Verification;

/**
 * Outcome of a request to the CaptchaFox siteverify endpoint.
 *
 * There are three outcomes, because an answer that CaptchaFox rejected is handled differently from an
 * answer that could not be checked at all: the first is always rejected, the second follows the
 * extension setting for an unreachable API.
 */
final class VerificationResult
{
    public const VALID = 'valid';
    public const INVALID = 'invalid';
    public const UNAVAILABLE = 'unavailable';

    private string $status;

    /**
     * @var string[]
     */
    private array $errorCodes;

    private string $reason;

    /**
     * @param string[] $errorCodes Error codes reported by CaptchaFox (INVALID only)
     * @param string $reason Why the answer could not be checked (UNAVAILABLE only)
     */
    private function __construct(string $status, array $errorCodes = [], string $reason = '')
    {
        $this->status = $status;
        $this->errorCodes = $errorCodes;
        $this->reason = $reason;
    }

    /**
     * Classifies a siteverify response. A 2xx status alone means nothing: only "success": true is a
     * valid answer, and any other value of "success" is a rejection, with or without error codes.
     */
    public static function fromResponse(int $statusCode, string $body): self
    {
        if ($statusCode < 200 || $statusCode >= 300) {
            return self::unavailable('HTTP status ' . $statusCode);
        }

        try {
            $data = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return self::unavailable('response is not valid JSON');
        }

        if (!is_array($data) || !array_key_exists('success', $data)) {
            return self::unavailable('response has no "success" field');
        }

        if ($data['success'] === true) {
            return new self(self::VALID);
        }

        $codes = is_array($data['error-codes'] ?? null) ? $data['error-codes'] : [];

        return new self(self::INVALID, array_values(array_filter($codes, 'is_string')));
    }

    public static function unavailable(string $reason): self
    {
        return new self(self::UNAVAILABLE, [], $reason);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string[]
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
