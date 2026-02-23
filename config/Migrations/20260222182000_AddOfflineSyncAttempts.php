<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddOfflineSyncAttempts extends BaseMigration
{
    public function change(): void
    {
        if (!$this->hasTable('offline_sync_attempts')) {
            $this->table('offline_sync_attempts')
                ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('client_attempt_id', 'string', ['limit' => 191, 'null' => false])
                ->addColumn('test_attempt_id', 'integer', ['null' => true, 'default' => null, 'signed' => false])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['user_id', 'client_attempt_id'], ['unique' => true, 'name' => 'uq_offline_sync_user_client'])
                ->addIndex(['test_attempt_id'])
                ->create();
        }

        $table = $this->table('offline_sync_attempts');
        if (!$table->hasForeignKey('user_id')) {
            $table->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
        }
        if (!$table->hasForeignKey('test_attempt_id')) {
            $table->addForeignKey('test_attempt_id', 'test_attempts', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ]);
        }
        $table->update();
    }
}
