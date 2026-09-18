<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1900Date20260918000000 extends SimpleMigrationStep {
    public function __construct(private IConfig $config) {
    }

    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('digitalsignage_tokens')) {
            $table = $schema->getTable('digitalsignage_tokens');
            if (!$table->hasColumn('time_zone')) {
                $table->addColumn('time_zone', Types::STRING, ['notnull' => false, 'length' => 64]);
            }
            if (!$table->hasColumn('weather_latitude')) {
                $table->addColumn('weather_latitude', Types::FLOAT, ['notnull' => false]);
            }
            if (!$table->hasColumn('weather_longitude')) {
                $table->addColumn('weather_longitude', Types::FLOAT, ['notnull' => false]);
            }
        }
        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $this->config->deleteAppValue('digitalsignage', 'display_timezone');
    }
}
