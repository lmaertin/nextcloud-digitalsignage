<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;

class PageController extends Controller {
    private $config;
    private $userId;

    public function __construct(
        string $AppName,
        IRequest $request,
        IConfig $config,
        ?string $UserId
    ) {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->userId = $UserId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        // Load current settings for display in the form
        $params = [
            'display_name' => $this->config->getAppValue('digitalsignage', 'display_name', 'Digital Signage'),
            'show_display_name' => $this->config->getAppValue('digitalsignage', 'show_display_name', '1'),
            'auto_fullscreen_prompt' => $this->config->getAppValue('digitalsignage', 'auto_fullscreen_prompt', '0'),
            'calendar_names' => $this->config->getAppValue('digitalsignage', 'calendar_names', '[]'),
            'image_folder' => $this->config->getAppValue('digitalsignage', 'image_folder', '/Fotos'),
            'slide_interval' => $this->config->getAppValue('digitalsignage', 'slide_interval', '10'),
            'image_fit_mode' => $this->config->getAppValue('digitalsignage', 'image_fit_mode', 'cover'),
            'weather_latitude' => $this->config->getAppValue('digitalsignage', 'weather_latitude', '52.52'),
            'weather_longitude' => $this->config->getAppValue('digitalsignage', 'weather_longitude', '13.405'),
            'calendar_exclude' => $this->config->getAppValue('digitalsignage', 'calendar_exclude', '[]'),
            'color_primary' => $this->config->getAppValue('digitalsignage', 'color_primary', '#0066cc'),
            'color_bg' => $this->config->getAppValue('digitalsignage', 'color_bg', '#f8f9fa'),
            'color_text' => $this->config->getAppValue('digitalsignage', 'color_text', '#2c3e50'),
            'color_gradient_start' => $this->config->getAppValue('digitalsignage', 'color_gradient_start', '#0066cc'),
            'color_gradient_end' => $this->config->getAppValue('digitalsignage', 'color_gradient_end', '#3399ff'),
            'show_titlebar' => $this->config->getAppValue('digitalsignage', 'show_titlebar', '1'),
        ];

        return new TemplateResponse(
            'digitalsignage',
            'index',
            $params
        );
    }
}
