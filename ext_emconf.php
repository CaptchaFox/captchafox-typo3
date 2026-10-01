<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CaptchaFox',
    'description' => 'Protects TYPO3 forms (EXT:form) against bots with CaptchaFox, verified on the server.',
    'category' => 'fe',
    'author_company' => 'Scoria Labs GmbH',
    'state' => 'stable',
    'version' => '12.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'php' => '8.1.0-8.5.99',
            'form' => '12.4.0-13.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'CaptchaFox\\CaptchaFoxTypo3\\' => 'Classes/',
        ],
    ],
];
