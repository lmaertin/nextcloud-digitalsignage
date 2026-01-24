<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Lukas Märtin <github@lukas-maertin.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DigitalSignage\Tests\Unit\Util;

use OCA\DigitalSignage\Util\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase {
    private Translator $translator;

    protected function setUp(): void {
        parent::setUp();
        $this->translator = new Translator();
    }

    public function testTranslatorCanBeCreated(): void {
        $this->assertInstanceOf(Translator::class, $this->translator);
    }

    public function testTranslationKeyExists(): void {
        // Test that translation method exists
        $this->assertTrue(method_exists($this->translator, 't'));
    }

    public function testTranslationReturnsString(): void {
        // Basic test for translation functionality
        $result = $this->translator->t('digitalsignage', 'Display Name');
        $this->assertIsString($result);
    }
}
