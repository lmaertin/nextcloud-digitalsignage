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
        $event = $eventData['objects'][0] ?? null;
        if (is_array($event) && !isset($event['DTEND'])) {
            $startDate = $event['DTSTART'][0] ?? null;
            $durationValue = $event['DURATION'][0] ?? null;
            $duration = self::toDateInterval($durationValue);

            if ($startDate instanceof \DateTimeInterface && $duration !== null) {
                $startDateTime = new \DateTimeImmutable(
                    $startDate->format('Y-m-d H:i:s.u'),
                    $startDate->getTimezone()
                );
                $eventData['objects'][0]['DTEND'] = [
                    $startDateTime->add($duration),
                    [
                        'VALUE' => ($event['DTSTART'][1]['VALUE'] ?? null) === 'DATE'
                            ? 'DATE'
                            : 'DATE-TIME',
                    ],
                ];
            }
        }

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

    private static function toDateInterval($durationValue): ?\DateInterval {
        if ($durationValue instanceof \DateInterval) {
            return $durationValue;
        }

        if (!is_string($durationValue)) {
            return null;
        }

        try {
            return new \DateInterval($durationValue);
        } catch (\Exception $exception) {
            return null;
        }
    }
}
