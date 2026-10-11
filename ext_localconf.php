<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Up to 12.0.x these values were set here and read from the extension configuration, e.g. by custom
// templates as {configuration.api_server}. They stay available so such integrations keep working;
// the extension itself uses CaptchaService::SCRIPT_URL and CaptchaService::VERIFY_URL.
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['captchafox_official']['api_server'] ??= 'https://cdn.captchafox.com/api.js';
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['captchafox_official']['verify_server'] ??= 'https://api.captchafox.com/siteverify';

// The form setup is registered for the form editor (module.tx_form) and the frontend (plugin.tx_form)
// here, so no static template has to be included. addTypoScriptSetup() also reaches sites that use
// site sets (TYPO3 13).
ExtensionManagementUtility::addTypoScriptSetup('
module.tx_form.settings.yamlConfigurations.4507 = EXT:captchafox_official/Configuration/Yaml/FormSetup.yaml
plugin.tx_form.settings.yamlConfigurations.4507 = EXT:captchafox_official/Configuration/Yaml/FormSetup.yaml
');
