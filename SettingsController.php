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
     * @AuthorizedAdminSetting(settings=OCA\DigitalSignage\Settings\AdminSettings)
     */
    public function saveAdmin(
        string $locale,
        string $weather_latitude,
        string $weather_longitude,
        string $slide_interval,
        string $calendar_name,
        string $image_folder,
        string $calendar_exclude
    ): JSONResponse {
        $this->config->setAppValue('digitalsignage', 'locale', $locale);
        $this->config->setAppValue('digitalsignage', 'weather_latitude', $weather_latitude);
        $this->config->setAppValue('digitalsignage', 'weather_longitude', $weather_longitude);
        $this->config->setAppValue('digitalsignage', 'slide_interval', $slide_interval);
        $this->config->setAppValue('digitalsignage', 'calendar_name', $calendar_name);
        $this->config->setAppValue('digitalsignage', 'image_folder', $image_folder);
        $this->config->setAppValue('digitalsignage', 'calendar_exclude', $calendar_exclude);

        return new JSONResponse(['status' => 'success']);
    }

    /**
     * @NoAdminRequired
     */
    public function saveUser(
        string $weather_latitude,
        string $weather_longitude,
        string $slide_interval,
        string $calendar_name,
        string $image_folder,
        string $calendar_exclude
    ): JSONResponse {
        $this->config->setAppValue('digitalsignage', 'weather_latitude', $weather_latitude);
        $this->config->setAppValue('digitalsignage', 'weather_longitude', $weather_longitude);
        $this->config->setAppValue('digitalsignage', 'slide_interval', $slide_interval);
        $this->config->setAppValue('digitalsignage', 'calendar_name', $calendar_name);
        $this->config->setAppValue('digitalsignage', 'image_folder', $image_folder);
        $this->config->setAppValue('digitalsignage', 'calendar_exclude', $calendar_exclude);

        return new JSONResponse(['status' => 'success']);
    }
