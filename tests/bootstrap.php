<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Lukas Märtin <github@lukas-maertin.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Try to load the Nextcloud server when available (e.g., in CI container)
if (file_exists(__DIR__ . '/../../../lib/base.php')) {
    try {
        require_once __DIR__ . '/../../../lib/base.php';
    } catch (\Throwable $e) {
        // If server is not installed locally, fall back to pure composer autoload
    }
}

// Define test constants
define('PHPUNIT_RUN', true);
