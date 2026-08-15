<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1400Date20260815000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('digitalsignage_presets')) {
            $table = $schema->getTable('digitalsignage_presets');
            foreach (['show_slideshow', 'show_weather', 'show_calendar'] as $columnName) {
                if (!$table->hasColumn($columnName)) {
                    $table->addColumn($columnName, Types::STRING, [
                        'notnull' => true,
                        'length' => 1,
                        'default' => '1',
                    ]);
                }
            }
        }

        return $schema;
    }
}
