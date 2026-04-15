<?php
namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Util\TextSizeConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\L10N\IFactory;

class PageController extends Controller {
    private $config;
    private $userId;
    private $l10nFactory;

    public function __construct(
        string $AppName,
        IRequest $request,
        IConfig $config,
        IFactory $l10nFactory,
        ?string $UserId
    ) {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->l10nFactory = $l10nFactory;
        $this->userId = $UserId;
    }

    private function resolveAppLanguage(?string $language, ?string $locale = null): string {
        if (is_string($language) && $language !== '' && $this->l10nFactory->languageExists('digitalsignage', $language)) {
            return $language;
        }

        if (is_string($locale) && $locale !== '') {
            $localeLanguage = $this->l10nFactory->findLanguageFromLocale('digitalsignage', str_replace('-', '_', $locale));
            if (is_string($localeLanguage) && $localeLanguage !== '') {
                return $localeLanguage;
            }
        }

        return 'en';
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        $userLanguage = $this->userId !== null
            ? $this->config->getUserValue($this->userId, 'core', 'lang', 'en')
            : 'en';
        $userLocale = $this->userId !== null
            ? $this->config->getUserValue($this->userId, 'core', 'locale', null)
            : null;

        // Load current settings for display in the form
        $params = [
            'translation_lang' => $this->resolveAppLanguage($userLanguage, $userLocale),
            'display_name' => $this->config->getAppValue('digitalsignage', 'display_name', 'Digital Signage'),
            'show_display_name' => $this->config->getAppValue('digitalsignage', 'show_display_name', '1'),
            'auto_fullscreen_prompt' => $this->config->getAppValue('digitalsignage', 'auto_fullscreen_prompt', '0'),
            'content_split_ratio' => $this->config->getAppValue('digitalsignage', 'content_split_ratio', '50'),
            'calendar_names' => $this->config->getAppValue('digitalsignage', 'calendar_names', '[]'),
            'image_folder' => $this->config->getAppValue('digitalsignage', 'image_folder', '/Fotos'),
            'slide_interval' => $this->config->getAppValue('digitalsignage', 'slide_interval', '10'),
            'image_fit_mode' => $this->config->getAppValue('digitalsignage', 'image_fit_mode', 'cover'),
            'text_sizes' => TextSizeConfig::getConfiguredSizes($this->config),
            'text_size_fields' => TextSizeConfig::getFieldDefinitions($this->config),
            'fullscreen_slideshow' => $this->config->getAppValue('digitalsignage', 'fullscreen_slideshow', '0'),
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
