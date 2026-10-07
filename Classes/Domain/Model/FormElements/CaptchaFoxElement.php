<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Domain\Model\FormElements;

use CaptchaFox\CaptchaFoxTypo3\Validation\CaptchaFoxValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;

/**
 * Form element "CaptchaFox". It always carries exactly one CaptchaFox validator.
 *
 * At least one: without it the widget would be shown but nothing checked, e.g. after an editor removed
 * the validator in the form editor or a hand-written form definition left it out.
 * At most one: the form framework applies the element type and then the form definition, which usually
 * lists the validator as well. Two validators would send the single-use token to CaptchaFox twice, and
 * the second check would fail.
 */
class CaptchaFoxElement extends GenericFormElement
{
    public function setOptions(array $options, bool $resetValidators = false)
    {
        parent::setOptions($options, $resetValidators);

        if (!$this->hasCaptchaFoxValidator()) {
            $this->createValidator('CaptchaFox');
        }
    }

    public function addValidator(ValidatorInterface $validator)
    {
        if ($validator instanceof CaptchaFoxValidator && $this->hasCaptchaFoxValidator()) {
            return;
        }

        parent::addValidator($validator);
    }

    private function hasCaptchaFoxValidator(): bool
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof CaptchaFoxValidator) {
                return true;
            }
        }

        return false;
    }
}
