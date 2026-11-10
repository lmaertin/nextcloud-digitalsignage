<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Service;

use OCA\DigitalSignage\Db\Preset;
use OCA\DigitalSignage\Db\PresetMapper;
use OCP\IConfig;

class PresetService {
    private PresetMapper $presetMapper;
    private IConfig $config;

    public function __construct(PresetMapper $presetMapper, IConfig $config) {
        $this->presetMapper = $presetMapper;
        $this->config = $config;
    }

    public function ensureDefaultPreset(string $userId): Preset {
        $defaultPreset = $this->presetMapper->findByNameForUser('Default', $userId);
        if ($defaultPreset !== null) {
            if ($this->normalizeImageOrderMode($defaultPreset->getImageOrderMode() ?? '') !== $defaultPreset->getImageOrderMode()) {
                $defaultPreset->setImageOrderMode($this->normalizeImageOrderMode($defaultPreset->getImageOrderMode() ?? ''));
                $defaultPreset->setUpdatedAt(time());
                $defaultPreset = $this->presetMapper->update($defaultPreset);
            }

            return $defaultPreset;
        }

        $now = time();
        $preset = new Preset();
        $preset->setUserId($userId);
        $preset->setName('Default');
        $preset->setImageFolder($this->config->getAppValue('digitalsignage', 'image_folder', '/Fotos'));
        $preset->setImageFitMode($this->config->getAppValue('digitalsignage', 'image_fit_mode', 'cover'));
        $preset->setImageOrderMode($this->normalizeImageOrderMode($this->config->getAppValue('digitalsignage', 'image_order_mode', 'shuffle')));
        $preset->setRecursiveMedia('0');
        $preset->setFullscreenSlideshow($this->config->getAppValue('digitalsignage', 'fullscreen_slideshow', '0'));
        $preset->setShowDisplayName($this->config->getAppValue('digitalsignage', 'show_display_name', '1'));
        $preset->setHeaderTitleSource($this->normalizeHeaderTitleSource(
            $this->config->getAppValue('digitalsignage', 'show_display_name', '1') === '1' ? 'global' : 'none'
        ));
        $preset->setShowSlideshow('1');
        $preset->setShowWeather('1');
        $preset->setShowCalendar('1');
        $preset->setShowEventDescription($this->config->getAppValue('digitalsignage', 'show_event_description', '0'));
        $preset->setCalendarNames($this->normalizeCalendarNames($this->config->getAppValue('digitalsignage', 'calendar_names', '[]')));
        $preset->setCalendarExclude($this->normalizeCalendarExclude($this->config->getAppValue('digitalsignage', 'calendar_exclude', '[]')));
        $preset->setSlideInterval((int)$this->config->getAppValue('digitalsignage', 'slide_interval', '10'));
        $preset->setCreatedAt($now);
        $preset->setUpdatedAt($now);

        return $this->presetMapper->insert($preset);
    }

    public function serializePreset(Preset $preset): array {
        $imageOrderMode = $this->normalizeImageOrderMode($preset->getImageOrderMode() ?? '');

        return [
            'id' => $preset->getId(),
            'name' => $preset->getName(),
            'imageFolder' => $preset->getImageFolder(),
            'imageFitMode' => $preset->getImageFitMode(),
            'imageOrderMode' => $imageOrderMode,
            'recursiveMedia' => $preset->getRecursiveMedia() === '1',
            'fullscreenSlideshow' => $preset->getFullscreenSlideshow() === '1',
            'showDisplayName' => $preset->getShowDisplayName() === '1',
            'headerTitleSource' => $this->normalizeHeaderTitleSource($preset->getHeaderTitleSource() ?? 'global'),
            'showSlideshow' => ($preset->getShowSlideshow() ?? '1') === '1',
            'showWeather' => ($preset->getShowWeather() ?? '1') === '1',
            'showCalendar' => ($preset->getShowCalendar() ?? '1') === '1',
            'showEventDescription' => ($preset->getShowEventDescription() ?? '0') === '1',
            'calendarNames' => json_decode($preset->getCalendarNames() ?: '[]', true) ?: [],
            'calendarExclude' => json_decode($preset->getCalendarExclude() ?: '[]', true) ?: [],
            'slideInterval' => $preset->getSlideInterval(),
            'createdAt' => $preset->getCreatedAt(),
            'updatedAt' => $preset->getUpdatedAt(),
        ];
    }

    public function normalizeImageOrderMode(string $imageOrderMode): string {
        return $imageOrderMode === 'filename' ? 'filename' : 'shuffle';
    }

    public function normalizeHeaderTitleSource(string $source): string {
        return in_array($source, ['global', 'preset', 'none'], true) ? $source : 'global';
    }

    public function normalizeCalendarNames(string $calendarNames): string {
        $names = json_decode($calendarNames, true);
        if (!is_array($names)) {
            return '[]';
        }

        $names = array_values(array_unique(array_filter($names, 'is_string')));
        return json_encode($names, JSON_THROW_ON_ERROR);
    }

    /**
     * Normalize calendar exclude entries. Accepts legacy plain strings
     * (substring rules hiding matching events) and rule objects with
     * term, match mode (contains|exact|wildcard) and inverse flag.
     */
    public function normalizeCalendarExclude(string $calendarExclude): string {
        $entries = json_decode($calendarExclude, true);
        if (!is_array($entries)) {
            return '[]';
        }

        $rules = [];
        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $entry = ['term' => $entry, 'match' => 'contains', 'inverse' => false];
            }
            if (!is_array($entry) || !isset($entry['term']) || !is_string($entry['term']) || trim($entry['term']) === '') {
                continue;
            }
            $rules[] = [
                'term' => $entry['term'],
                'match' => in_array($entry['match'] ?? 'contains', ['contains', 'exact', 'wildcard'], true) ? $entry['match'] ?? 'contains' : 'contains',
                'inverse' => ($entry['inverse'] ?? false) === true,
            ];
        }

        return json_encode($rules, JSON_THROW_ON_ERROR);
    }
}
