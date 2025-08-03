<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CaptchaFox',
    'description' => 'CaptchaFox Integration for EXT:form',
    'category' => 'fe',
    'author_company' => 'Scoria Labs GmbH',
    'state' => 'stable',
    'version' => '12.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'CaptchaFox\\CaptchaFoxTypo3\\' => 'Classes/',
        ],
    ],
];
