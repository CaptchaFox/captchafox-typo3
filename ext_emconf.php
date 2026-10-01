<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CaptchaFox',
    'description' => 'Protects TYPO3 forms (EXT:form) against bots with CaptchaFox, verified on the server.',
    'category' => 'fe',
    'author_company' => 'Scoria Labs GmbH',
    'state' => 'stable',
    'version' => '12.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.99.99',
            'php' => '8.2.0-8.5.99',
            'form' => '14.3.0-14.99.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'CaptchaFox\\CaptchaFoxTypo3\\' => 'Classes/',
        ],
    ],
];
