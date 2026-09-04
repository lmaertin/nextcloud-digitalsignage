<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Controller;

use OCA\DigitalSignage\Controller\PublicApiController;
use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\DisplayConfigService;
use OCA\DigitalSignage\Service\InstantMessageService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\Config\IUserConfig;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use PHPUnit\Framework\TestCase;

class PublicApiControllerTest extends TestCase {
    public function testGetMessagesRejectsInvalidToken(): void {
        $request = $this->createMock(IRequest::class);
        $tokenMapper = $this->createMock(TokenMapper::class);
        $tokenMapper->method('findByToken')->with('invalid-token')->willReturn(null);

        $controller = $this->createController($request, $tokenMapper, $this->createMock(InstantMessageService::class));
        $response = $controller->getMessages('invalid-token');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(403, $response->getStatus());
    }

    public function testGetMessagesUsesDisplayScopeAndCursor(): void {
        $request = $this->createMock(IRequest::class);
        $request->method('getParam')->with('since')->willReturn('cursor-1');

        $display = new Token();
        $display->setId(9);

        $tokenMapper = $this->createMock(TokenMapper::class);
        $tokenMapper->method('findByToken')->with('public-token')->willReturn($display);

        $instantMessageService = $this->createMock(InstantMessageService::class);
        $instantMessageService->expects($this->once())
            ->method('pollMessages')
            ->with(9, 'cursor-1')
            ->willReturn([
                'messages' => [[
                    'id' => 'next-message',
                    'message' => 'Reminder',
                    'duration' => 15,
                    'expiresAt' => time() + 15,
                ]],
                'nextSince' => 'next-message',
            ]);

        $controller = $this->createController($request, $tokenMapper, $instantMessageService);
        $response = $controller->getMessages('public-token');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());
        $this->assertCount(1, $response->getData()['messages']);
        $this->assertSame('next-message', $response->getData()['nextSince']);
    }

    private function createController(
        IRequest $request,
        TokenMapper $tokenMapper,
        InstantMessageService $instantMessageService
    ): PublicApiController {
        return new PublicApiController(
            'digitalsignage',
            $request,
            $this->createMock(IConfig::class),
            $this->createMock(IRootFolder::class),
            $this->createMock(ICalendarManager::class),
            $tokenMapper,
            $this->createMock(DisplayConfigService::class),
            $this->createMock(IFactory::class),
            $this->createMock(IAppManager::class),
            $this->createMock(IClientService::class),
            $this->createMock(IUserConfig::class),
            $this->createMock(IUserManager::class),
            $this->createMock(IURLGenerator::class),
            $instantMessageService
        );
    }
}
