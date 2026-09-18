<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\Token;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\PresetService;
use OCP\IURLGenerator;

class TokenController extends Controller {
    private $tokenMapper;
    private $presetMapper;
    private $presetService;
    private $urlGenerator;
    private $userId;

    public function __construct(
        string $AppName,
        IRequest $request,
        TokenMapper $tokenMapper,
        PresetMapper $presetMapper,
        PresetService $presetService,
        IURLGenerator $urlGenerator,
        ?string $userId
    ) {
        parent::__construct($AppName, $request);
        $this->tokenMapper = $tokenMapper;
        $this->presetMapper = $presetMapper;
        $this->presetService = $presetService;
        $this->urlGenerator = $urlGenerator;
        $this->userId = $userId;
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
    public function create(
        string $name,
        string $time_zone = '',
        ?float $weather_latitude = null,
        ?float $weather_longitude = null,
        ?int $active_preset_id = null
    ): JSONResponse {
        try {
            $token = $this->generateApiToken();
            $controlToken = $this->generateApiToken();
            $defaultPreset = $this->presetService->ensureDefaultPreset((string)$this->userId);

            $normalizedTimeZone = trim($time_zone);
            if ($normalizedTimeZone !== '' && !in_array($normalizedTimeZone, \DateTimeZone::listIdentifiers(), true)) {
                return new JSONResponse(['error' => 'Invalid timezone'], 400);
            }
            if ($weather_latitude !== null && ($weather_latitude < -90 || $weather_latitude > 90)) {
                return new JSONResponse(['error' => 'Invalid weather latitude'], 400);
            }
            if ($weather_longitude !== null && ($weather_longitude < -180 || $weather_longitude > 180)) {
                return new JSONResponse(['error' => 'Invalid weather longitude'], 400);
            }
            if ($active_preset_id !== null && $this->presetMapper->findForUser($active_preset_id, (string)$this->userId) === null) {
                return new JSONResponse(['error' => 'Preset not found'], 404);
            }

            $tokenEntity = new Token();
            $tokenEntity->setToken($token);
            $tokenEntity->setControlToken($controlToken);
            $tokenEntity->setUserId($this->userId);
            $tokenEntity->setName($name);
            $tokenEntity->setActivePresetId($active_preset_id ?? $defaultPreset->getId());
            $tokenEntity->setTimeZone($normalizedTimeZone !== '' ? $normalizedTimeZone : null);
            $tokenEntity->setWeatherLatitude($weather_latitude);
            $tokenEntity->setWeatherLongitude($weather_longitude);
            $tokenEntity->setRevision(1);
            $tokenEntity->setCreatedAt(time());
            $tokenEntity->setUpdatedAt(time());

            $this->tokenMapper->insert($tokenEntity);

            return new JSONResponse([
                'id' => $tokenEntity->getId(),
                'token' => $token,
                'controlToken' => $controlToken,
                'name' => $name,
                'url' => $this->urlGenerator->linkToRouteAbsolute('digitalsignage.public.display', ['token' => $token]),
                'controlUrl' => $this->urlGenerator->linkToRouteAbsolute('digitalsignage.control.activatePreset', ['controlToken' => $controlToken]),
                'activePresetId' => $defaultPreset->getId(),
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function update(
        int $id,
        ?string $name = null,
        string $time_zone = '',
        ?float $weather_latitude = null,
        ?float $weather_longitude = null,
        ?int $active_preset_id = null
    ): JSONResponse {
        try {
            $display = $this->tokenMapper->find($id);
            if ($display->getUserId() !== $this->userId) {
                return new JSONResponse(['error' => 'Unauthorized'], 403);
            }

            if ($name !== null && trim($name) === '') {
                return new JSONResponse(['error' => 'Display name must not be empty'], 400);
            }

            $normalizedTimeZone = trim($time_zone);
            if ($normalizedTimeZone !== '' && !in_array($normalizedTimeZone, \DateTimeZone::listIdentifiers(), true)) {
                return new JSONResponse(['error' => 'Invalid timezone'], 400);
            }

            if ($weather_latitude !== null && ($weather_latitude < -90 || $weather_latitude > 90)) {
                return new JSONResponse(['error' => 'Invalid weather latitude'], 400);
            }
            if ($weather_longitude !== null && ($weather_longitude < -180 || $weather_longitude > 180)) {
                return new JSONResponse(['error' => 'Invalid weather longitude'], 400);
            }

            if ($active_preset_id !== null && $this->presetMapper->findForUser($active_preset_id, (string)$this->userId) === null) {
                return new JSONResponse(['error' => 'Preset not found'], 404);
            }

            $display->setTimeZone($normalizedTimeZone !== '' ? $normalizedTimeZone : null);
            $display->setWeatherLatitude($weather_latitude);
            $display->setWeatherLongitude($weather_longitude);
            $display->setActivePresetId($active_preset_id ?? $display->getActivePresetId());
            if ($name !== null) {
                $display->setName(trim($name));
            }
            $display->setRevision(max(1, $display->getRevision() + 1));
            $display->setUpdatedAt(time());
            $display = $this->tokenMapper->update($display);

            return new JSONResponse(['success' => true, 'revision' => $display->getRevision()]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function clone(int $id): JSONResponse {
        try {
            $source = $this->tokenMapper->find($id);
            if ($source->getUserId() !== $this->userId) {
                return new JSONResponse(['error' => 'Unauthorized'], 403);
            }

            $tokenEntity = new Token();
            $tokenEntity->setToken($this->generateApiToken());
            $tokenEntity->setControlToken($this->generateApiToken());
            $tokenEntity->setUserId((string)$this->userId);
            $tokenEntity->setName($source->getName() . ' (Copy)');
            $tokenEntity->setActivePresetId($source->getActivePresetId());
            $tokenEntity->setTimeZone($source->getTimeZone());
            $tokenEntity->setWeatherLatitude($source->getWeatherLatitude());
            $tokenEntity->setWeatherLongitude($source->getWeatherLongitude());
            $tokenEntity->setRevision(1);
            $tokenEntity->setCreatedAt(time());
            $tokenEntity->setUpdatedAt(time());
            $tokenEntity = $this->tokenMapper->insert($tokenEntity);

            return new JSONResponse(['success' => true, 'id' => $tokenEntity->getId()]);
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
                    'url' => $this->urlGenerator->linkToRouteAbsolute('digitalsignage.public.display', ['token' => $token->getToken()]),
                    'controlUrl' => $this->urlGenerator->linkToRouteAbsolute('digitalsignage.control.activatePreset', ['controlToken' => (string)$token->getControlToken()]),
                    'timeZone' => $token->getTimeZone(),
                    'weatherLatitude' => $token->getWeatherLatitude(),
                    'weatherLongitude' => $token->getWeatherLongitude(),
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
