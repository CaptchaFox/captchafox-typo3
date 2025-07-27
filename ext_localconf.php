<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

call_user_func(function () {

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['captchafox_typo3']['verify_server'] = 'https://api.captchafox.com/siteverify';
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['captchafox_typo3']['api_server'] = 'https://cdn.captchafox.com/api.js';

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        't3-form-icon-captchafox',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:captchafox_typo3/Resources/Public/Icons/CaptchaFox.svg']
    );


    ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_form {
           settings {
               yamlConfigurations {
                    100 = EXT:captchafox_typo3/Configuration/Yaml/FormSetup.yaml
               }
           }f
        }
    ');
});