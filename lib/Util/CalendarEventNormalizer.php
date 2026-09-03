<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Util;

/**
 * The frontend has no access to the IANA timezone name embedded in the
 * DTSTART/DTEND DateTime payloads returned by ICalendar::search(), so it
 * previously misinterpreted the naive "date" string as browser-local time.
 * This rewrites both properties to unambiguous ISO 8601 instants beforehand.
 */
class CalendarEventNormalizer {
    public static function normalize(array $eventData): array {
        if (!isset($eventData['objects'][0]) || !is_array($eventData['objects'][0])) {
            return $eventData;
        }

        foreach (['DTSTART', 'DTEND'] as $property) {
            if (!isset($eventData['objects'][0][$property][0]['date'])) {
                continue;
            }

            $isDateOnly = ($eventData['objects'][0][$property][1]['VALUE'] ?? null) === 'DATE';
            $eventData['objects'][0][$property][0]['date'] = self::toIsoString(
                $eventData['objects'][0][$property][0],
                $isDateOnly
            );
        }

        return $eventData;
    }

    private static function toIsoString(array $dateTimeData, bool $isDateOnly): string {
        if ($isDateOnly) {
            return substr($dateTimeData['date'], 0, 10);
        }

        try {
            $timezone = new \DateTimeZone($dateTimeData['timezone'] ?? 'UTC');
            $dateTime = new \DateTimeImmutable($dateTimeData['date'], $timezone);
            return $dateTime->format(\DateTimeInterface::ATOM);
        } catch (\Exception $e) {
            return $dateTimeData['date'];
        }
    }
}
