<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UpdateAiRequestsTable extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('ai_requests');

        // Ensure source_medium exists and is varchar
        if (!$table->hasColumn('source_medium')) {
            $table->addColumn('source_medium', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ]);
        } else {
            $table->changeColumn('source_medium', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ]);
        }

        // Ensure source_reference exists and is varchar
        if (!$table->hasColumn('source_reference')) {
            $table->addColumn('source_reference', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ]);
        } else {
            $table->changeColumn('source_reference', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ]);
        }

        // Change type to string to allow 'test_generation'
        $table->changeColumn('type', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => false,
        ]);

        // Change status to string to be safe
        $table->changeColumn('status', 'string', [
            'default' => 'success',
            'limit' => 50,
            'null' => false,
        ]);
        
        $table->update();
    }
}
