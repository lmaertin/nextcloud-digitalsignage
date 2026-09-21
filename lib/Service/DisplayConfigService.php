<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Service;

use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Util\TextSizeConfig;
use OCP\IConfig;

class DisplayConfigService {
    private IConfig $config;
    private PresetMapper $presetMapper;

    public function __construct(IConfig $config, PresetMapper $presetMapper) {
        $this->config = $config;
        $this->presetMapper = $presetMapper;
    }

    private function normalizeLocale(string $locale): string {
        $normalized = str_replace('_', '-', trim($locale));
        if ($normalized === '') {
            return 'en';
        }

        if (preg_match('/^[a-z]{2}$/i', $normalized) === 1) {
            return strtolower($normalized);
        }

        if (preg_match('/^[a-z]{2}-[a-z]{2}$/i', $normalized) === 1) {
            $parts = explode('-', $normalized, 2);
            return strtolower($parts[0]) . '-' . strtoupper($parts[1]);
        }

        return 'en';
    }

    private function resolveLocale(Token $display): string {
        $configuredLocale = trim($this->config->getAppValue('digitalsignage', 'locale', ''));
        if ($configuredLocale !== '') {
            return $this->normalizeLocale($configuredLocale);
        }

        $userLocale = trim($this->config->getUserValue($display->getUserId(), 'core', 'lang', 'en'));
        if ($userLocale !== '') {
            return $this->normalizeLocale($userLocale);
        }

        return 'en';
    }

    private function resolveTimeZone(Token $display): string {
        $displayTimeZone = $display->getTimeZone();
        if (is_string($displayTimeZone) && $this->isValidTimeZone($displayTimeZone)) {
            return $displayTimeZone;
        }

        $userTimeZone = trim($this->config->getUserValue($display->getUserId(), 'core', 'timezone', ''));
        return $this->isValidTimeZone($userTimeZone) ? $userTimeZone : '';
    }

