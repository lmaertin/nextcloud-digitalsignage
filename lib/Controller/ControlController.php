<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\InstantMessageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class ControlController extends Controller {
    private TokenMapper $tokenMapper;
    private PresetMapper $presetMapper;
    private InstantMessageService $instantMessageService;

    public function __construct(
        string $appName,
        IRequest $request,
        TokenMapper $tokenMapper,
        PresetMapper $presetMapper,
        InstantMessageService $instantMessageService
    ) {
        parent::__construct($appName, $request);
        $this->tokenMapper = $tokenMapper;
        $this->presetMapper = $presetMapper;
        $this->instantMessageService = $instantMessageService;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function activatePreset(string $controlToken): JSONResponse {
        $display = $this->tokenMapper->findByControlToken($controlToken);
        if ($display === null) {
            return new JSONResponse(['error' => 'Invalid control token'], 403);
        }

        $presetId = $this->request->getParam('presetId');
        $presetName = $this->request->getParam('presetName');
        $preset = null;

        if ($presetId !== null && $presetId !== '') {
            $preset = $this->presetMapper->findForUser((int)$presetId, $display->getUserId());
        } elseif (is_string($presetName) && trim($presetName) !== '') {
            $preset = $this->presetMapper->findByNameForUser(trim($presetName), $display->getUserId());
        }

        if ($preset === null) {
            return new JSONResponse(['error' => 'Preset not found'], 404);
        }

        $display->setActivePresetId($preset->getId());
        $display->setRevision(max(1, $display->getRevision() + 1));
        $display->setUpdatedAt(time());
        $this->tokenMapper->update($display);

        return new JSONResponse([
            'success' => true,
            'displayId' => $display->getId(),
            'displayName' => $display->getName(),
            'presetId' => $preset->getId(),
            'presetName' => $preset->getName(),
            'revision' => $display->getRevision(),
        ]);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function sendMessage(string $controlToken): JSONResponse {
        $display = $this->tokenMapper->findByControlToken($controlToken);
        if ($display === null) {
            return new JSONResponse(['error' => 'Invalid control token'], 403);
        }

        $message = $this->request->getParam('message');
        $durationParam = $this->request->getParam('duration');
        $duration = $durationParam === null || $durationParam === '' ? 15 : (int)$durationParam;

        if (!is_string($message)) {
            return new JSONResponse(['error' => 'Message must be a string'], 400);
        }

        try {
            $storedMessage = $this->instantMessageService->storeMessage((int)$display->getId(), $message, $duration);
        } catch (\InvalidArgumentException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 400);
        }

        return new JSONResponse([
            'success' => true,
            'displayId' => $display->getId(),
            'messageId' => $storedMessage['id'],
            'duration' => $storedMessage['duration'],
            'expiresAt' => $storedMessage['expiresAt'],
        ]);
    }
}
