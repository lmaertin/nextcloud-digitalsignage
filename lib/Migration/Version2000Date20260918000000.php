<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2000Date20260918000000 extends SimpleMigrationStep {
    public function __construct(private IDBConnection $db, private IConfig $config) {
    }

    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('digitalsignage_presets')) {
            $table = $schema->getTable('digitalsignage_presets');
            if (!$table->hasColumn('calendar_names')) {
                $table->addColumn('calendar_names', Types::TEXT, ['notnull' => false]);
            }
        }
        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $calendarNames = $this->config->getAppValue('digitalsignage', 'calendar_names', '[]');
        $queryBuilder = $this->db->getQueryBuilder();
        $queryBuilder->update('digitalsignage_presets')
            ->set('calendar_names', $queryBuilder->createNamedParameter($calendarNames))
            ->executeStatement();
        $this->config->deleteAppValue('digitalsignage', 'calendar_names');
    }
}
