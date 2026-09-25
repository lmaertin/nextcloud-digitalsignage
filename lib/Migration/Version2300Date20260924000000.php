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
        $lookups = [];
        $migrated = 0;

        foreach ($this->presetMapper->findAll() as $preset) {
            $values = json_decode($preset->getCalendarNames() ?: '[]', true);
            if (!is_array($values) || $values === []) {
                continue;
            }

            $userId = $preset->getUserId();
            if (!isset($lookups[$userId])) {
                try {
                    $calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId);
                } catch (\Exception $e) {
                    $calendars = [];
                }

                $keys = $names = [];
                foreach ($calendars as $calendar) {
                    $key = $calendar->getKey();
                    $keys[$key] = true;
                    $name = $calendar->getDisplayName();
                    $names[$name] = array_key_exists($name, $names) ? null : $key;
                }
                $lookups[$userId] = [$keys, $names];
            }

            [$keys, $names] = $lookups[$userId];
            $updated = array_map(static function ($value) use ($keys, $names) {
                if (!is_string($value) || isset($keys[$value])) {
                    return $value;
                }

                // Null marks a duplicate display name; never guess which calendar it meant.
                return $names[$value] ?? $value;
            }, $values);

            if ($updated !== $values) {
                $preset->setCalendarNames(json_encode($updated));
                $this->presetMapper->update($preset);
                $migrated++;
            }
        }

        if ($migrated > 0) {
            $output->info(sprintf('Migrated calendar selections in %d preset(s).', $migrated));
        }
    }
}
