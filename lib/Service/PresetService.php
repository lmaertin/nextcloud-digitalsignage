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
        $preset->setFullscreenSlideshow($this->config->getAppValue('digitalsignage', 'fullscreen_slideshow', '0'));
        $preset->setShowDisplayName($this->config->getAppValue('digitalsignage', 'show_display_name', '1'));
        $preset->setShowSlideshow('1');
        $preset->setShowWeather('1');
        $preset->setShowCalendar('1');
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
            'fullscreenSlideshow' => $preset->getFullscreenSlideshow() === '1',
            'showDisplayName' => $preset->getShowDisplayName() === '1',
            'showSlideshow' => ($preset->getShowSlideshow() ?? '1') === '1',
            'showWeather' => ($preset->getShowWeather() ?? '1') === '1',
            'showCalendar' => ($preset->getShowCalendar() ?? '1') === '1',
            'slideInterval' => $preset->getSlideInterval(),
            'createdAt' => $preset->getCreatedAt(),
            'updatedAt' => $preset->getUpdatedAt(),
        ];
    }

    public function normalizeImageOrderMode(string $imageOrderMode): string {
        return $imageOrderMode === 'filename' ? 'filename' : 'shuffle';
    }
}
