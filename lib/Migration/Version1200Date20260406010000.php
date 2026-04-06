<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1200Date20260406010000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('digitalsignage_presets')) {
            $table = $schema->getTable('digitalsignage_presets');

            if (!$table->hasColumn('image_order_mode')) {
                $table->addColumn('image_order_mode', Types::STRING, [
                    'notnull' => true,
                    'length' => 32,
                    'default' => 'shuffle',
                ]);
            }
        }

        return $schema;
    }
}
