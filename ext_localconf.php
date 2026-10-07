<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

call_user_func(function () {
    // TYPO3 10 has no Configuration/Icons.php yet.
    GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class)->registerIcon(
        't3-form-icon-captchafox',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:captchafox_official/Resources/Public/Icons/CaptchaFox.svg']
    );

    // The form setup is registered for the form editor (module.tx_form) and the frontend
    // (plugin.tx_form) here, so no static template has to be included.
    ExtensionManagementUtility::addTypoScriptSetup('
module.tx_form.settings.yamlConfigurations.4507 = EXT:captchafox_official/Configuration/Yaml/FormSetup.yaml
plugin.tx_form.settings.yamlConfigurations.4507 = EXT:captchafox_official/Configuration/Yaml/FormSetup.yaml
');
});
