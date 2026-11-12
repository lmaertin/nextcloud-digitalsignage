<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Util;

use OCA\DigitalSignage\Util\Slug;
use PHPUnit\Framework\TestCase;

class SlugTest extends TestCase {
    public function testNormalizesCaseAndWhitespace(): void {
        $this->assertSame('lobby-tv-2', Slug::normalize('  Lobby-TV-2 '));
    }

    public function testEmptyMeansNoSlug(): void {
        $this->assertNull(Slug::normalize(''));
        $this->assertNull(Slug::normalize('   '));
        $this->assertNull(Slug::normalize(null));
    }

    /**
     * @dataProvider invalidSlugs
     */
    public function testRejectsInvalidSlugs(string $slug): void {
        $this->assertFalse(Slug::normalize($slug));
    }

    public function invalidSlugs(): array {
        return [
            'space' => ['lobby tv'],
            'slash' => ['lobby/tv'],
            'leading hyphen' => ['-lobby'],
            'trailing hyphen' => ['lobby-'],
            'double hyphen' => ['lobby--tv'],
            'non-ascii' => ['resepsjon-ø'],
            'too long' => [str_repeat('a', Slug::MAX_LENGTH + 1)],
        ];
    }
}
