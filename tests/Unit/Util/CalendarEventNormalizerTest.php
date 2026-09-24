<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Util;

use OCA\DigitalSignage\Util\CalendarEventNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Sabre\VObject\Parameter exposes its value only via __toString(), it is never
 * a plain string. Comparing it to a string with === (as the original code did)
 * always evaluates to false. This stand-in reproduces that behavior so tests
 * catch a regression to strict '=== ' comparisons instead of a string cast.
 */
class StringableParameter {
    public function __construct(private string $value) {
    }

    public function __toString(): string {
        return $this->value;
    }
}

class CalendarEventNormalizerTest extends TestCase {
    public function testTimedEventKeepsWallClockTimeWithExplicitOffset(): void {
        $dateTime = new \DateTimeImmutable('2026-09-03 09:00:00', new \DateTimeZone('America/New_York'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [$dateTime, ['VALUE' => 'DATE-TIME']],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-03T09:00:00-04:00', $result['objects'][0]['DTSTART'][0]['date']);
    }

    public function testAllDayEventKeepsPlainDateWithoutTimezoneShift(): void {
        $dateTime = new \DateTimeImmutable('2026-09-03', new \DateTimeZone('UTC'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [$dateTime, ['VALUE' => 'DATE']],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-03', $result['objects'][0]['DTSTART'][0]['date']);
    }

    /**
     * Regression test for https://github.com/lmaertin/nextcloud-digitalsignage/issues/20:
     * all-day events appeared one day earlier for users whose timezone is behind UTC.
     * A VALUE=DATE property must never be converted to another timezone before formatting,
     * regardless of which timezone the underlying DateTimeInterface instance carries.
     */
    public function testAllDayEventInTimezoneBehindUtcKeepsCalendarDateStable(): void {
        $dateTime = new \DateTimeImmutable('2026-09-21', new \DateTimeZone('America/New_York'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [$dateTime, ['VALUE' => 'DATE']],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-21', $result['objects'][0]['DTSTART'][0]['date']);
    }

    public function testAllDayEventInTimezoneAheadOfUtcKeepsCalendarDateStable(): void {
        $dateTime = new \DateTimeImmutable('2026-09-21', new \DateTimeZone('Pacific/Kiritimati'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [$dateTime, ['VALUE' => 'DATE']],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-21', $result['objects'][0]['DTSTART'][0]['date']);
    }

    /**
     * Regression test for the actual root cause of issue #20: ICalendar::search() returns
     * Sabre\VObject\Parameter objects for VALUE, not plain strings. A strict '===' comparison
     * against the string 'DATE' always fails for these objects, silently falling back to a full
     * timestamp (with a UTC offset) that browsers behind UTC render one calendar day earlier.
     */
    public function testAllDayEventWithSabreParameterObjectKeepsPlainDate(): void {
        $dateTime = new \DateTimeImmutable('2026-09-21', new \DateTimeZone('UTC'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [$dateTime, ['VALUE' => new StringableParameter('DATE')]],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-21', $result['objects'][0]['DTSTART'][0]['date']);
    }

    public function testDurationIsNormalizedToEndDateTime(): void {
        $startDate = new \DateTimeImmutable('2026-09-03 09:00:00', new \DateTimeZone('Europe/Berlin'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [$startDate, ['VALUE' => 'DATE-TIME']],
                'DURATION' => ['PT2H'],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-03T11:00:00+02:00', $result['objects'][0]['DTEND'][0]['date']);
    }

    public function testAllDayDurationIsNormalizedToEndDate(): void {
        $startDate = new \DateTimeImmutable('2026-09-03', new \DateTimeZone('UTC'));
        $eventData = [
            'objects' => [[
                'DTSTART' => [$startDate, ['VALUE' => 'DATE']],
                'DURATION' => [new \DateInterval('P2D')],
            ]],
        ];

        $result = CalendarEventNormalizer::normalize($eventData);

        $this->assertSame('2026-09-05', $result['objects'][0]['DTEND'][0]['date']);
    }

    public function testMissingObjectsAreReturnedUnchanged(): void {
        $eventData = ['objects' => []];

        $this->assertSame($eventData, CalendarEventNormalizer::normalize($eventData));
    }
}
