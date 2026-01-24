<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Lukas Märtin <github@lukas-maertin.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DigitalSignage\Tests\Unit\Db;

use OCA\DigitalSignage\Db\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase {
    private Token $token;

    protected function setUp(): void {
        parent::setUp();
        $this->token = new Token();
    }

    public function testTokenCanBeCreated(): void {
        $this->token->setName('Test Display');
        $this->token->setToken('test-token-123');
        $this->token->setUserId('admin');

        $this->assertEquals('Test Display', $this->token->getName());
        $this->assertEquals('test-token-123', $this->token->getToken());
        $this->assertEquals('admin', $this->token->getUserId());
    }

    public function testTokenCreatedAtIsSet(): void {
        $this->token->setCreatedAt(time());

        $this->assertIsInt($this->token->getCreatedAt());
    }

    public function testTokenHasJsonSerializable(): void {
        $this->token->setId(1);
        $this->token->setName('Test Display');
        $this->token->setToken('test-token-123');
        $this->token->setUserId('admin');
        $this->token->setCreatedAt(time());

        $json = json_encode($this->token);
        $this->assertIsString($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals(1, $data['id']);
    }
}
