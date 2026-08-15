<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1600Date20260815000000 extends SimpleMigrationStep {
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
            if (!$table->hasColumn('header_title_source')) {
                $table->addColumn('header_title_source', Types::STRING, [
                    'notnull' => true,
                    'length' => 16,
                    'default' => 'global',
                ]);
            }
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $queryBuilder = $this->db->getQueryBuilder();
        $queryBuilder->update('digitalsignage_presets')
            ->set(
                'header_title_source',
                $queryBuilder->createNamedParameter(
                    $this->config->getAppValue('digitalsignage', 'show_display_name', '1') === '1'
                        ? 'global'
                        : 'none'
                )
            );
        $queryBuilder->executeStatement();
    }
}
