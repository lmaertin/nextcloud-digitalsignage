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
            'calendar_names' => $this->config->getAppValue('digitalsignage', 'calendar_names', '[]'),
            'image_folder' => $this->config->getAppValue('digitalsignage', 'image_folder', '/Fotos'),
            'slide_interval' => $this->config->getAppValue('digitalsignage', 'slide_interval', '10'),
            'weather_latitude' => $this->config->getAppValue('digitalsignage', 'weather_latitude', '52.3758'),
            'weather_longitude' => $this->config->getAppValue('digitalsignage', 'weather_longitude', '9.9747'),
            'calendar_exclude' => $this->config->getAppValue('digitalsignage', 'calendar_exclude', '[]'),
        ];
        
        return new TemplateResponse(
            'digitalsignage',
            'index',
            $params
        );
    }
}
