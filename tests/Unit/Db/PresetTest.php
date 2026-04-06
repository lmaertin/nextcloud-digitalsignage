<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Db;

use OCA\DigitalSignage\Db\Preset;
use PHPUnit\Framework\TestCase;

class PresetTest extends TestCase {
    public function testPresetCanBeCreated(): void {
        $preset = new Preset();
        $preset->setId(1);
        $preset->setUserId('admin');
        $preset->setName('Reception');
        $preset->setImageFolder('/Fotos/Reception');
        $preset->setImageFitMode('cover');
        $preset->setImageOrderMode('filename');
        $preset->setFullscreenSlideshow('1');
        $preset->setSlideInterval(12);
        $preset->setCreatedAt(time());
        $preset->setUpdatedAt(time());

        $this->assertSame('Reception', $preset->getName());
        $this->assertSame('/Fotos/Reception', $preset->getImageFolder());
        $this->assertSame('cover', $preset->getImageFitMode());
        $this->assertSame('filename', $preset->getImageOrderMode());
        $this->assertSame('1', $preset->getFullscreenSlideshow());
        $this->assertSame(12, $preset->getSlideInterval());
    }
}
