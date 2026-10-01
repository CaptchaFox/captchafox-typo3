<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Validation;

use CaptchaFox\CaptchaFoxTypo3\Services\CaptchaService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class CaptchaFoxValidator extends AbstractValidator
{
    private const LANGUAGE_FILE = 'LLL:EXT:captchafox_official/Resources/Private/Language/locallang.xlf:';

    protected $acceptsEmptyValues = false;

    public function __construct(private readonly CaptchaService $captchaService) {}

    public function isValid(mixed $value): void
    {
        $status = $this->captchaService->validate(is_string($value) ? $value : '', $this->getCurrentRequest());

        if ($status['verified']) {
            return;
        }

        $message = $this->translateErrorMessage(self::LANGUAGE_FILE . 'error_captchafox_' . $status['error'], 'captchafox_official');
        if ($message === '') {
            // Error codes without a text of their own (e.g. new codes of the API).
            $message = $this->translateErrorMessage(self::LANGUAGE_FILE . 'error_captchafox_default', 'captchafox_official');
        }

        $this->addError($message, 1753561629);
    }

    private function getCurrentRequest(): ServerRequestInterface
    {
        return $this->getRequest() ?? $GLOBALS['TYPO3_REQUEST'];
    }
}
