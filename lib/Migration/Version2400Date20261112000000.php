<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Adds an optional short URL slug per display, e.g. /apps/digitalsignage/s/lobby.
 */
class Version2400Date20261112000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if (!$schema->hasTable('digitalsignage_tokens')) {
            return null;
        }

        $table = $schema->getTable('digitalsignage_tokens');
        if (!$table->hasColumn('slug')) {
            $table->addColumn('slug', Types::STRING, [
                'notnull' => false,
                'length' => 64,
            ]);
        }
        if (!$table->hasIndex('ds_tokens_slug_uniq')) {
            $table->addUniqueIndex(['slug'], 'ds_tokens_slug_uniq');
        }

        return $schema;
    }
}
