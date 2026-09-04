<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Service;

use OCA\DigitalSignage\Service\InstantMessageService;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\TestCase;

class InstantMessageServiceTest extends TestCase {
    public function testStoreMessageUsesDisplayScopedCacheKeyAndSafetyTtl(): void {
        $cache = $this->createMock(ICache::class);
        $cache->expects($this->once())
            ->method('set')
            ->with(
                'display-7',
                $this->callback(static function ($value): bool {
                    return is_array($value)
                        && isset($value['id'], $value['message'], $value['duration'], $value['expiresAt'])
                        && $value['message'] === 'Maintenance starts now'
                        && $value['duration'] === 15;
                }),
                75
            );

        $cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheFactory->method('create')->with('digitalsignage-instant-messages')->willReturn($cache);

        $service = new InstantMessageService($cacheFactory);
        $result = $service->storeMessage(7, 'Maintenance starts now', 15);

        $this->assertSame('Maintenance starts now', $result['message']);
        $this->assertSame(15, $result['duration']);
        $this->assertIsString($result['id']);
        $this->assertGreaterThanOrEqual(time() + 14, $result['expiresAt']);
    }

    public function testStoreMessageRejectsHtml(): void {
        $cache = $this->createMock(ICache::class);
        $cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheFactory->method('create')->willReturn($cache);

        $service = new InstantMessageService($cacheFactory);

        $this->expectException(\InvalidArgumentException::class);
        $service->storeMessage(3, '<b>Unsafe</b>', 15);
    }

    public function testStoreMessageRejectsTooShortDuration(): void {
        $cache = $this->createMock(ICache::class);
        $cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheFactory->method('create')->willReturn($cache);

        $service = new InstantMessageService($cacheFactory);

        $this->expectException(\InvalidArgumentException::class);
        $service->storeMessage(3, 'Valid text', 1);
    }

    public function testPollMessagesSkipsAlreadySeenMessage(): void {
        $cache = $this->createMock(ICache::class);
        $cache->method('get')->with('display-2')->willReturn([
            'id' => 'abc123',
            'message' => 'Test',
            'duration' => 15,
            'expiresAt' => time() + 30,
        ]);

        $cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheFactory->method('create')->willReturn($cache);

        $service = new InstantMessageService($cacheFactory);
        $result = $service->pollMessages(2, 'abc123');

        $this->assertSame([], $result['messages']);
        $this->assertSame('abc123', $result['nextSince']);
    }
}
