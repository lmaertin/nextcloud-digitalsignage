<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2200Date20260919000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('digitalsignage_presets')) {
            $table = $schema->getTable('digitalsignage_presets');
            if (!$table->hasColumn('recursive_media')) {
                $table->addColumn('recursive_media', Types::STRING, [
                    'notnull' => false,
                    'length' => 1,
                    'default' => '0',
                ]);
            }
        }

        return $schema;
    }
}
