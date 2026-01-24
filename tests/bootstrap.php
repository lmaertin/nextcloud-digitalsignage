<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Lukas Märtin <github@lukas-maertin.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Check if we're running inside Nextcloud container
if (file_exists(__DIR__ . '/../../../lib/base.php')) {
    // Running in container - load Nextcloud
    require_once __DIR__ . '/../../../lib/base.php';
} else {
    // Running locally - use composer autoloader only
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Define test constants
define('PHPUNIT_RUN', true);
