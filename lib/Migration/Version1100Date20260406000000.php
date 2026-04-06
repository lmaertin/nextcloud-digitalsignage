<?php

declare(strict_types=1);

namespace OCA\DigitalSignage\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1100Date20260406000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('digitalsignage_tokens')) {
            $table = $schema->getTable('digitalsignage_tokens');

            if (!$table->hasColumn('control_token')) {
                $table->addColumn('control_token', Types::STRING, [
                    'notnull' => false,
                    'length' => 64,
                ]);
            }

            if (!$table->hasColumn('active_preset_id')) {
                $table->addColumn('active_preset_id', Types::INTEGER, [
                    'notnull' => false,
                ]);
            }

            if (!$table->hasColumn('revision')) {
                $table->addColumn('revision', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 1,
                ]);
            }

            if (!$table->hasColumn('updated_at')) {
                $table->addColumn('updated_at', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                ]);
            }

            if (!$table->hasIndex('digitalsignage_control_token_idx')) {
                $table->addUniqueIndex(['control_token'], 'digitalsignage_control_token_idx');
            }

            if (!$table->hasIndex('digitalsignage_active_preset_idx')) {
                $table->addIndex(['active_preset_id'], 'digitalsignage_active_preset_idx');
            }
        }

        if (!$schema->hasTable('digitalsignage_presets')) {
            $table = $schema->createTable('digitalsignage_presets');
            $table->addColumn('id', Types::INTEGER, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 255,
            ]);
            $table->addColumn('image_folder', Types::STRING, [
                'notnull' => true,
                'length' => 512,
            ]);
            $table->addColumn('image_fit_mode', Types::STRING, [
                'notnull' => true,
                'length' => 32,
                'default' => 'cover',
            ]);
            $table->addColumn('image_order_mode', Types::STRING, [
                'notnull' => true,
                'length' => 32,
                'default' => 'shuffle',
            ]);
            $table->addColumn('fullscreen_slideshow', Types::STRING, [
                'notnull' => true,
                'length' => 1,
                'default' => '0',
            ]);
            $table->addColumn('slide_interval', Types::INTEGER, [
                'notnull' => true,
                'default' => 10,
            ]);
            $table->addColumn('created_at', Types::INTEGER, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::INTEGER, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'digitalsignage_preset_user_idx');
            $table->addUniqueIndex(['user_id', 'name'], 'digitalsignage_preset_user_name_idx');
        }

        return $schema;
    }
}
