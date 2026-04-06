<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\PresetService;

class TokenController extends Controller {
    private $tokenMapper;
    private $presetMapper;
    private $presetService;
    private $userId;

    public function __construct(
        string $AppName,
        IRequest $request,
        TokenMapper $tokenMapper,
        PresetMapper $presetMapper,
        PresetService $presetService,
        ?string $UserId
    ) {
        parent::__construct($AppName, $request);
        $this->tokenMapper = $tokenMapper;
        $this->presetMapper = $presetMapper;
        $this->presetService = $presetService;
        $this->userId = $UserId;
    }

    private function generateApiToken(): string {
        return bin2hex(random_bytes(32));
    }

    private function ensureDisplayState(Token $display): Token {
        $changed = false;

        if (!$display->getControlToken()) {
            $display->setControlToken($this->generateApiToken());
            $changed = true;
        }

        if (!$display->getRevision()) {
            $display->setRevision(1);
            $changed = true;
        }

        if (!$display->getUpdatedAt()) {
            $display->setUpdatedAt($display->getCreatedAt() ?: time());
            $changed = true;
        }

        if ($changed) {
            $display = $this->tokenMapper->update($display);
        }

        return $display;
    }

    /**
     * @NoAdminRequired
     */
    public function create(string $name): JSONResponse {
        try {
            $token = $this->generateApiToken();
            $controlToken = $this->generateApiToken();
            $defaultPreset = $this->presetService->ensureDefaultPreset((string)$this->userId);

            $tokenEntity = new Token();
            $tokenEntity->setToken($token);
            $tokenEntity->setControlToken($controlToken);
            $tokenEntity->setUserId($this->userId);
            $tokenEntity->setName($name);
            $tokenEntity->setActivePresetId($defaultPreset->getId());
            $tokenEntity->setRevision(1);
            $tokenEntity->setCreatedAt(time());
            $tokenEntity->setUpdatedAt(time());

            $this->tokenMapper->insert($tokenEntity);

            return new JSONResponse([
                'id' => $tokenEntity->getId(),
                'token' => $token,
                'controlToken' => $controlToken,
                'name' => $name,
                'url' => \OC::$server->getURLGenerator()->linkToRouteAbsolute('digitalsignage.public.display', ['token' => $token]),
                'controlUrl' => \OC::$server->getURLGenerator()->linkToRouteAbsolute('digitalsignage.control.activatePreset', ['controlToken' => $controlToken]),
                'activePresetId' => $defaultPreset->getId(),
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function list(): JSONResponse {
        try {
            $this->presetService->ensureDefaultPreset((string)$this->userId);
            $tokens = $this->tokenMapper->findByUserId($this->userId);

            $result = array_map(function($token) {
                $token = $this->ensureDisplayState($token);
                $presetName = null;
                if ($token->getActivePresetId() !== null) {
                    $preset = $this->presetMapper->findForUser($token->getActivePresetId(), $this->userId);
                    $presetName = $preset ? $preset->getName() : null;
                }

                return [
                    'id' => $token->getId(),
                    'token' => substr($token->getToken(), 0, 8) . '...',
                    'viewToken' => $token->getToken(),
                    'controlToken' => $token->getControlToken(),
                    'name' => $token->getName(),
                    'createdAt' => $token->getCreatedAt(),
                    'updatedAt' => $token->getUpdatedAt(),
                    'revision' => $token->getRevision(),
                    'activePresetId' => $token->getActivePresetId(),
                    'activePresetName' => $presetName,
                    'url' => \OC::$server->getURLGenerator()->linkToRouteAbsolute('digitalsignage.public.display', ['token' => $token->getToken()]),
                    'controlUrl' => \OC::$server->getURLGenerator()->linkToRouteAbsolute('digitalsignage.control.activatePreset', ['controlToken' => (string)$token->getControlToken()])
                ];
            }, $tokens);

            return new JSONResponse($result);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function activatePreset(int $id, int $presetId): JSONResponse {
        try {
            $display = $this->tokenMapper->find($id);
            if ($display->getUserId() !== $this->userId) {
                return new JSONResponse(['error' => 'Unauthorized'], 403);
            }

            $preset = $this->presetMapper->findForUser($presetId, $this->userId);
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
                'presetId' => $preset->getId(),
                'presetName' => $preset->getName(),
                'revision' => $display->getRevision(),
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function delete(int $id): JSONResponse {
        try {
            $token = $this->tokenMapper->find($id);

            if ($token->getUserId() !== $this->userId) {
                return new JSONResponse(['error' => 'Unauthorized'], 403);
            }

            $this->tokenMapper->delete($token);

            return new JSONResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }
}
