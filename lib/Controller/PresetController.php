<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Db\Preset;
use OCA\DigitalSignage\Db\PresetMapper;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\PresetService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PresetController extends Controller {
    private PresetMapper $presetMapper;
    private TokenMapper $tokenMapper;
    private PresetService $presetService;
    private ?string $userId;

    public function __construct(
        string $appName,
        IRequest $request,
        PresetMapper $presetMapper,
        TokenMapper $tokenMapper,
        PresetService $presetService,
        ?string $userId
    ) {
        parent::__construct($appName, $request);
        $this->presetMapper = $presetMapper;
        $this->tokenMapper = $tokenMapper;
        $this->presetService = $presetService;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     */
    public function list(): JSONResponse {
        try {
            $this->presetService->ensureDefaultPreset((string)$this->userId);
            $presets = array_map(function (Preset $preset): Preset {
                $normalizedImageOrderMode = $this->readImageOrderMode($preset->getImageOrderMode() ?? '');
                if ($normalizedImageOrderMode !== ($preset->getImageOrderMode() ?? '')) {
                    $preset->setImageOrderMode($normalizedImageOrderMode);
                    $preset->setUpdatedAt(time());
                    return $this->presetMapper->update($preset);
                }

                return $preset;
            }, $this->presetMapper->findByUserId((string)$this->userId));

            return new JSONResponse(array_map(fn (Preset $preset): array => $this->presetService->serializePreset($preset), $presets));
        } catch (\Throwable $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function create(
        string $name,
        string $image_folder,
        string $image_fit_mode = 'cover',
        string $image_order_mode = 'shuffle',
        string $fullscreen_slideshow = '0',
        string $show_display_name = '1',
        string $header_title_source = 'global',
        string $show_slideshow = '1',
        string $show_weather = '1',
        string $show_calendar = '1',
        string $show_event_description = '0',
        string $calendar_names = '[]',
        string $calendar_exclude = '[]',
        int $slide_interval = 10,
        string $recursive_media = '0'
    ): JSONResponse {
        try {
            if (trim($name) === '') {
                return new JSONResponse(['error' => 'Missing preset name'], 400);
            }
            if ($this->presetMapper->findByNameForUser(trim($name), (string)$this->userId) !== null) {
                return new JSONResponse(['error' => 'A preset with this name already exists'], 409);
            }
            if ($show_slideshow !== '1' && $show_weather !== '1' && $show_calendar !== '1') {
                return new JSONResponse(['error' => 'At least one widget must be enabled'], 400);
            }

            $imageOrderMode = $this->readImageOrderMode($image_order_mode);
            $now = time();
            $preset = new Preset();
            $preset->setUserId((string)$this->userId);
            $preset->setName(trim($name));
            $preset->setImageFolder($image_folder);
            $preset->setImageFitMode($image_fit_mode);
            $preset->setImageOrderMode($imageOrderMode);
            $preset->setRecursiveMedia($recursive_media === '1' ? '1' : '0');
            $preset->setFullscreenSlideshow($fullscreen_slideshow === '1' ? '1' : '0');
            $preset->setShowDisplayName($show_display_name === '1' ? '1' : '0');
            $preset->setHeaderTitleSource($this->presetService->normalizeHeaderTitleSource($header_title_source));
            $preset->setShowSlideshow($show_slideshow === '1' ? '1' : '0');
            $preset->setShowWeather($show_weather === '1' ? '1' : '0');
            $preset->setShowCalendar($show_calendar === '1' ? '1' : '0');
            $preset->setShowEventDescription($show_event_description === '1' ? '1' : '0');
            $preset->setCalendarNames($this->presetService->normalizeCalendarNames($calendar_names));
            $preset->setCalendarExclude($this->presetService->normalizeCalendarNames($calendar_exclude));
            $preset->setSlideInterval(max(5, min(300, $slide_interval)));
            $preset->setCreatedAt($now);
            $preset->setUpdatedAt($now);

            $preset = $this->presetMapper->insert($preset);

            return new JSONResponse($this->presetService->serializePreset($preset));
        } catch (\Throwable $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function update(
        int $id,
        string $name,
        string $image_folder,
        string $image_fit_mode = 'cover',
        string $image_order_mode = 'shuffle',
        string $fullscreen_slideshow = '0',
        string $show_display_name = '1',
        string $header_title_source = 'global',
        string $show_slideshow = '1',
        string $show_weather = '1',
        string $show_calendar = '1',
        string $show_event_description = '0',
        string $calendar_names = '[]',
        string $calendar_exclude = '[]',
        int $slide_interval = 10,
        string $recursive_media = '0'
    ): JSONResponse {
        try {
            $preset = $this->presetMapper->findForUser($id, (string)$this->userId);
            if ($preset === null) {
                return new JSONResponse(['error' => 'Preset not found'], 404);
            }
            $existingPreset = $this->presetMapper->findByNameForUser(trim($name), (string)$this->userId);
            if ($existingPreset !== null && $existingPreset->getId() !== $id) {
                return new JSONResponse(['error' => 'A preset with this name already exists'], 409);
            }
            if ($show_slideshow !== '1' && $show_weather !== '1' && $show_calendar !== '1') {
                return new JSONResponse(['error' => 'At least one widget must be enabled'], 400);
            }

            $imageOrderMode = $this->readImageOrderMode($image_order_mode);
            $preset->setName(trim($name));
            $preset->setImageFolder($image_folder);
            $preset->setImageFitMode($image_fit_mode);
            $preset->setImageOrderMode($imageOrderMode);
            $preset->setRecursiveMedia($recursive_media === '1' ? '1' : '0');
            $preset->setFullscreenSlideshow($fullscreen_slideshow === '1' ? '1' : '0');
            $preset->setShowDisplayName($show_display_name === '1' ? '1' : '0');
            $preset->setHeaderTitleSource($this->presetService->normalizeHeaderTitleSource($header_title_source));
            $preset->setShowSlideshow($show_slideshow === '1' ? '1' : '0');
            $preset->setShowWeather($show_weather === '1' ? '1' : '0');
            $preset->setShowCalendar($show_calendar === '1' ? '1' : '0');
            $preset->setShowEventDescription($show_event_description === '1' ? '1' : '0');
            $preset->setCalendarNames($this->presetService->normalizeCalendarNames($calendar_names));
            $preset->setCalendarExclude($this->presetService->normalizeCalendarNames($calendar_exclude));
            $preset->setSlideInterval(max(5, min(300, $slide_interval)));
            $preset->setUpdatedAt(time());

            $preset = $this->presetMapper->update($preset);

            return new JSONResponse($this->presetService->serializePreset($preset));
        } catch (\Throwable $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function clone(int $id): JSONResponse {
        try {
            $source = $this->presetMapper->findForUser($id, (string)$this->userId);
            if ($source === null) {
                return new JSONResponse(['error' => 'Preset not found'], 404);
            }

            $copy = new Preset();
            $copy->setUserId((string)$this->userId);
            $copy->setName($this->getCloneName($source->getName(), (string)$this->userId));
            $copy->setImageFolder($source->getImageFolder());
            $copy->setImageFitMode($source->getImageFitMode());
            $copy->setImageOrderMode($source->getImageOrderMode());
            $copy->setRecursiveMedia($source->getRecursiveMedia() ?: '0');
            $copy->setFullscreenSlideshow($source->getFullscreenSlideshow());
            $copy->setShowDisplayName($source->getShowDisplayName());
            $copy->setHeaderTitleSource($source->getHeaderTitleSource());
            $copy->setShowSlideshow($source->getShowSlideshow());
            $copy->setShowWeather($source->getShowWeather());
            $copy->setShowCalendar($source->getShowCalendar());
            $copy->setShowEventDescription($source->getShowEventDescription());
            $copy->setCalendarNames($source->getCalendarNames() ?: '[]');
            $copy->setCalendarExclude($source->getCalendarExclude() ?: '[]');
            $copy->setSlideInterval($source->getSlideInterval());
            $copy->setCreatedAt(time());
            $copy->setUpdatedAt(time());

            return new JSONResponse($this->presetService->serializePreset($this->presetMapper->insert($copy)));
        } catch (\Throwable $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function delete(int $id): JSONResponse {
        try {
            $preset = $this->presetMapper->findForUser($id, (string)$this->userId);
            if ($preset === null) {
                return new JSONResponse(['error' => 'Preset not found'], 404);
            }

            $displays = $this->tokenMapper->findByActivePresetId($id, (string)$this->userId);
            foreach ($displays as $display) {
                $display->setActivePresetId(null);
                $display->setRevision(max(1, $display->getRevision() + 1));
                $display->setUpdatedAt(time());
                $this->tokenMapper->update($display);
            }

            $this->presetMapper->delete($preset);

            return new JSONResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function readImageOrderMode(string $fallback = 'shuffle'): string {
        $requestValue = $this->request->getParam('image_order_mode', $this->request->getParam('imageOrderMode', $fallback));
        return $this->presetService->normalizeImageOrderMode(is_string($requestValue) ? $requestValue : $fallback);
    }

    private function getCloneName(string $sourceName, string $userId): string {
        $baseName = $sourceName . ' (Copy)';
        $candidate = $baseName;
        $suffix = 2;
        while ($this->presetMapper->findByNameForUser($candidate, $userId) !== null) {
            $candidate = $baseName . ' ' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

}
