<?php
namespace OCA\DigitalSignage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;
use OCP\IConfig;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\Config\IUserConfig;
use OCP\IUserManager;
use OCP\Calendar\IManager as ICalendarManager;
use OCA\DigitalSignage\Db\TokenMapper;
use OCA\DigitalSignage\Service\DisplayConfigService;
use OCA\DigitalSignage\Util\CalendarEventNormalizer;
use OCP\L10N\IFactory;
use OCP\IURLGenerator;

class PublicApiController extends Controller {
    private $config;
    private $rootFolder;
    private $calendarManager;
    private $tokenMapper;
    private $displayConfigService;
    private $l10nFactory;
    private $appManager;
    private $clientService;
    private $userConfig;
    private $userManager;
    private $urlGenerator;

    private function resolveAppLanguage(?string $language, ?string $locale = null): string {
        if (is_string($language) && $language !== '' && $this->l10nFactory->languageExists('digitalsignage', $language)) {
            return $language;
        }

        if (is_string($locale) && $locale !== '') {
            $localeLanguage = $this->l10nFactory->findLanguageFromLocale('digitalsignage', str_replace('-', '_', $locale));
            if (is_string($localeLanguage) && $localeLanguage !== '') {
                return $localeLanguage;
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
        TokenMapper $tokenMapper,
        DisplayConfigService $displayConfigService,
        IFactory $l10nFactory,
        IAppManager $appManager,
        IClientService $clientService,
        IUserConfig $userConfig,
        IUserManager $userManager,
        IURLGenerator $urlGenerator
    ) {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->rootFolder = $rootFolder;
        $this->calendarManager = $calendarManager;
        $this->tokenMapper = $tokenMapper;
        $this->displayConfigService = $displayConfigService;
        $this->l10nFactory = $l10nFactory;
        $this->appManager = $appManager;
        $this->clientService = $clientService;
        $this->userConfig = $userConfig;
        $this->userManager = $userManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getWeather(string $token): JSONResponse {
        $display = $this->validateToken($token);
        if (!$display) {
            return new JSONResponse(['error' => 'Invalid token'], 403);
        }

        $userId = $display->getUserId();
        $user = $this->userManager->get($userId);
        if ($user === null || !$this->appManager->isEnabledForUser('weather_status', $user)) {
            return new JSONResponse(['error' => 'Nextcloud Weather app is not enabled'], 503);
        }

        $latitude = $this->userConfig->getValueFloat($userId, 'weather_status', 'lat');
        $longitude = $this->userConfig->getValueFloat($userId, 'weather_status', 'lon');
        $altitude = $this->userConfig->getValueFloat($userId, 'weather_status', 'altitude');
        if ($latitude === 0.0 || $longitude === 0.0) {
            return new JSONResponse(['error' => 'Weather location is not configured'], 503);
        }

        try {
            $client = $this->clientService->newClient();
            $response = $client->get('https://api.met.no/weatherapi/locationforecast/2.0/compact', [
                'query' => [
                    'lat' => number_format($latitude, 2, '.', ''),
                    'lon' => number_format($longitude, 2, '.', ''),
                    'altitude' => $altitude,
                ],
                'headers' => [
                    'User-Agent' => 'NextcloudDigitalSignage weather integration',
                ],
            ]);
            $weather = json_decode($response->getBody(), true);
            $timeseries = $weather['properties']['timeseries'] ?? [];
            if (!is_array($timeseries) || $timeseries === []) {
                return new JSONResponse(['error' => 'No weather forecast available'], 502);
            }

            $days = [];
            foreach ($timeseries as $entry) {
                $details = $entry['data']['instant']['details'] ?? [];
                $nextHour = $entry['data']['next_1_hours'] ?? $entry['data']['next_6_hours'] ?? [];
                $symbol = $nextHour['summary']['symbol_code'] ?? 'fair_day';
                if (isset($details['air_temperature']) && isset($entry['time'])) {
                    $date = substr((string)$entry['time'], 0, 10);
                    if (!isset($days[$date])) {
                        $days[$date] = [
                            'date' => $date,
                            'minTemperature' => (float)$details['air_temperature'],
                            'maxTemperature' => (float)$details['air_temperature'],
                            'iconCode' => (string)$symbol,
                        ];
                    } else {
                        $days[$date]['minTemperature'] = min($days[$date]['minTemperature'], (float)$details['air_temperature']);
                        $days[$date]['maxTemperature'] = max($days[$date]['maxTemperature'], (float)$details['air_temperature']);
                        if (substr($days[$date]['iconCode'], -6) === '_night' && substr((string)$symbol, -6) !== '_night') {
                            $days[$date]['iconCode'] = (string)$symbol;
                        }
                    }
                }
            }
            $forecast = array_slice(array_values($days), 0, 4);

            if ($forecast === []) {
                return new JSONResponse(['error' => 'No weather forecast available'], 502);
            }

            return new JSONResponse([
                'source' => 'weather_status',
                'unit' => 'celsius',
                'forecast' => $forecast,
            ]);
        } catch (\Throwable $e) {
            return new JSONResponse(['error' => 'Unable to load weather forecast'], 502);
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getWeatherIcon(string $token): StreamResponse {
        if (!$this->validateToken($token)) {
            http_response_code(403);
            die('Invalid token');
        }

        $icon = (string)$this->request->getParam('icon');
        if (!preg_match('/^[a-z0-9_]+$/', $icon)) {
            http_response_code(400);
            die('Invalid weather icon');
        }

        $iconPath = $this->appManager->getAppPath('weather_status') . '/img/met.no.icons/' . $icon . '.svg';
        if (!is_file($iconPath)) {
            http_response_code(404);
            die('Weather icon not found');
        }

        $response = new StreamResponse(fopen($iconPath, 'rb'));
        $response->addHeader('Content-Type', 'image/svg+xml');
        $response->addHeader('Content-Security-Policy', "default-src 'none'; img-src 'self' data:");
        $response->addHeader('Cache-Control', 'public, max-age=3600');
        return $response;
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
            'slideInterval' => $effectiveConfig['slideInterval'],
            'imageRefreshIntervalMinutes' => $effectiveConfig['imageRefreshIntervalMinutes'],
            'calendarExclude' => $effectiveConfig['calendarExclude'],
            'showEventDescription' => $effectiveConfig['showEventDescription'],
            'autoFullscreenPrompt' => $effectiveConfig['autoFullscreenPrompt'],
            'imageFitMode' => $effectiveConfig['imageFitMode'],
            'imageOrderMode' => $effectiveConfig['imageOrderMode'],
            'textSizes' => $effectiveConfig['textSizes'],
            'textSizeCssVariables' => $effectiveConfig['textSizeCssVariables'],
            'fullscreenSlideshow' => $effectiveConfig['fullscreenSlideshow'],
            'showSlideshow' => $effectiveConfig['showSlideshow'],
            'showWeather' => $effectiveConfig['showWeather'],
            'showCalendar' => $effectiveConfig['showCalendar'],
            'headerTitleSource' => $effectiveConfig['headerTitleSource'],
            'activePresetId' => $effectiveConfig['activePresetId'],
            'activePresetName' => $effectiveConfig['activePresetName'],
            'revision' => $effectiveConfig['revision'],
            'i18n' => [
                'fullscreenPromptTitle' => $this->getTranslation('fullscreenPromptTitle', $userId),
                'fullscreenPromptYes' => $this->getTranslation('fullscreenPromptYes', $userId),
                'fullscreenPromptNo' => $this->getTranslation('fullscreenPromptNo', $userId),
                'weatherLocationRequired' => $this->getTranslation('weatherLocationRequired', $userId),
                'weatherUnavailable' => $this->getTranslation('weatherUnavailable', $userId),
                'slideshowRefreshFailed' => $this->getTranslation('slideshowRefreshFailed', $userId),
                'slideshowError' => $this->getTranslation('slideshowError', $userId),
                'slideshowCheckConsole' => $this->getTranslation('slideshowCheckConsole', $userId),
            ]
        ]);

        $policy = new ContentSecurityPolicy();
        $response->setContentSecurityPolicy($policy);

        return $response;
    }

    private function getTranslation(string $key, string $userId): string {
        $userLang = $this->config->getUserValue($userId, 'core', 'lang', 'en');
        $userLocale = $this->config->getUserValue($userId, 'core', 'locale', null);
        $appLanguage = $this->resolveAppLanguage($userLang, $userLocale);
        $l10n = $this->l10nFactory->get('digitalsignage', $appLanguage, $userLocale);

        return match ($key) {
            'fullscreenPromptTitle' => $l10n->t('Activate fullscreen mode?'),
            'fullscreenPromptYes' => $l10n->t('Yes'),
            'fullscreenPromptNo' => $l10n->t('No'),
            'weatherLocationRequired' => $l10n->t('Weather location not configured'),
            'weatherUnavailable' => $l10n->t('Weather forecast unavailable'),
            'slideshowRefreshFailed' => $l10n->t('Slideshow image refresh failed'),
            'slideshowError' => $l10n->t('Slideshow Error'),
            'slideshowCheckConsole' => $l10n->t('Check console for details'),
            default => $key,
        };
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

                $start = new \DateTime();
                $end = new \DateTime();
                $end->modify('+30 days');

                $searchResult = $targetCalendar->search('', [], ['timerange' => ['start' => $start, 'end' => $end]], null, null);

                foreach ($searchResult as $eventData) {
                    $allEvents[] = CalendarEventNormalizer::normalize($eventData);
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
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'mkv'];
            $videoExtensions = ['mp4', 'webm', 'mov', 'mkv'];

            foreach ($folder->getDirectoryListing() as $file) {
                if ($file->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
                    $extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
                    if (in_array($extension, $allowedExtensions)) {
                        $images[] = [
                            'id' => $file->getId(),
                            'name' => $file->getName(),
                            'path' => $file->getPath(),
                            'size' => $file->getSize(),
                            'mtime' => $file->getMTime(),
                            'type' => in_array($extension, $videoExtensions) ? 'video' : 'image'
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
