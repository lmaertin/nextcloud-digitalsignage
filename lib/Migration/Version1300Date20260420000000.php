<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1300Date20260420000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('digitalsignage_presets')) {
            $table = $schema->getTable('digitalsignage_presets');

            if (!$table->hasColumn('show_display_name')) {
                $table->addColumn('show_display_name', Types::STRING, [
                    'notnull' => true,
                    'length' => 1,
                    'default' => '1',
                ]);
            }
        }

        return $schema;
    }
}
