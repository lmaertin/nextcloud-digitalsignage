<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1500Date20260815000000 extends SimpleMigrationStep {
    public function __construct(
        private IDBConnection $db,
        private IConfig $config
    ) {
    }

    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('digitalsignage_presets')) {
            $table = $schema->getTable('digitalsignage_presets');
            if (!$table->hasColumn('show_event_description')) {
                $table->addColumn('show_event_description', Types::STRING, [
                    'notnull' => true,
                    'length' => 1,
                    'default' => '0',
                ]);
            }
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $value = $this->config->getAppValue('digitalsignage', 'show_event_description', '0') === '1' ? '1' : '0';
        $queryBuilder = $this->db->getQueryBuilder();
        $queryBuilder->update('digitalsignage_presets')
            ->set('show_event_description', $queryBuilder->createNamedParameter($value));
        $queryBuilder->executeStatement();
    }
}
