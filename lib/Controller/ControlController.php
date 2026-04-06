<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\TokenMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class ControlController extends Controller {
    private TokenMapper $tokenMapper;
    private PresetMapper $presetMapper;

    public function __construct(
        string $appName,
        IRequest $request,
        TokenMapper $tokenMapper,
        PresetMapper $presetMapper
    ) {
        parent::__construct($appName, $request);
        $this->tokenMapper = $tokenMapper;
        $this->presetMapper = $presetMapper;
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
}
