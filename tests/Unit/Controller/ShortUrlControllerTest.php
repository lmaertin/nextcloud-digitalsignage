<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Controller;

use OCA\DigitalSignage\Controller\ShortUrlController;
use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\TokenMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

class ShortUrlControllerTest extends TestCase {
    private TokenMapper $mapper;
    private IURLGenerator $urlGenerator;
    private ShortUrlController $controller;

    protected function setUp(): void {
        parent::setUp();

        $this->mapper = $this->createMock(TokenMapper::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->controller = new ShortUrlController(
            'digitalsignage',
            $this->createMock(IRequest::class),
            $this->mapper,
            $this->urlGenerator
        );
    }

    public function testRedirectsToTokenUrl(): void {
        $display = new Token();
        $display->setToken('abc123');

        $this->mapper->expects($this->once())->method('findBySlug')->with('lobby')->willReturn($display);
        $this->urlGenerator->method('linkToRoute')
            ->with('digitalsignage.public.display', ['token' => 'abc123'])
            ->willReturn('/apps/digitalsignage/public/abc123');

        $response = $this->controller->resolve('Lobby');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/apps/digitalsignage/public/abc123', $response->getRedirectURL());
    }

    public function testUnknownSlugIsThrottled404(): void {
        $this->mapper->method('findBySlug')->willReturn(null);

        $response = $this->controller->resolve('nope');

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertTrue($response->isThrottled());
    }

    public function testInvalidSlugSkipsLookup(): void {
        $this->mapper->expects($this->never())->method('findBySlug');

        $response = $this->controller->resolve('not a slug');

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
    }
}
