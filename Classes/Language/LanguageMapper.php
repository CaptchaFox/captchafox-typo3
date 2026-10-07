<?php

declare(strict_types=1);

namespace CaptchaFox\CaptchaFoxTypo3\Language;

/**
 * Maps locales of TYPO3 site languages (de-DE, zh-TW, nb-NO …) to CaptchaFox widget language codes.
 *
 * Without an explicit language the widget follows the browser language, not the page language.
 * Explicit codes are not normalised by the widget, so the mapping must produce its exact codes.
 */
final class LanguageMapper
{
    /**
     * Language codes supported by the CaptchaFox widget (docs.captchafox.com/en/language-codes).
     */
    public const SUPPORTED = [
        'cs', 'da', 'de', 'en', 'es', 'fi', 'fr', 'ga', 'id', 'it', 'ja',
        'ko', 'nl', 'no', 'pl', 'pt', 'ru', 'sv', 'tr', 'uk', 'zh-cn', 'zh-tw',
    ];

    /**
     * Locales whose code differs from their primary subtag.
     */
    private const SPECIAL = [
        'zh-cn' => 'zh-cn',
        'zh-hans' => 'zh-cn',
        'zh-tw' => 'zh-tw',
        'zh-hk' => 'zh-tw',
        'zh-hant' => 'zh-tw',
        'nb' => 'no',
        'nn' => 'no',
    ];

    /**
     * @return string|null The CaptchaFox code, or null if the language is not supported (the widget then
     *                     falls back to the browser language)
     */
    public static function fromLocale(string $locale): ?string
    {
        // de_DE.UTF-8, de-DE and de all lead to "de".
        $locale = strtolower(str_replace('_', '-', explode('.', trim($locale))[0]));
        $parts = explode('-', $locale);
        $primary = $parts[0];
        $withRegion = isset($parts[1]) ? $primary . '-' . $parts[1] : $primary;

        foreach ([$withRegion, $primary] as $candidate) {
            if (isset(self::SPECIAL[$candidate])) {
                return self::SPECIAL[$candidate];
            }
        }

        // Other Chinese variants are ambiguous between simplified and traditional script.
        if ($primary === 'zh') {
            return null;
        }

        return in_array($primary, self::SUPPORTED, true) ? $primary : null;
    }
}
