<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'captchafox_official',
    'description' => 'CaptchaFox Typo3',
    'category' => 'fe',
    'author_company' => 'scorialabs',
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
