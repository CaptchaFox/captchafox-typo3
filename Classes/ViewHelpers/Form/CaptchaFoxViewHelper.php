<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\ViewHelpers\Form;

use CaptchaFox\CaptchaFoxTypo3\Services\CaptchaService;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

/**
 * Provides the variable {captchafox} (siteKey, language, scriptUrl, disabledReason) to its children.
 */
class CaptchaFoxViewHelper extends AbstractFormFieldViewHelper
{
    public function __construct(private readonly CaptchaService $captchaService)
    {
        parent::__construct();
    }

    public function render(): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'];

        $container = $this->templateVariableContainer;
        $container->add('captchafox', [
            'siteKey' => $this->captchaService->getSiteKey(),
            'language' => $this->captchaService->getWidgetLanguage($request) ?? '',
            'scriptUrl' => $this->captchaService->getScriptUrl(),
            'disabledReason' => $this->captchaService->getDisabledReason($request),
        ]);

        $content = (string)$this->renderChildren();

        $container->remove('captchafox');

        return $content;
    }
}
