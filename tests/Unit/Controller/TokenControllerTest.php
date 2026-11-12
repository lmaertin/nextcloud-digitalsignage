<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Lukas Märtin <github@lukas-maertin.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DigitalSignage\Tests\Unit\Controller;

use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Controller\TokenController;
use OCA\DigitalSignage\Service\PresetService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

class TokenControllerTest extends TestCase {
    private TokenController $controller;
    private TokenMapper $mapper;
    private PresetMapper $presetMapper;
    private PresetService $presetService;
    private IRequest $request;
    private IURLGenerator $urlGenerator;

    protected function setUp(): void {
        parent::setUp();

        $this->request = $this->createMock(IRequest::class);
        $this->mapper = $this->createMock(TokenMapper::class);
        $this->presetMapper = $this->createMock(PresetMapper::class);
        $this->presetService = $this->createMock(PresetService::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->urlGenerator->method('linkToRouteAbsolute')->willReturn('https://example.test/display');

        $this->presetService->method('ensureDefaultPreset');
        $this->presetMapper->method('findForUser')->willReturn(null);

        $this->controller = new TokenController(
            'digitalsignage',
            $this->request,
            $this->mapper,
            $this->presetMapper,
            $this->presetService,
            $this->urlGenerator,
            'testUser'
        );
    }

    public function testTokenControllerCanBeCreated(): void {
        $this->assertInstanceOf(TokenController::class, $this->controller);
    }

    public function testListReturnsTokens(): void {
        $token = new Token();
        $token->setId(1);
        $token->setName('Test Display');
        $token->setToken('test-token');
        $token->setControlToken('test-control-token');
        $token->setUserId('testUser');
        $token->setRevision(1);
        $token->setCreatedAt(time());
        $token->setUpdatedAt(time());

        $this->mapper->method('findByUserId')->willReturn([$token]);
        $this->mapper->method('update')->willReturn($token);

        $response = $this->controller->list();

        $this->assertInstanceOf(JSONResponse::class, $response);
    }

    private function ownedDisplay(int $id, ?string $slug = null): Token {
        $display = new Token();
        $display->setId($id);
        $display->setUserId('testUser');
        $display->setName('Lobby');
        $display->setToken('view-token');
        $display->setRevision(1);
        $display->setSlug($slug);
        return $display;
    }

    public function testUpdateSetsNormalizedSlug(): void {
        $display = $this->ownedDisplay(1);
        $this->mapper->method('find')->willReturn($display);
        $this->mapper->method('findBySlug')->with('lobby')->willReturn(null);
        $this->mapper->expects($this->once())->method('update')->willReturnArgument(0);

        $response = $this->controller->update(1, null, '', null, null, null, ' Lobby ');

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('lobby', $display->getSlug());
    }

    public function testUpdateRejectsSlugUsedByAnotherDisplay(): void {
        $this->mapper->method('find')->willReturn($this->ownedDisplay(1));
        $this->mapper->method('findBySlug')->willReturn($this->ownedDisplay(2, 'lobby'));
        $this->mapper->expects($this->never())->method('update');

        $response = $this->controller->update(1, null, '', null, null, null, 'lobby');

        $this->assertSame(409, $response->getStatus());
    }

    public function testUpdateRejectsInvalidSlug(): void {
        $this->mapper->method('find')->willReturn($this->ownedDisplay(1));
        $this->mapper->expects($this->never())->method('update');

        $response = $this->controller->update(1, null, '', null, null, null, 'lobby tv');

        $this->assertSame(400, $response->getStatus());
    }

    public function testUpdateWithEmptySlugRemovesIt(): void {
        $display = $this->ownedDisplay(1, 'lobby');
        $this->mapper->method('find')->willReturn($display);
        $this->mapper->method('update')->willReturnArgument(0);

        $this->controller->update(1, null, '', null, null, null, '');

        $this->assertNull($display->getSlug());
    }

    public function testUpdateWithoutSlugKeepsIt(): void {
        $display = $this->ownedDisplay(1, 'lobby');
        $this->mapper->method('find')->willReturn($display);
        $this->mapper->method('update')->willReturnArgument(0);

        $this->controller->update(1, 'New name');

        $this->assertSame('lobby', $display->getSlug());
    }
}
