<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddDeviceLogsPerformanceIndexes extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        if (!$this->hasTable('device_logs')) {
            return;
        }

        $table = $this->table('device_logs');

        if (!$table->hasIndex(['created_at', 'ip_address'])) {
            $table->addIndex(['created_at', 'ip_address'], ['name' => 'idx_device_logs_created_ip']);
        }

        if (!$table->hasIndex(['created_at', 'user_id'])) {
            $table->addIndex(['created_at', 'user_id'], ['name' => 'idx_device_logs_created_user']);
        }

        $table->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        if (!$this->hasTable('device_logs')) {
            return;
        }

        $table = $this->table('device_logs');

        if ($table->hasIndex(['created_at', 'ip_address'])) {
            $table->removeIndex(['created_at', 'ip_address']);
        }

        if ($table->hasIndex(['created_at', 'user_id'])) {
            $table->removeIndex(['created_at', 'user_id']);
        }

        $table->update();
    }
}
