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
        $table = $this->table('activity_logs');
        $table
            ->removeColumn('entity_type')
            ->removeColumn('entity_id')
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('activity_logs');
        $table
            ->addColumn('entity_type', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('entity_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->update();
    }
}
