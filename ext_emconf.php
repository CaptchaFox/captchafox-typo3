<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CaptchaFox',
    'description' => 'CaptchaFox Integration for EXT:form',
    'category' => 'fe',
    'author_company' => 'Scoria Labs GmbH',
    'state' => 'stable',
    'version' => '10.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-11.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'CaptchaFox\\CaptchaFoxTypo3\\' => 'Classes/',
        ],
    ],
];
