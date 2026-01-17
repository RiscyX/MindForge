<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveEntityColumnsFromActivityLogs extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('activity_logs')) {
            return;
        }

        $table = $this->table('activity_logs');

        if ($table->hasColumn('entity_type')) {
            $table->removeColumn('entity_type');
        }
        if ($table->hasColumn('entity_id')) {
            $table->removeColumn('entity_id');
        }

        $table->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        if (!$this->hasTable('activity_logs')) {
            return;
        }

        $table = $this->table('activity_logs');
        if (!$table->hasColumn('entity_type')) {
            $table->addColumn('entity_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ]);
        }
        if (!$table->hasColumn('entity_id')) {
            $table->addColumn('entity_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ]);
        }

        $table->update();
    }
}
