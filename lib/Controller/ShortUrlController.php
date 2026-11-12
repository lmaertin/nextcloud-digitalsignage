<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Util\Slug;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * Resolves a display's short URL slug to its full public token URL.
 */
class ShortUrlController extends Controller {
    private TokenMapper $tokenMapper;
    private IURLGenerator $urlGenerator;

    public function __construct(string $AppName, IRequest $request, TokenMapper $tokenMapper, IURLGenerator $urlGenerator) {
        parent::__construct($AppName, $request);
        $this->tokenMapper = $tokenMapper;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @BruteForceProtection(action=digitalsignage_short_url)
     */
    public function resolve(string $slug): Response {
        $normalized = Slug::normalize($slug);
        $display = is_string($normalized) ? $this->tokenMapper->findBySlug($normalized) : null;

        if ($display === null) {
            $response = new TemplateResponse('digitalsignage', 'error', [
                'message' => 'Display not found',
            ], 'blank');
            $response->setStatus(Http::STATUS_NOT_FOUND);
            $response->throttle(['slug' => $slug]);
            return $response;
        }

        return new RedirectResponse(
            $this->urlGenerator->linkToRoute('digitalsignage.public.display', ['token' => $display->getToken()])
        );
    }
}
