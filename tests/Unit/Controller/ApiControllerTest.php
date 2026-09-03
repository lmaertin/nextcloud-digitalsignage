<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Controller;

use OCA\DigitalSignage\Controller\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\Calendar\ICalendar;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

class ApiControllerTest extends TestCase {
    public function testGetCalendarUsesTimerangeForRecurringEvents(): void {
        $calendar = $this->createMock(ICalendar::class);
        $calendar->method('getDisplayName')->willReturn('Team Calendar');
        $calendar->method('getKey')->willReturn('team-calendar');
        $calendar->expects($this->once())
            ->method('search')
            ->with(
                '',
                [],
                $this->callback(static function (array $options): bool {
                    return isset($options['timerange']['start'], $options['timerange']['end'])
                        && $options['timerange']['start'] instanceof \DateTimeInterface
                        && $options['timerange']['end'] instanceof \DateTimeInterface;
                }),
                null,
                null
            )
            ->willReturn([]);

        $calendarManager = $this->createMock(ICalendarManager::class);
        $calendarManager->method('getCalendarsForPrincipal')
            ->with('principals/users/testUser')
            ->willReturn([$calendar]);

        $request = $this->createMock(IRequest::class);
        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')
            ->willReturnMap([
                ['digitalsignage', 'calendar_name', '', 'Team Calendar'],
            ]);

        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);

        $controller = new ApiController(
            'digitalsignage',
            $request,
            $config,
            $rootFolder,
            $calendarManager,
            $userSession,
            'testUser'
        );

        $response = $controller->getCalendar();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame('Team Calendar', $response->getData()['calendarName']);
    }

    public function testGetCalendarReturnsAllExpandedOccurrencesOfARecurringEvent(): void {
        $calendar = $this->createMock(ICalendar::class);
        $calendar->method('getDisplayName')->willReturn('Team Calendar');
        $calendar->method('getKey')->willReturn('team-calendar');

        // Simulates three expanded occurrences of a weekly recurring event,
        // as returned by ICalendar::search() with a timerange option.
        $occurrences = [];
        foreach (['2026-09-06', '2026-09-13', '2026-09-20'] as $day) {
            $start = new \DateTimeImmutable($day . ' 09:00:00', new \DateTimeZone('Europe/Berlin'));
            $end = new \DateTimeImmutable($day . ' 10:00:00', new \DateTimeZone('Europe/Berlin'));
            $occurrences[] = [
                'objects' => [[
                    'SUMMARY' => ['Weekly Standup'],
                    'DTSTART' => [$start, ['VALUE' => 'DATE-TIME']],
                    'DTEND' => [$end, ['VALUE' => 'DATE-TIME']],
                ]],
            ];
        }

        $calendar->method('search')->willReturn($occurrences);

        $calendarManager = $this->createMock(ICalendarManager::class);
        $calendarManager->method('getCalendarsForPrincipal')
            ->with('principals/users/testUser')
            ->willReturn([$calendar]);

        $request = $this->createMock(IRequest::class);
        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')
            ->willReturnMap([
                ['digitalsignage', 'calendar_name', '', 'Team Calendar'],
            ]);

        $rootFolder = $this->createMock(IRootFolder::class);
        $userSession = $this->createMock(IUserSession::class);

        $controller = new ApiController(
            'digitalsignage',
            $request,
            $config,
            $rootFolder,
            $calendarManager,
            $userSession,
            'testUser'
        );

        $response = $controller->getCalendar();
        $events = $response->getData()['calendar'];

        $this->assertCount(3, $events);
        $this->assertSame('2026-09-06T09:00:00+02:00', $events[0]['objects'][0]['DTSTART'][0]['date']);
        $this->assertSame('2026-09-13T09:00:00+02:00', $events[1]['objects'][0]['DTSTART'][0]['date']);
        $this->assertSame('2026-09-20T09:00:00+02:00', $events[2]['objects'][0]['DTSTART'][0]['date']);
    }
}
