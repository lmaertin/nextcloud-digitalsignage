<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Util;

use OCP\IConfig;

class TextSizeConfig {
    private const LEGACY_CONFIG_KEYS = [
        'text_scale',
        'text_size_forecast_temperature',
        'text_size_weather_location',
        'text_size_forecast_day_label',
        'text_size_calendar_event_title',
        'text_size_calendar_entry',
    ];

    private const FIELDS = [
        'display_title' => [
            'configKey' => 'text_size_display_title',
            'cssVariable' => '--font-size-display-title',
            'label' => 'Display title',
            'default' => '1.2',
        ],
        'clock_time' => [
            'configKey' => 'text_size_clock_time',
            'cssVariable' => '--font-size-clock-time',
            'label' => 'Clock time',
            'default' => '2.4',
        ],
        'clock_date' => [
            'configKey' => 'text_size_clock_date',
            'cssVariable' => '--font-size-clock-date',
            'label' => 'Clock date',
            'default' => '1.2',
        ],
        'weather_temperature' => [
            'configKey' => 'text_size_weather_temperature',
            'cssVariable' => '--font-size-weather-temperature',
            'label' => 'Weather temperature',
            'default' => '1.2',
        ],
        'weather_day' => [
            'configKey' => 'text_size_weather_day',
            'cssVariable' => '--font-size-weather-day',
            'label' => 'Weather day',
            'default' => '1.2',
        ],
        'appointments_title' => [
            'configKey' => 'text_size_appointments_title',
            'cssVariable' => '--font-size-appointments-title',
            'label' => 'Calendar title',
            'default' => '1.2',
        ],
        'appointments_time' => [
            'configKey' => 'text_size_appointments_time',
            'cssVariable' => '--font-size-appointments-time',
            'label' => 'Calendar time',
            'default' => '1.2',
        ],
        'appointments_location' => [
            'configKey' => 'text_size_appointments_location',
            'cssVariable' => '--font-size-appointments-location',
            'label' => 'Calendar location',
            'default' => '1.2',
        ],
    ];

    public static function getConfiguredSizes(IConfig $config): array {
        $defaults = self::getDefaults();
        $sizes = [];

        foreach (self::FIELDS as $name => $field) {
            $sizes[$name] = self::sanitizeValue(
                $config->getAppValue('digitalsignage', $field['configKey'], $defaults[$name]),
                $defaults[$name]
            );
        }

        return $sizes;
    }

    public static function getFieldDefinitions(IConfig $config): array {
        $configuredSizes = self::getEditableSizes($config);
        $defaults = self::getBaseDefaults();
        $fields = [];

        foreach (self::FIELDS as $name => $field) {
            $fields[$name] = [
                'key' => $name,
                'configKey' => $field['configKey'],
                'cssVariable' => $field['cssVariable'],
                'label' => $field['label'],
                'value' => $configuredSizes[$name],
                'default' => $defaults[$name],
            ];
        }

        return $fields;
    }

    public static function getEditableSizes(IConfig $config): array {
        return self::getConfiguredSizes($config);
    }

    public static function toCssVariables(array $sizes): array {
        $defaults = self::getDefaults();
        $cssVariables = [];

        foreach (self::FIELDS as $name => $field) {
            $cssVariables[$field['cssVariable']] = self::normalizeForCss(
                (string)($sizes[$name] ?? $defaults[$name]),
                $defaults[$name]
            );
        }

        return $cssVariables;
    }

    public static function saveConfiguredSizes(IConfig $config, array $values): void {
        $defaults = self::getDefaults();

        foreach (self::FIELDS as $name => $field) {
            $config->setAppValue(
                'digitalsignage',
                $field['configKey'],
                self::sanitizeValue((string)($values[$name] ?? $defaults[$name]), $defaults[$name])
            );
        }

        self::removeLegacyConfig($config);
    }

    public static function getDefaults(): array {
        return self::getBaseDefaults();
    }

    private static function getBaseDefaults(): array {
        $defaults = [];
        foreach (self::FIELDS as $name => $field) {
            $defaults[$name] = $field['default'];
        }

        return $defaults;
    }

    private static function sanitizeValue(string $value, string $fallback): string {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return $fallback;
        }

        if (is_numeric($trimmed)) {
            return self::formatNumber((float)$trimmed);
        }

        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*(rem|em)$/i', $trimmed, $matches)) {
            return self::formatNumber((float)$matches[1]);
        }

        if (strlen($trimmed) > 60) {
            return $fallback;
        }

        return $fallback;
    }

    private static function normalizeForCss(string $value, string $fallback): string {
        return self::sanitizeValue($value, $fallback) . 'rem';
    }

    private static function removeLegacyConfig(IConfig $config): void {
        foreach (self::LEGACY_CONFIG_KEYS as $configKey) {
            $config->deleteAppValue('digitalsignage', $configKey);
        }
    }

    private static function formatNumber(float $value): string {
        return rtrim(rtrim(sprintf('%.3F', $value), '0'), '.');
    }
}
