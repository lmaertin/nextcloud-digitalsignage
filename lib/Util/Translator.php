<?php
namespace OCA\DigitalSignage\Util;

use OCP\L10N\IFactory;

class Translator {
    private static $translations = [];
    private static $locale = 'en';
    private static $factory = null;

    public static function init(IFactory $factory, string $locale = 'en') {
        self::$factory = $factory;
        self::$locale = $locale;
        self::loadTranslations();
    }

    private static function loadTranslations() {
        $path = __DIR__ . '/../../l10n/' . self::$locale . '.json';
        if (file_exists($path)) {
            $content = file_get_contents($path);
            self::$translations = json_decode($content, true) ?? [];
        }
    }

    public static function t(string $key, ?string $fallback = null): string {
        if (isset(self::$translations[$key])) {
            return self::$translations[$key];
        }
        return $fallback ?? $key;
    }

    public static function getLocale(): string {
        return self::$locale;
    }

    public static function setLocale(string $locale) {
        self::$locale = $locale;
        self::loadTranslations();
    }
}
