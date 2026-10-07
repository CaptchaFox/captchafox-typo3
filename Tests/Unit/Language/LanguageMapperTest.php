<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Tests\Unit\Language;

use CaptchaFox\CaptchaFoxTypo3\Language\LanguageMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LanguageMapperTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function locales(): array
    {
        return [
            'TYPO3 locale with encoding' => ['de_DE.UTF-8', 'de'],
            'BCP 47 tag' => ['de-DE', 'de'],
            'language only' => ['en', 'en'],
            'upper case' => ['FR_FR', 'fr'],
            'surrounding space' => [' it-IT ', 'it'],
            'Chinese, mainland' => ['zh-CN', 'zh-cn'],
            'Chinese, simplified script' => ['zh-Hans', 'zh-cn'],
            'Chinese, Taiwan' => ['zh_TW.UTF-8', 'zh-tw'],
            'Chinese, Hong Kong' => ['zh-HK', 'zh-tw'],
            'Chinese, traditional script' => ['zh-Hant', 'zh-tw'],
            'Chinese, ambiguous' => ['zh', null],
            'Norwegian Bokmål' => ['nb-NO', 'no'],
            'Norwegian Nynorsk' => ['nn_NO.UTF-8', 'no'],
            'Portuguese, Brazil' => ['pt-BR', 'pt'],
            'Irish' => ['ga-IE', 'ga'],
            'Ukrainian' => ['uk-UA', 'uk'],
            'not supported: Catalan' => ['ca-ES', null],
            'not supported: Greek' => ['el_GR.UTF-8', null],
            'C locale' => ['C.UTF-8', null],
            'empty' => ['', null],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function mapsLocalesToWidgetCodes(string $locale, ?string $expected): void
    {
        self::assertSame($expected, LanguageMapper::fromLocale($locale));
    }

    #[Test]
    public function everySupportedCodeMapsToItself(): void
    {
        foreach (LanguageMapper::SUPPORTED as $code) {
            self::assertSame($code, LanguageMapper::fromLocale($code), $code);
        }
    }
}
