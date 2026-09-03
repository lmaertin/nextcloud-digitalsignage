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
        foreach (['DTSTART', 'DTEND'] as $property) {
            $dateTime = $eventData['objects'][0][$property][0] ?? null;
            if (!$dateTime instanceof \DateTimeInterface) {
                continue;
            }

            $isDateOnly = ($eventData['objects'][0][$property][1]['VALUE'] ?? null) === 'DATE';
            $eventData['objects'][0][$property][0] = [
                'date' => $dateTime->format($isDateOnly ? 'Y-m-d' : DATE_ATOM),
            ];
        }

        return $eventData;
    }
}
