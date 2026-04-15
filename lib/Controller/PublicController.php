<?php
namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Util\TextSizeConfig;
use OCP\AppFramework\PublicShareController;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;
use OCP\ISession;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\DisplayConfigService;

use OCP\IConfig;
use OCP\L10N\IFactory;

class PublicController extends PublicShareController {
    private $tokenMapper;
    private $config;
    private $displayConfigService;
    private $l10nFactory;

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

    public function __construct(
        string $AppName,
        IRequest $request,
        ISession $session,
        TokenMapper $tokenMapper,
        IConfig $config,
        DisplayConfigService $displayConfigService,
        IFactory $l10nFactory
    ) {
        parent::__construct($AppName, $request, $session);
        $this->tokenMapper = $tokenMapper;
        $this->config = $config;
        $this->displayConfigService = $displayConfigService;
        $this->l10nFactory = $l10nFactory;
    }

    protected function getPasswordHash(): ?string {
        return null; // No password protection
    }

    public function isPasswordProtected(): bool {
        return false; // No password protection
    }

    public function isValidToken(): bool {
        try {
            $token = $this->request->getParam('token');
            if (empty($token)) {
                return false;
            }

            $tokenEntity = $this->tokenMapper->findByToken($token);
            return $tokenEntity !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function verifyPassword(string $password): bool {
        return true; // No password required
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function showAuthenticate(): TemplateResponse {
        return new TemplateResponse('digitalsignage', 'public_display', [], 'blank');
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function display(string $token): TemplateResponse {
        try {
            // Verify token exists
            $tokenEntity = $this->tokenMapper->findByToken($token);

            if (!$tokenEntity) {
                $response = new TemplateResponse('digitalsignage', 'error', [
                    'message' => 'Invalid or expired token'
                ], 'blank');
                return $response;
            }

            $effectiveConfig = $this->displayConfigService->getEffectiveConfig($tokenEntity);
            $locale = (string)($effectiveConfig['locale'] ?? 'en');
            $lang = substr(strtolower(str_replace('_', '-', $locale)), 0, 2);
            $appLanguage = $this->resolveAppLanguage($lang, $locale);
            $colorPrimary = $effectiveConfig['colorPrimary'];
            $colorBg = $effectiveConfig['colorBg'];
            $colorText = $effectiveConfig['colorText'];
            $colorGradientStart = $effectiveConfig['colorGradientStart'];
            $colorGradientEnd = $effectiveConfig['colorGradientEnd'];
            $showTitlebar = $this->config->getAppValue('digitalsignage', 'show_titlebar', '1');
            $displayName = $effectiveConfig['displayName'];
            $showDisplayName = $effectiveConfig['showDisplayName'];
            $textSizes = $effectiveConfig['textSizes'] ?? TextSizeConfig::getConfiguredSizes($this->config);
            $textSizeCssVariables = $effectiveConfig['textSizeCssVariables'] ?? TextSizeConfig::toCssVariables($textSizes);
            $contentSplitRatio = (string)$effectiveConfig['contentSplitRatio'];
            $fullscreenSlideshow = $effectiveConfig['fullscreenSlideshow'] ? '1' : '0';
            $l10n = $this->l10nFactory->get('digitalsignage', $appLanguage, $locale);
            if ($showDisplayName === '' || $showDisplayName === null) {
                $showDisplayName = '1';
            }

            $response = new TemplateResponse(
                'digitalsignage',
                'public_display',
                [
                    'token' => $token,
                    'lang' => $lang,
                    'color_primary' => $colorPrimary,
                    'color_bg' => $colorBg,
                    'color_text' => $colorText,
                    'color_gradient_start' => $colorGradientStart,
                    'color_gradient_end' => $colorGradientEnd,
                    'show_titlebar' => $showTitlebar,
                    'display_name' => $displayName,
                    'show_display_name' => $showDisplayName,
                    'text_sizes' => $textSizes,
                    'text_size_css_variables' => $textSizeCssVariables,
                    'content_split_ratio' => $contentSplitRatio,
                    'fullscreen_slideshow' => $fullscreenSlideshow,
                    'fullscreen_title_enter' => $l10n->t('Fullscreen'),
                    'fullscreen_title_exit' => $l10n->t('Exit fullscreen')
                ],
                'blank'
            );

            // Set CSP to allow open-meteo API
            $policy = new ContentSecurityPolicy();
            $policy->addAllowedConnectDomain('https://api.open-meteo.com');
            $policy->addAllowedScriptDomain('https://cdnjs.cloudflare.com');
            $response->setContentSecurityPolicy($policy);

            return $response;
        } catch (\Exception $e) {
            return new TemplateResponse('digitalsignage', 'error', [
                'message' => $e->getMessage()
            ], 'blank');
        }
    }
}
