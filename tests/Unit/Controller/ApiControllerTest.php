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
}
