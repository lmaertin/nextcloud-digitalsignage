<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Service;

use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\Token;
use OCP\IConfig;

class DisplayConfigService {
    private IConfig $config;
    private PresetMapper $presetMapper;

    public function __construct(IConfig $config, PresetMapper $presetMapper) {
        $this->config = $config;
        $this->presetMapper = $presetMapper;
    }

    public function getEffectiveConfig(Token $display): array {
        $effective = [
            'displayName' => $this->config->getAppValue('digitalsignage', 'display_name', 'Digital Signage'),
            'showDisplayName' => $this->config->getAppValue('digitalsignage', 'show_display_name', '1'),
            'locale' => $this->config->getAppValue('digitalsignage', 'locale', 'de-DE'),
            'weatherLatitude' => (float)$this->config->getAppValue('digitalsignage', 'weather_latitude', '52.3758'),
            'weatherLongitude' => (float)$this->config->getAppValue('digitalsignage', 'weather_longitude', '9.9747'),
            'calendarExclude' => json_decode($this->config->getAppValue('digitalsignage', 'calendar_exclude', '[]'), true) ?: [],
            'autoFullscreenPrompt' => $this->config->getAppValue('digitalsignage', 'auto_fullscreen_prompt', '0') === '1',
            'textScale' => (float)$this->config->getAppValue('digitalsignage', 'text_scale', '1.0'),
            'imageFolder' => $this->config->getAppValue('digitalsignage', 'image_folder', '/Fotos'),
            'imageFitMode' => $this->config->getAppValue('digitalsignage', 'image_fit_mode', 'cover'),
            'imageOrderMode' => $this->config->getAppValue('digitalsignage', 'image_order_mode', 'shuffle'),
            'slideInterval' => (int)$this->config->getAppValue('digitalsignage', 'slide_interval', '10'),
            'fullscreenSlideshow' => $this->config->getAppValue('digitalsignage', 'fullscreen_slideshow', '0') === '1',
            'colorPrimary' => $this->config->getAppValue('digitalsignage', 'color_primary', '#0066cc'),
            'colorBg' => $this->config->getAppValue('digitalsignage', 'color_bg', '#f8f9fa'),
            'colorText' => $this->config->getAppValue('digitalsignage', 'color_text', '#2c3e50'),
            'colorGradientStart' => $this->config->getAppValue('digitalsignage', 'color_gradient_start', '#0066cc'),
            'colorGradientEnd' => $this->config->getAppValue('digitalsignage', 'color_gradient_end', '#3399ff'),
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
                $effective['slideInterval'] = $preset->getSlideInterval();
                $effective['fullscreenSlideshow'] = $preset->getFullscreenSlideshow() === '1';
                $effective['activePresetName'] = $preset->getName();
            }
        }

        return $effective;
    }
}
