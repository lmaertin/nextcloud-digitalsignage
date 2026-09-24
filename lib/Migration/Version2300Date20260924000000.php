<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCA\DigitalSignage\Db\PresetMapper;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migrates preset calendar selections from the (potentially ambiguous)
 * calendar display name to the calendar's unique key, see issue #18.
 */
class Version2300Date20260924000000 extends SimpleMigrationStep {
    private PresetMapper $presetMapper;
    private ICalendarManager $calendarManager;

    public function __construct(PresetMapper $presetMapper, ICalendarManager $calendarManager) {
        $this->presetMapper = $presetMapper;
        $this->calendarManager = $calendarManager;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $presets = $this->presetMapper->findAll();
        $calendarsByUser = [];
        $migratedCount = 0;

        foreach ($presets as $preset) {
            $calendarNames = json_decode($preset->getCalendarNames() ?: '[]', true);
            if (!is_array($calendarNames) || $calendarNames === []) {
                continue;
            }

            $userId = $preset->getUserId();
            if (!array_key_exists($userId, $calendarsByUser)) {
                try {
                    $calendarsByUser[$userId] = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId);
                } catch (\Exception $e) {
                    $calendarsByUser[$userId] = [];
                }
            }
            $calendars = $calendarsByUser[$userId];

            $changed = false;
            $migratedNames = [];
            foreach ($calendarNames as $storedValue) {
                if (!is_string($storedValue)) {
                    $migratedNames[] = $storedValue;
                    continue;
                }

                $migratedNames[] = $this->resolveCalendarKey($storedValue, $calendars, $changed);
            }

            if ($changed) {
                $preset->setCalendarNames(json_encode($migratedNames));
                $this->presetMapper->update($preset);
                $migratedCount++;
            }
        }

        if ($migratedCount > 0) {
            $output->info(sprintf('Migrated calendar selection to unique keys for %d preset(s).', $migratedCount));
        }
    }

    private function resolveCalendarKey(string $storedValue, array $calendars, bool &$changed): string {
        foreach ($calendars as $calendar) {
            if ($calendar->getKey() === $storedValue) {
                // Already a unique key, nothing to migrate.
                return $storedValue;
            }
        }

        foreach ($calendars as $calendar) {
            if ($calendar->getDisplayName() === $storedValue) {
                $changed = true;
                return $calendar->getKey();
            }
        }

        // No matching calendar found (e.g. it was deleted); keep the stored value as-is.
        return $storedValue;
    }
}
