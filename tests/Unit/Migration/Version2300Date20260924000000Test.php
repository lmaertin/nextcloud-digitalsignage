<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Migration;

use OCA\DigitalSignage\Db\Preset;
use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Migration\Version2300Date20260924000000;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

class Version2300Date20260924000000Test extends TestCase {
    public function testMigratesDisplayNameToUniqueCalendarKey(): void {
        $calendar = $this->createMock(ICalendar::class);
        $calendar->method('getKey')->willReturn('family-new');
        $calendar->method('getDisplayName')->willReturn('Family');

        $preset = new Preset();
        $preset->setId(1);
        $preset->setUserId('testUser');
        $preset->setCalendarNames(json_encode(['Family']));

        $presetMapper = $this->createMock(PresetMapper::class);
        $presetMapper->method('findAll')->willReturn([$preset]);
        $presetMapper->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Preset $updated): bool {
                return $updated->getCalendarNames() === json_encode(['family-new']);
            }));

        $calendarManager = $this->createMock(ICalendarManager::class);
        $calendarManager->method('getCalendarsForPrincipal')
            ->with('principals/users/testUser')
            ->willReturn([$calendar]);

        $migration = new Version2300Date20260924000000($presetMapper, $calendarManager);
        $migration->postSchemaChange($this->createMock(IOutput::class), static function () {
        }, []);
    }

    public function testLeavesAlreadyMigratedPresetsUntouched(): void {
        $calendar = $this->createMock(ICalendar::class);
        $calendar->method('getKey')->willReturn('family-new');
        $calendar->method('getDisplayName')->willReturn('Family');

        $preset = new Preset();
        $preset->setId(1);
        $preset->setUserId('testUser');
        $preset->setCalendarNames(json_encode(['family-new']));

        $presetMapper = $this->createMock(PresetMapper::class);
        $presetMapper->method('findAll')->willReturn([$preset]);
        $presetMapper->expects($this->never())->method('update');

        $calendarManager = $this->createMock(ICalendarManager::class);
        $calendarManager->method('getCalendarsForPrincipal')
            ->with('principals/users/testUser')
            ->willReturn([$calendar]);

        $migration = new Version2300Date20260924000000($presetMapper, $calendarManager);
        $migration->postSchemaChange($this->createMock(IOutput::class), static function () {
        }, []);
    }

    public function testKeepsUnknownCalendarReferenceUnchanged(): void {
        $preset = new Preset();
        $preset->setId(1);
        $preset->setUserId('testUser');
        $preset->setCalendarNames(json_encode(['Deleted Calendar']));

        $presetMapper = $this->createMock(PresetMapper::class);
        $presetMapper->method('findAll')->willReturn([$preset]);
        $presetMapper->expects($this->never())->method('update');

        $calendarManager = $this->createMock(ICalendarManager::class);
        $calendarManager->method('getCalendarsForPrincipal')
            ->with('principals/users/testUser')
            ->willReturn([]);

        $migration = new Version2300Date20260924000000($presetMapper, $calendarManager);
        $migration->postSchemaChange($this->createMock(IOutput::class), static function () {
        }, []);
    }
}
