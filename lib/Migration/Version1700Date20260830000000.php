<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1700Date20260830000000 extends SimpleMigrationStep {
    public function __construct(
        private IConfig $config
    ) {
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $legacySeconds = $this->config->getAppValue('digitalsignage', 'image_refresh_interval', '');
        if ($legacySeconds === '') {
            return;
        }

        $minutes = max(0, (float)$legacySeconds / 60);
        $this->config->setAppValue('digitalsignage', 'image_refresh_interval_minutes', (string)$minutes);
        $this->config->deleteAppValue('digitalsignage', 'image_refresh_interval');
    }
}