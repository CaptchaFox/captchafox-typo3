<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\ViewHelpers\Form;

use CaptchaFox\CaptchaFoxTypo3\Services\CaptchaService;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class CaptchaFoxViewHelper extends AbstractFormFieldViewHelper
{
    protected CaptchaService $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
        parent::__construct();
    }

    public function render(): string
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $container = $this->templateVariableContainer;
        $container->add('configuration', $this->captchaService->getConfiguration());
        $container->add('showCaptcha', $this->captchaService->getShowCaptcha());
        $container->add('name', $name);

        $content = $this->renderChildren();

        $container->remove('name');
        $container->remove('showCaptcha');
        $container->remove('configuration');

        return $content;
    }
}
