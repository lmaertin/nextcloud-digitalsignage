<?php
return [
    'routes' => [
        // Admin/User page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        // Public display page (no auth required)
        ['name' => 'public#display', 'url' => '/public/{token}', 'verb' => 'GET'],

        // API routes
        ['name' => 'api#getConfig', 'url' => '/api/config', 'verb' => 'GET'],
        ['name' => 'api#getCalendarsList', 'url' => '/api/calendars', 'verb' => 'GET'],
        ['name' => 'api#getFoldersList', 'url' => '/api/folders', 'verb' => 'GET'],
        ['name' => 'api#getCalendar', 'url' => '/api/calendar', 'verb' => 'GET'],
        ['name' => 'api#getEventTitles', 'url' => '/api/event-titles', 'verb' => 'GET'],
        ['name' => 'api#getImages', 'url' => '/api/images', 'verb' => 'GET'],
        ['name' => 'api#getImage', 'url' => '/api/image', 'verb' => 'GET'],
        ['name' => 'preset#list', 'url' => '/api/presets', 'verb' => 'GET'],
        ['name' => 'preset#create', 'url' => '/api/presets', 'verb' => 'POST'],
        ['name' => 'preset#update', 'url' => '/api/presets/{id}', 'verb' => 'PUT'],
        ['name' => 'preset#delete', 'url' => '/api/presets/{id}', 'verb' => 'DELETE'],

        // Public API routes (with token)
        ['name' => 'publicApi#getConfig', 'url' => '/api/public/{token}/config', 'verb' => 'GET'],
        ['name' => 'publicApi#getCalendar', 'url' => '/api/public/{token}/calendar', 'verb' => 'GET'],
        ['name' => 'publicApi#getImages', 'url' => '/api/public/{token}/images', 'verb' => 'GET'],
        ['name' => 'publicApi#getImage', 'url' => '/api/public/{token}/image', 'verb' => 'GET'],
        ['name' => 'control#activatePreset', 'url' => '/api/control/{controlToken}/activate-preset', 'verb' => 'POST'],

        // Token management
        ['name' => 'token#create', 'url' => '/api/token/create', 'verb' => 'POST'],
        ['name' => 'token#list', 'url' => '/api/token/list', 'verb' => 'GET'],
        ['name' => 'token#activatePreset', 'url' => '/api/token/{id}/activate-preset', 'verb' => 'POST'],
        ['name' => 'token#delete', 'url' => '/api/token/delete/{id}', 'verb' => 'DELETE'],

        // Settings
        ['name' => 'settings#saveUser', 'url' => '/settings/user', 'verb' => 'POST'],
    ]
];
