<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Lukas Märtin <github@lukas-maertin.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DigitalSignage\Tests\Unit\Controller;

use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Controller\TokenController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

class TokenControllerTest extends TestCase {
    private TokenController $controller;
    private TokenMapper $mapper;
    private IRequest $request;

    protected function setUp(): void {
        parent::setUp();

        $this->request = $this->createMock(IRequest::class);
        $this->mapper = $this->createMock(TokenMapper::class);

        $this->controller = new TokenController(
            'digitalsignage',
            $this->request,
            $this->mapper,
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
        $token->setUserId('testUser');

        $this->mapper->method('findByUserId')->willReturn([$token]);

        $response = $this->controller->list();

        $this->assertInstanceOf(JSONResponse::class, $response);
    }
}
