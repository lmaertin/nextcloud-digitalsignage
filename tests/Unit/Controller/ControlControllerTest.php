<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Tests\Unit\Controller;

use OCA\DigitalSignage\Controller\ControlController;
use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\InstantMessageService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

class ControlControllerTest extends TestCase {
    public function testSendMessageRejectsInvalidControlToken(): void {
        $request = $this->createMock(IRequest::class);
        $tokenMapper = $this->createMock(TokenMapper::class);
        $tokenMapper->method('findByControlToken')->with('invalid')->willReturn(null);

        $controller = new ControlController(
            'digitalsignage',
            $request,
            $tokenMapper,
            $this->createMock(PresetMapper::class),
            $this->createMock(InstantMessageService::class)
        );

        $response = $controller->sendMessage('invalid');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(403, $response->getStatus());
    }

    public function testSendMessageStoresDisplayScopedMessage(): void {
        $request = $this->createMock(IRequest::class);
        $request->method('getParam')->willReturnCallback(static function (string $key) {
            if ($key === 'message') {
                return 'Building closes at 18:00';
            }
            if ($key === 'duration') {
                return 20;
            }
            return null;
        });

        $display = new Token();
        $display->setId(42);

        $tokenMapper = $this->createMock(TokenMapper::class);
        $tokenMapper->method('findByControlToken')->with('valid-token')->willReturn($display);

        $instantMessageService = $this->createMock(InstantMessageService::class);
        $instantMessageService->expects($this->once())
            ->method('storeMessage')
            ->with(42, 'Building closes at 18:00', 20)
            ->willReturn([
                'id' => 'message-id',
                'message' => 'Building closes at 18:00',
                'duration' => 20,
                'expiresAt' => time() + 20,
            ]);

        $controller = new ControlController(
            'digitalsignage',
            $request,
            $tokenMapper,
            $this->createMock(PresetMapper::class),
            $instantMessageService
        );

        $response = $controller->sendMessage('valid-token');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());
        $this->assertSame(true, $response->getData()['success']);
        $this->assertSame(42, $response->getData()['displayId']);
    }
}
