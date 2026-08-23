<?php
namespace OCA\DigitalSignage\Controller;

use OCA\DigitalSignage\Util\TextSizeConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\Calendar\IManager as ICalendarManager;
use OCP\IUserSession;

class ApiController extends Controller {
    private $config;
    private $rootFolder;
    private $calendarManager;
    private $userSession;
    private $userId;

    private function normalizeLocale(string $locale): string {
        $normalized = str_replace('_', '-', trim($locale));
        if ($normalized === '') {
            return 'en';
        }

        if (preg_match('/^[a-z]{2}$/i', $normalized) === 1) {
            return strtolower($normalized);
        }

        if (preg_match('/^[a-z]{2}-[a-z]{2}$/i', $normalized) === 1) {
            $parts = explode('-', $normalized, 2);
            return strtolower($parts[0]) . '-' . strtoupper($parts[1]);
        }

        return 'en';
    }

    private function resolveLocale(): string {
        $configuredLocale = trim($this->config->getAppValue('digitalsignage', 'locale', ''));
        if ($configuredLocale !== '') {
            return $this->normalizeLocale($configuredLocale);
        }

        if (!empty($this->userId)) {
            $userLocale = trim($this->config->getUserValue((string)$this->userId, 'core', 'lang', 'en'));
            if ($userLocale !== '') {
                return $this->normalizeLocale($userLocale);
            }
        }

        return 'en';
    }

    public function __construct(
        string $AppName,
        IRequest $request,
        IConfig $config,
        IRootFolder $rootFolder,
        ICalendarManager $calendarManager,
        IUserSession $userSession,
        ?string $userId
    ) {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->rootFolder = $rootFolder;
        $this->calendarManager = $calendarManager;
        $this->userSession = $userSession;
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     */
    public function getConfig(): JSONResponse {
        $response = new JSONResponse([
            'locale' => $this->resolveLocale(),
            'contentSplitRatio' => max(50, min(85, (int)$this->config->getAppValue('digitalsignage', 'content_split_ratio', '50'))),
            'slideInterval' => (int)$this->config->getAppValue('digitalsignage', 'slide_interval', '60'),
            'calendarExclude' => json_decode($this->config->getAppValue('digitalsignage', 'calendar_exclude', '[]'), true),
            'imageFitMode' => $this->config->getAppValue('digitalsignage', 'image_fit_mode', 'cover'),
            'imageOrderMode' => $this->config->getAppValue('digitalsignage', 'image_order_mode', 'shuffle'),
            'textSizes' => TextSizeConfig::getConfiguredSizes($this->config),
            'textSizeCssVariables' => TextSizeConfig::toCssVariables(TextSizeConfig::getConfiguredSizes($this->config)),
            'fullscreenSlideshow' => $this->config->getAppValue('digitalsignage', 'fullscreen_slideshow', '0') === '1'
        ]);

        $policy = new ContentSecurityPolicy();
        $response->setContentSecurityPolicy($policy);

        return $response;
    }

    /**
     * @NoAdminRequired
     */
    public function getCalendarsList(): JSONResponse {
        try {
            $calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $this->userId);
            $calendarList = [];

            foreach ($calendars as $calendar) {
                // Skip deleted/trashed calendars
                if (method_exists($calendar, 'isDeleted') && $calendar->isDeleted()) {
                    continue;
                }

                // Filter out calendars from trash
                $uri = $calendar->getUri();
                if (strpos($uri, 'trash') !== false || strpos($uri, 'deleted') !== false) {
                    continue;
                }

                $calendarList[] = [
                    'key' => $calendar->getKey(),
                    'displayName' => $calendar->getDisplayName(),
                    'uri' => $calendar->getUri()
                ];
            }

            return new JSONResponse($calendarList);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function getFoldersList(): JSONResponse {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            $folders = $this->listFolders($userFolder, '');

            return new JSONResponse($folders);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function listFolders($folder, $path) {
        $folders = [];

        foreach ($folder->getDirectoryListing() as $node) {
            if ($node->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $folderPath = $path . '/' . $node->getName();
                $folders[] = $folderPath;

                // Recursively list subfolders (max depth 3)
                if (substr_count($folderPath, '/') < 3) {
                    $subfolders = $this->listFolders($node, $folderPath);
                    $folders = array_merge($folders, $subfolders);
                }
            }
        }

        return $folders;
    }

    /**
     * @NoAdminRequired
     */
    public function getCalendar(): JSONResponse {
        try {
            $calendarName = $this->config->getAppValue('digitalsignage', 'calendar_name', '');

            if (empty($calendarName)) {
                return new JSONResponse(['error' => 'No calendar configured'], 400);
            }

            $calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $this->userId);
            $targetCalendar = null;

            foreach ($calendars as $calendar) {
                if ($calendar->getDisplayName() === $calendarName || $calendar->getKey() === $calendarName) {
                    $targetCalendar = $calendar;
                    break;
                }
            }

            if (!$targetCalendar) {
                return new JSONResponse(['error' => 'Calendar not found: ' . $calendarName], 404);
            }

            // Get events for next 30 days
            $start = new \DateTime();
            $end = new \DateTime();
            $end->modify('+30 days');

            $searchResult = $targetCalendar->search('', [], [], null, null);
            $events = [];

            foreach ($searchResult as $eventData) {
                $events[] = $eventData;
            }

            // Return raw calendar data
            $response = new JSONResponse([
                'calendar' => $events,
                'calendarName' => $targetCalendar->getDisplayName()
            ]);

            return $response;
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function getEventTitles(): JSONResponse {
        try {
            $calendarNamesJson = $this->config->getAppValue('digitalsignage', 'calendar_names', '[]');
            $calendarNames = json_decode($calendarNamesJson, true);

            if (empty($calendarNames) || !is_array($calendarNames)) {
                return new JSONResponse([]);
            }

            $calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $this->userId);
            $eventTitles = [];

            foreach ($calendarNames as $calendarName) {
                $targetCalendar = null;

                foreach ($calendars as $calendar) {
                    if ($calendar->getDisplayName() === $calendarName || $calendar->getKey() === $calendarName) {
                        $targetCalendar = $calendar;
                        break;
                    }
                }

                if (!$targetCalendar) {
                    continue;
                }

                $searchResult = $targetCalendar->search('', [], [], null, null);

                foreach ($searchResult as $eventData) {
                    $title = null;

                    if (isset($eventData['objects'][0]['SUMMARY'][0])) {
                        $title = $eventData['objects'][0]['SUMMARY'][0];
                    } elseif (isset($eventData['SUMMARY'])) {
                        $title = is_array($eventData['SUMMARY']) ? $eventData['SUMMARY'][0] : $eventData['SUMMARY'];
                    }

                    if ($title && !empty(trim($title)) && !in_array($title, $eventTitles)) {
                        $eventTitles[] = trim($title);
                    }
                }
            }

            sort($eventTitles, SORT_STRING | SORT_FLAG_CASE);

            return new JSONResponse($eventTitles);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function getImages(): JSONResponse {
        try {
            $imageFolder = $this->config->getAppValue('digitalsignage', 'image_folder', '/Fotos/Info-Monitor');

            if (empty($imageFolder)) {
                return new JSONResponse(['error' => 'No image folder configured'], 400);
            }

            $userFolder = $this->rootFolder->getUserFolder($this->userId);

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
     * @NoAdminRequired
     */
    public function getImage(): StreamResponse {
        try {
            $fileId = $this->request->getParam('id');

            if (empty($fileId)) {
                http_response_code(400);
                die('Missing file ID');
            }

            $userFolder = $this->rootFolder->getUserFolder($this->userId);
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