    public function getEffectiveConfig(Token $display): array {
        $effective = [
            'displayName' => $display->getName() ?: 'Digital Signage',
            'showDisplayName' => '1',
            'headerTitleSource' => 'global',
            'locale' => $this->resolveLocale($display),
            'timeZone' => $this->resolveTimeZone($display),
            'weatherLatitude' => $display->getWeatherLatitude(),
            'weatherLongitude' => $display->getWeatherLongitude(),
            'contentSplitRatio' => max(50, min(85, (int)$this->config->getAppValue('digitalsignage', 'content_split_ratio', '50'))),
            'calendarExclude' => json_decode($this->config->getAppValue('digitalsignage', 'calendar_exclude', '[]'), true) ?: [],
            'calendarNames' => [],
            'calendarExclude' => [],
            'autoFullscreenPrompt' => $this->config->getAppValue('digitalsignage', 'auto_fullscreen_prompt', '0') === '1',
            'textSizes' => TextSizeConfig::getConfiguredSizes($this->config),
            'textSizeCssVariables' => TextSizeConfig::toCssVariables(TextSizeConfig::getConfiguredSizes($this->config)),
            'imageFolder' => $this->config->getAppValue('digitalsignage', 'image_folder', '/Fotos'),
            'imageFitMode' => $this->config->getAppValue('digitalsignage', 'image_fit_mode', 'cover'),
            'imageOrderMode' => $this->config->getAppValue('digitalsignage', 'image_order_mode', 'shuffle'),
            'recursiveMedia' => false,
            'slideInterval' => (int)$this->config->getAppValue('digitalsignage', 'slide_interval', '10'),
            'imageRefreshIntervalMinutes' => max(0, (float)$this->config->getAppValue('digitalsignage', 'image_refresh_interval_minutes', '15')),
            'fullscreenSlideshow' => $this->config->getAppValue('digitalsignage', 'fullscreen_slideshow', '0') === '1',
            'showSlideshow' => true,
            'showWeather' => true,
            'showCalendar' => true,
            'showEventDescription' => $this->config->getAppValue('digitalsignage', 'show_event_description', '0') === '1',
            'colorPrimary' => $this->config->getAppValue('digitalsignage', 'color_primary', '#0066cc'),
            'colorBg' => $this->config->getAppValue('digitalsignage', 'color_bg', '#f8f9fa'),
            'colorText' => $this->config->getAppValue('digitalsignage', 'color_text', '#2c3e50'),
            'colorGradientStart' => $this->config->getAppValue('digitalsignage', 'color_gradient_start', '#0066cc'),
            'colorGradientEnd' => $this->config->getAppValue('digitalsignage', 'color_gradient_end', '#3399ff'),
            'messageBgColor' => $this->config->getAppValue('digitalsignage', 'message_bg_color', '#20262f'),
            'messageBgOpacity' => (int)$this->config->getAppValue('digitalsignage', 'message_bg_opacity', '86'),
            'messageTextColor' => $this->config->getAppValue('digitalsignage', 'message_text_color', '#ffffff'),
            'messageFontSize' => $this->config->getAppValue('digitalsignage', 'message_font_size', '1.0'),
            'messageWidthPercent' => $this->config->getAppValue('digitalsignage', 'message_width_percent', '88'),
            'messagePosition' => $this->normalizeMessagePosition($this->config->getAppValue('digitalsignage', 'message_position', 'top')),
            'activePresetId' => $display->getActivePresetId(),
            'activePresetName' => null,
            'revision' => $display->getRevision() ?: 1,
        ];

        $presetId = $display->getActivePresetId();
        if ($presetId !== null) {
            $preset = $this->presetMapper->findForUser($presetId, $display->getUserId());
            if ($preset !== null) {
                $effective['imageFolder'] = $preset->getImageFolder();
                $effective['imageFitMode'] = $preset->getImageFitMode();
                $effective['imageOrderMode'] = $preset->getImageOrderMode() === 'filename' ? 'filename' : 'shuffle';
                $effective['recursiveMedia'] = ($preset->getRecursiveMedia() ?? '0') === '1';
                $effective['slideInterval'] = $preset->getSlideInterval();
                $effective['fullscreenSlideshow'] = $preset->getFullscreenSlideshow() === '1';
                $effective['showDisplayName'] = $preset->getShowDisplayName() ?? '1';
                $effective['headerTitleSource'] = $this->normalizeHeaderTitleSource($preset->getHeaderTitleSource() ?? 'global');
                $effective['showDisplayName'] = $effective['headerTitleSource'] !== 'none' ? '1' : '0';
                if ($effective['headerTitleSource'] === 'preset') {
                    $effective['displayName'] = $preset->getName();
                }
                $effective['showSlideshow'] = ($preset->getShowSlideshow() ?? '1') === '1';
                $effective['showWeather'] = ($preset->getShowWeather() ?? '1') === '1';
                $effective['showCalendar'] = ($preset->getShowCalendar() ?? '1') === '1';
                $effective['showEventDescription'] = ($preset->getShowEventDescription() ?? '0') === '1';
                $effective['calendarNames'] = json_decode($preset->getCalendarNames() ?: '[]', true) ?: [];
                $effective['calendarExclude'] = json_decode($preset->getCalendarExclude() ?: '[]', true) ?: [];
                $effective['activePresetName'] = $preset->getName();
            }
        }

        if ($effective['fullscreenSlideshow']) {
            $effective['showSlideshow'] = true;
            $effective['showWeather'] = false;
            $effective['showCalendar'] = false;
        }

        $effective['messageBgColorRgba'] = self::hexToRgba($effective['messageBgColor'], $effective['messageBgOpacity']);

        return $effective;
    }

    private function normalizeHeaderTitleSource(string $source): string {
        return in_array($source, ['global', 'preset', 'none'], true) ? $source : 'global';
    }

    private function normalizeMessagePosition(string $position): string {
        return in_array($position, ['top', 'middle', 'bottom'], true) ? $position : 'top';
    }

    public static function hexToRgba(string $hex, int $opacityPercent): string {
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $matches) !== 1) {
            return 'rgba(32, 38, 48, 0.86)';
        }

        $red = hexdec(substr($matches[1], 0, 2));
        $green = hexdec(substr($matches[1], 2, 2));
        $blue = hexdec(substr($matches[1], 4, 2));
        $alpha = max(0, min(100, $opacityPercent)) / 100;

        return sprintf('rgba(%d, %d, %d, %s)', $red, $green, $blue, rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.') ?: '0');
    }

    private function isValidTimeZone(string $timeZone): bool {
        return in_array($timeZone, \DateTimeZone::listIdentifiers(), true);
    }
}
