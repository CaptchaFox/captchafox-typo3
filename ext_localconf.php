<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// The form setup is registered for the form editor (module.tx_form) and the frontend (plugin.tx_form)
// here, so no static template has to be included. addTypoScriptSetup() also reaches sites that use
// site sets (TYPO3 13).
ExtensionManagementUtility::addTypoScriptSetup('
module.tx_form.settings.yamlConfigurations.4507 = EXT:captchafox_official/Configuration/Yaml/FormSetup.yaml
plugin.tx_form.settings.yamlConfigurations.4507 = EXT:captchafox_official/Configuration/Yaml/FormSetup.yaml
');
