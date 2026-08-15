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
}
