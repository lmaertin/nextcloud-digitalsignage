<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\Calendar\IManager as ICalendarManager;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\DisplayConfigService;
use OCP\L10N\IFactory;

class PublicApiController extends Controller {
    private $config;
    private $rootFolder;
    private $calendarManager;
    private $tokenMapper;
    private $displayConfigService;
    private $l10nFactory;

    public function __construct(
        string $AppName,
        IRequest $request,
        IConfig $config,
        IRootFolder $rootFolder,
        ICalendarManager $calendarManager,
        TokenMapper $tokenMapper,
        DisplayConfigService $displayConfigService,
        IFactory $l10nFactory
    ) {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->rootFolder = $rootFolder;
        $this->calendarManager = $calendarManager;
        $this->tokenMapper = $tokenMapper;
        $this->displayConfigService = $displayConfigService;
        $this->l10nFactory = $l10nFactory;
    }

    private function validateToken(string $token): ?\OCA\DigitalSignage\Db\Token {
        try {
            $tokenEntity = $this->tokenMapper->findByToken($token);
            if (!$tokenEntity) {
                return null;
            }
            return $tokenEntity;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getConfig(string $token): JSONResponse {
        $display = $this->validateToken($token);
        if (!$display) {
            return new JSONResponse(['error' => 'Invalid token'], 403);
        }

        $userId = $display->getUserId();
        $effectiveConfig = $this->displayConfigService->getEffectiveConfig($display);

        $response = new JSONResponse([
            'displayName' => $effectiveConfig['displayName'],
            'locale' => $effectiveConfig['locale'],
            'contentSplitRatio' => $effectiveConfig['contentSplitRatio'],
            'weather' => [
                'latitude' => $effectiveConfig['weatherLatitude'],
                'longitude' => $effectiveConfig['weatherLongitude']
            ],
            'slideInterval' => $effectiveConfig['slideInterval'],
            'calendarExclude' => $effectiveConfig['calendarExclude'],
            'autoFullscreenPrompt' => $effectiveConfig['autoFullscreenPrompt'],
            'imageFitMode' => $effectiveConfig['imageFitMode'],
            'imageOrderMode' => $effectiveConfig['imageOrderMode'],
            'textScale' => $effectiveConfig['textScale'],
            'fullscreenSlideshow' => $effectiveConfig['fullscreenSlideshow'],
            'activePresetId' => $effectiveConfig['activePresetId'],
            'activePresetName' => $effectiveConfig['activePresetName'],
            'revision' => $effectiveConfig['revision'],
            'i18n' => [
                'fullscreenPromptTitle' => $this->getTranslation('fullscreenPromptTitle', $userId),
                'fullscreenPromptYes' => $this->getTranslation('fullscreenPromptYes', $userId),
                'fullscreenPromptNo' => $this->getTranslation('fullscreenPromptNo', $userId)
            ]
        ]);

        $policy = new ContentSecurityPolicy();
        $policy->addAllowedConnectDomain('https://api.open-meteo.com');
        $response->setContentSecurityPolicy($policy);

        return $response;
    }

    private function getTranslation(string $key, string $userId): string {
        // Get user's language from Nextcloud
        $userLang = $this->config->getUserValue($userId, 'core', 'lang', 'en');

        // Extract base language code (de_DE -> de, en_US -> en)
        $lang = strtolower(substr($userLang, 0, 2));

        $translations = [
            'de' => [
                'fullscreenPromptTitle' => 'Vollbildmodus aktivieren?',
                'fullscreenPromptYes' => 'Ja',
                'fullscreenPromptNo' => 'Nein'
            ],
            'en' => [
                'fullscreenPromptTitle' => 'Activate fullscreen mode?',
                'fullscreenPromptYes' => 'Yes',
                'fullscreenPromptNo' => 'No'
            ]
        ];

        return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getCalendar(string $token): JSONResponse {
        $display = $this->validateToken($token);
        if (!$display) {
            return new JSONResponse(['error' => 'Invalid token'], 403);
        }

        $userId = $display->getUserId();

        try {
            $calendarNamesJson = $this->config->getAppValue('digitalsignage', 'calendar_names', '[]');
            $calendarNames = json_decode($calendarNamesJson, true);

            if (empty($calendarNames) || !is_array($calendarNames)) {
                return new JSONResponse(['error' => 'No calendars configured'], 400);
            }

            $calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $userId);
            $allEvents = [];

            // Iterate through each configured calendar name
            foreach ($calendarNames as $calendarName) {
                $targetCalendar = null;

                foreach ($calendars as $calendar) {
                    if ($calendar->getDisplayName() === $calendarName || $calendar->getKey() === $calendarName) {
                        $targetCalendar = $calendar;
                        break;
                    }
                }

                if (!$targetCalendar) {
                    // Skip calendar if not found instead of returning error
                    continue;
                }

                $searchResult = $targetCalendar->search('', [], [], null, null);

                foreach ($searchResult as $eventData) {
                    $allEvents[] = $eventData;
                }
            }

            return new JSONResponse([
                'calendar' => $allEvents,
                'calendarName' => implode(', ', $calendarNames)
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getImages(string $token): JSONResponse {
        $display = $this->validateToken($token);
        if (!$display) {
            return new JSONResponse(['error' => 'Invalid token'], 403);
        }

        $userId = $display->getUserId();

        try {
            $effectiveConfig = $this->displayConfigService->getEffectiveConfig($display);
            $imageFolder = $effectiveConfig['imageFolder'];

            if (empty($imageFolder)) {
                return new JSONResponse(['error' => 'No image folder configured'], 400);
            }

            $userFolder = $this->rootFolder->getUserFolder($userId);

            try {
                $folder = $userFolder->get($imageFolder);
            } catch (\OCP\Files\NotFoundException $e) {
                return new JSONResponse(['error' => 'Image folder not found: ' . $imageFolder], 404);
            }

            if (!$folder instanceof \OCP\Files\Folder) {
                return new JSONResponse(['error' => 'Path is not a folder'], 400);
            }

            $images = [];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            foreach ($folder->getDirectoryListing() as $file) {
                if ($file->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
                    $extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
                    if (in_array($extension, $allowedExtensions)) {
                        $images[] = [
                            'id' => $file->getId(),
                            'name' => $file->getName(),
                            'path' => $file->getPath(),
                            'size' => $file->getSize(),
                            'mtime' => $file->getMTime()
                        ];
                    }
                }
            }

            return new JSONResponse(['images' => $images]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getImage(string $token): StreamResponse {
        $display = $this->validateToken($token);
        if (!$display) {
            http_response_code(403);
            die('Invalid token');
        }

        try {
            $fileId = $this->request->getParam('id');

            if (empty($fileId)) {
                http_response_code(400);
                die('Missing file ID');
            }

            $userFolder = $this->rootFolder->getUserFolder($display->getUserId());
            $files = $userFolder->getById((int)$fileId);

            if (empty($files)) {
                http_response_code(404);
                die('File not found');
            }

            $file = $files[0];

            if (!($file instanceof \OCP\Files\File)) {
                http_response_code(400);
                die('Not a file');
            }

            $response = new StreamResponse($file->fopen('r'));
            $response->addHeader('Content-Type', $file->getMimeType());
            $response->addHeader('Content-Length', $file->getSize());
            $response->addHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');

            return $response;
        } catch (\Exception $e) {
            http_response_code(500);
            die('Error: ' . $e->getMessage());
        }
    }
}
