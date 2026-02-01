<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;

class SettingsController extends Controller {
    private $config;

    public function __construct(
        string $AppName,
        IRequest $request,
        IConfig $config
    ) {
        parent::__construct($AppName, $request);
        $this->config = $config;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveUser(
        string $display_name = '',
        string $show_display_name = '1',
        string $auto_fullscreen_prompt = '0',
        string $weather_latitude = '52.52',
        string $weather_longitude = '13.405',
        string $slide_interval = '60',
        string $calendar_names = '[]',
        string $image_folder = '',
        string $calendar_exclude = '[]',
        string $color_primary = '#0066cc',
        string $color_bg = '#f8f9fa',
        string $color_text = '#2c3e50',
        string $color_gradient_start = '#0066cc',
        string $color_gradient_end = '#3399ff',
        string $show_titlebar = '1'
    ): JSONResponse {
        $this->config->setAppValue('digitalsignage', 'display_name', $display_name);
        $this->config->setAppValue('digitalsignage', 'show_display_name', $show_display_name);
        $this->config->setAppValue('digitalsignage', 'auto_fullscreen_prompt', $auto_fullscreen_prompt);
        $this->config->setAppValue('digitalsignage', 'weather_latitude', $weather_latitude);
        $this->config->setAppValue('digitalsignage', 'weather_longitude', $weather_longitude);
        $this->config->setAppValue('digitalsignage', 'slide_interval', $slide_interval);
        $this->config->setAppValue('digitalsignage', 'calendar_names', $calendar_names);
        $this->config->setAppValue('digitalsignage', 'image_folder', $image_folder);
        $this->config->setAppValue('digitalsignage', 'calendar_exclude', $calendar_exclude);
        $this->config->setAppValue('digitalsignage', 'color_primary', $color_primary);
        $this->config->setAppValue('digitalsignage', 'color_bg', $color_bg);
        $this->config->setAppValue('digitalsignage', 'color_text', $color_text);
        $this->config->setAppValue('digitalsignage', 'color_gradient_start', $color_gradient_start);
        $this->config->setAppValue('digitalsignage', 'color_gradient_end', $color_gradient_end);
        $this->config->setAppValue('digitalsignage', 'show_titlebar', $show_titlebar);
        return new JSONResponse(['status' => 'success']);
    }

    /**
     * @NoAdminRequired
     */
    public function getUser(): JSONResponse {
        $display_name = $this->config->getAppValue('digitalsignage', 'display_name', '');
        $show_display_name = $this->config->getAppValue('digitalsignage', 'show_display_name', '1');
        $auto_fullscreen_prompt = $this->config->getAppValue('digitalsignage', 'auto_fullscreen_prompt', '0');
        $weather_latitude = $this->config->getAppValue('digitalsignage', 'weather_latitude', '52.52');
        $weather_longitude = $this->config->getAppValue('digitalsignage', 'weather_longitude', '13.405');
        $slide_interval = $this->config->getAppValue('digitalsignage', 'slide_interval', '60');
        $calendar_names = $this->config->getAppValue('digitalsignage', 'calendar_names', '[]');
        $image_folder = $this->config->getAppValue('digitalsignage', 'image_folder', '');
        $calendar_exclude = $this->config->getAppValue('digitalsignage', 'calendar_exclude', '[]');
        $color_primary = $this->config->getAppValue('digitalsignage', 'color_primary', '#0066cc');
        $color_bg = $this->config->getAppValue('digitalsignage', 'color_bg', '#f8f9fa');
        $color_text = $this->config->getAppValue('digitalsignage', 'color_text', '#2c3e50');
        $color_gradient_start = $this->config->getAppValue('digitalsignage', 'color_gradient_start', '#0066cc');
        $color_gradient_end = $this->config->getAppValue('digitalsignage', 'color_gradient_end', '#3399ff');
        $show_titlebar = $this->config->getAppValue('digitalsignage', 'show_titlebar', '1');

        return new JSONResponse([
            'display_name' => $display_name,
            'show_display_name' => $show_display_name,
            'auto_fullscreen_prompt' => $auto_fullscreen_prompt,
            'weather_latitude' => $weather_latitude,
            'weather_longitude' => $weather_longitude,
            'slide_interval' => $slide_interval,
            'calendar_names' => $calendar_names,
            'image_folder' => $image_folder,
            'calendar_exclude' => $calendar_exclude,
            'color_primary' => $color_primary,
            'color_bg' => $color_bg,
            'color_text' => $color_text,
            'color_gradient_start' => $color_gradient_start,
            'color_gradient_end' => $color_gradient_end,
            'show_titlebar' => $show_titlebar,
        ]);
    }
}
