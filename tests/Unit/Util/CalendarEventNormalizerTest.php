<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Util;

use OCA\DigitalSignage\Util\CalendarEventNormalizer;
use PHPUnit\Framework\TestCase;

class CalendarEventNormalizerTest extends TestCase {
    public function testTimedEventKeepsWallClockTimeWithExplicitOffset(): void {
        $dateTime = new \DateTime('2026-09-03 09:00:00', new \DateTimeZone('America/New_York'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [json_decode(json_encode($dateTime), true), ['VALUE' => 'DATE-TIME']],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-03T09:00:00-04:00', $result['objects'][0]['DTSTART'][0]['date']);
    }

    public function testAllDayEventKeepsPlainDateWithoutTimezoneShift(): void {
        $dateTime = new \DateTime('2026-09-03', new \DateTimeZone('UTC'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [json_decode(json_encode($dateTime), true), ['VALUE' => 'DATE']],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-03', $result['objects'][0]['DTSTART'][0]['date']);
    }

    public function testMissingObjectsAreReturnedUnchanged(): void {
        $eventData = ['objects' => []];

        $this->assertSame($eventData, CalendarEventNormalizer::normalize($eventData));
    }
}
