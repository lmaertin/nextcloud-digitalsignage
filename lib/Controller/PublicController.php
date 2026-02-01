<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\PublicShareController;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;
use OCP\ISession;
use OCA\DigitalSignage\Db\TokenMapper;

use OCP\IConfig;

class PublicController extends PublicShareController {
    private $tokenMapper;
    private $config;

    public function __construct(
        string $AppName,
        IRequest $request,
        ISession $session,
        TokenMapper $tokenMapper,
        IConfig $config
    ) {
        parent::__construct($AppName, $request, $session);
        $this->tokenMapper = $tokenMapper;
        $this->config = $config;
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

            // Get locale from config
            $locale = $this->config->getAppValue('digitalsignage', 'locale', 'de-DE');
            $lang = substr($locale, 0, 2); // Extract language code (de, en, fr, etc.)

            $colorPrimary = $this->config->getAppValue('digitalsignage', 'color_primary', '#0066cc');
            $colorBg = $this->config->getAppValue('digitalsignage', 'color_bg', '#f8f9fa');
            $colorText = $this->config->getAppValue('digitalsignage', 'color_text', '#2c3e50');
            $colorGradientStart = $this->config->getAppValue('digitalsignage', 'color_gradient_start', '#0066cc');
            $colorGradientEnd = $this->config->getAppValue('digitalsignage', 'color_gradient_end', '#3399ff');
            $showTitlebar = $this->config->getAppValue('digitalsignage', 'show_titlebar', '1');
            $displayName = $this->config->getAppValue('digitalsignage', 'display_name', '');
            $showDisplayName = $this->config->getAppValue('digitalsignage', 'show_display_name', '1');
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
                    'show_display_name' => $showDisplayName
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
