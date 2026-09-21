<?php
namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Util\TextSizeConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;

class SettingsController extends Controller {
    private $config;

    private const DEFAULT_IMAGE_REFRESH_INTERVAL_MINUTES = 15;

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
        string $auto_fullscreen_prompt = '0',
        string $content_split_ratio = '50',
        string $slide_interval = '60',
        string $image_refresh_interval_minutes = '15',
        string $image_folder = '',
        string $color_primary = '#0066cc',
        string $color_bg = '#f8f9fa',
        string $color_text = '#2c3e50',
        string $color_gradient_start = '#0066cc',
        string $color_gradient_end = '#3399ff',
        string $show_titlebar = '1',
        string $image_fit_mode = 'cover',
        string $text_size_display_title = '',
        string $text_size_clock_time = '',
        string $text_size_clock_date = '',
        string $text_size_weather_temperature = '',
        string $text_size_weather_day = '',
        string $text_size_appointments_title = '',
        string $text_size_appointments_time = '',
        string $text_size_appointments_location = '',
        string $fullscreen_slideshow = '0',
        string $message_bg_color = '#0066cc',
        string $message_bg_opacity = '50',
        string $message_text_color = '#ffffff',
        string $message_font_size = '1.0',
        string $message_width_percent = '88',
        string $message_position = 'top'
    ): JSONResponse {
        $normalizedContentSplitRatio = (string)max(50, min(85, (int)$content_split_ratio ?: 50));
        $imageRefreshValue = (int)$image_refresh_interval_minutes;
        $normalizedImageRefreshInterval = (string)($imageRefreshValue >= 0 ? $imageRefreshValue : self::DEFAULT_IMAGE_REFRESH_INTERVAL_MINUTES);
        $normalizedMessageFontSize = (string)max(0.5, min(4.0, (float)$message_font_size ?: 1.0));
        $normalizedMessageWidthPercent = (string)max(20, min(100, (int)$message_width_percent ?: 88));
        $normalizedMessageBgOpacity = (string)max(0, min(100, (int)$message_bg_opacity));
        $normalizedMessagePosition = in_array($message_position, ['top', 'middle', 'bottom'], true) ? $message_position : 'top';

        $this->config->setAppValue('digitalsignage', 'auto_fullscreen_prompt', $auto_fullscreen_prompt);
        $this->config->setAppValue('digitalsignage', 'content_split_ratio', $normalizedContentSplitRatio);
        $this->config->deleteAppValue('digitalsignage', 'left_column_split_ratio');
        $this->config->setAppValue('digitalsignage', 'slide_interval', $slide_interval);
        $this->config->setAppValue('digitalsignage', 'image_refresh_interval_minutes', $normalizedImageRefreshInterval);
        $this->config->setAppValue('digitalsignage', 'image_folder', $image_folder);
        $this->config->setAppValue('digitalsignage', 'color_primary', $color_primary);
        $this->config->setAppValue('digitalsignage', 'color_bg', $color_bg);
        $this->config->setAppValue('digitalsignage', 'color_text', $color_text);
        $this->config->setAppValue('digitalsignage', 'color_gradient_start', $color_gradient_start);
        $this->config->setAppValue('digitalsignage', 'color_gradient_end', $color_gradient_end);
        $this->config->setAppValue('digitalsignage', 'show_titlebar', $show_titlebar);
        $this->config->setAppValue('digitalsignage', 'image_fit_mode', $image_fit_mode);
        TextSizeConfig::saveConfiguredSizes($this->config, [
            'display_title' => $text_size_display_title,
            'clock_time' => $text_size_clock_time,
            'clock_date' => $text_size_clock_date,
            'weather_temperature' => $text_size_weather_temperature,
            'weather_day' => $text_size_weather_day,
            'appointments_title' => $text_size_appointments_title,
            'appointments_time' => $text_size_appointments_time,
            'appointments_location' => $text_size_appointments_location,
        ]);
        $this->config->setAppValue('digitalsignage', 'fullscreen_slideshow', $fullscreen_slideshow);
        $this->config->setAppValue('digitalsignage', 'message_bg_color', $message_bg_color);
        $this->config->setAppValue('digitalsignage', 'message_bg_opacity', $normalizedMessageBgOpacity);
        $this->config->setAppValue('digitalsignage', 'message_text_color', $message_text_color);
        $this->config->setAppValue('digitalsignage', 'message_font_size', $normalizedMessageFontSize);
        $this->config->setAppValue('digitalsignage', 'message_width_percent', $normalizedMessageWidthPercent);
        $this->config->setAppValue('digitalsignage', 'message_position', $normalizedMessagePosition);
        return new JSONResponse(['status' => 'success']);
    }

    /**
     * @NoAdminRequired
     */
    public function getUser(): JSONResponse {
        $auto_fullscreen_prompt = $this->config->getAppValue('digitalsignage', 'auto_fullscreen_prompt', '0');
        $content_split_ratio = $this->config->getAppValue('digitalsignage', 'content_split_ratio', '50');
        $slide_interval = $this->config->getAppValue('digitalsignage', 'slide_interval', '60');
        $image_refresh_interval_minutes = $this->config->getAppValue('digitalsignage', 'image_refresh_interval_minutes', '15');
        $image_folder = $this->config->getAppValue('digitalsignage', 'image_folder', '');
        $color_primary = $this->config->getAppValue('digitalsignage', 'color_primary', '#0066cc');
        $color_bg = $this->config->getAppValue('digitalsignage', 'color_bg', '#f8f9fa');
        $color_text = $this->config->getAppValue('digitalsignage', 'color_text', '#2c3e50');
        $color_gradient_start = $this->config->getAppValue('digitalsignage', 'color_gradient_start', '#0066cc');
        $color_gradient_end = $this->config->getAppValue('digitalsignage', 'color_gradient_end', '#3399ff');
        $show_titlebar = $this->config->getAppValue('digitalsignage', 'show_titlebar', '1');
        $image_fit_mode = $this->config->getAppValue('digitalsignage', 'image_fit_mode', 'cover');
        $text_sizes = TextSizeConfig::getConfiguredSizes($this->config);
        $fullscreen_slideshow = $this->config->getAppValue('digitalsignage', 'fullscreen_slideshow', '0');
        $message_bg_color = $this->config->getAppValue('digitalsignage', 'message_bg_color', '#0066cc');
        $message_bg_opacity = $this->config->getAppValue('digitalsignage', 'message_bg_opacity', '50');
        $message_text_color = $this->config->getAppValue('digitalsignage', 'message_text_color', '#ffffff');
        $message_font_size = $this->config->getAppValue('digitalsignage', 'message_font_size', '1.0');
        $message_width_percent = $this->config->getAppValue('digitalsignage', 'message_width_percent', '88');
        $message_position = $this->config->getAppValue('digitalsignage', 'message_position', 'top');

        return new JSONResponse([
            'auto_fullscreen_prompt' => $auto_fullscreen_prompt,
            'content_split_ratio' => $content_split_ratio,
            'slide_interval' => $slide_interval,
            'image_refresh_interval_minutes' => $image_refresh_interval_minutes,
            'image_folder' => $image_folder,
            'color_primary' => $color_primary,
            'color_bg' => $color_bg,
            'color_text' => $color_text,
            'color_gradient_start' => $color_gradient_start,
            'color_gradient_end' => $color_gradient_end,
            'show_titlebar' => $show_titlebar,
            'image_fit_mode' => $image_fit_mode,
            'text_sizes' => $text_sizes,
            'fullscreen_slideshow' => $fullscreen_slideshow,
            'message_bg_color' => $message_bg_color,
            'message_bg_opacity' => $message_bg_opacity,
            'message_text_color' => $message_text_color,
            'message_font_size' => $message_font_size,
            'message_width_percent' => $message_width_percent,
            'message_position' => $message_position,
        ]);
    }
}
