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

class PublicController extends PublicShareController {
    private $tokenMapper;

    public function __construct(
        string $AppName,
        IRequest $request,
        ISession $session,
        TokenMapper $tokenMapper
    ) {
        parent::__construct($AppName, $request, $session);
        $this->tokenMapper = $tokenMapper;
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

            $response = new TemplateResponse(
                'digitalsignage',
                'public_display',
                [
                    'token' => $token,
                    'lang' => $lang
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
