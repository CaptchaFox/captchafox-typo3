<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Validation;

use CaptchaFox\CaptchaFoxTypo3\Services\CaptchaService;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class CaptchaFoxValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;

    protected CaptchaService $captchaService;

    public function injectCaptchaService(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }

    public function __construct(array $options = []) {


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
        $status = $this->captchaService->validate((string)$value);

        if ($status['error'] !== '') {
            $errorText = $this->translateErrorMessage(
                'LLL:EXT:captchafox_official/Resources/Private/Language/locallang.xlf:error_captchafox_' . $status['error'],
                'captchafox_official'
            );

            if (empty($errorText)) {
                $errorText = htmlspecialchars($status['error']);
            }

            $this->addError($errorText, 1753561629);
        }
    }


}
