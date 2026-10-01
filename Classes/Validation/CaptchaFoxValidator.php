<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Validation;

use CaptchaFox\CaptchaFoxTypo3\Services\CaptchaService;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class CaptchaFoxValidator extends AbstractValidator
{
    private const LANGUAGE_FILE = 'LLL:EXT:captchafox_official/Resources/Private/Language/locallang.xlf:';

    protected $acceptsEmptyValues = false;

    protected CaptchaService $captchaService;

    public function injectCaptchaService(CaptchaService $captchaService): void
    {
        $this->captchaService = $captchaService;
    }

    public function __construct(array $options = [])
    {
        // TYPO3 10 passes the options to the constructor, TYPO3 11 to setOptions().
        if ((new Typo3Version())->getMajorVersion() < 11) {
            parent::__construct($options);
        }
    }

    public function setOptions(array $options): void
    {
        $this->initializeDefaultOptions($options);
    }

    public function isValid($value): void
    {
        $status = $this->captchaService->validate(is_string($value) ? $value : '', $GLOBALS['TYPO3_REQUEST']);

        if ($status['verified']) {
            return;
        }

        $message = (string)$this->translateErrorMessage(self::LANGUAGE_FILE . 'error_captchafox_' . $status['error'], 'captchafox_official');
        if ($message === '') {
            // Error codes without a text of their own (e.g. new codes of the API).
            $message = (string)$this->translateErrorMessage(self::LANGUAGE_FILE . 'error_captchafox_default', 'captchafox_official');
        }

        $this->addError($message, 1753561629);
    }
}
