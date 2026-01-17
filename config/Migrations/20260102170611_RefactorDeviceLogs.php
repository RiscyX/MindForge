<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RefactorDeviceLogs extends BaseMigration
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
        if (!$this->hasTable('device_logs')) {
            return;
        }

        $table = $this->table('device_logs');

        foreach (['os', 'browser', 'is_mobile', 'is_tablet', 'is_desktop'] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }

        $deviceTypeOptions = [
            'default' => 2,
            'limit' => 1, // TINYINT
            'null' => false,
            'comment' => '0: Mobile, 1: Tablet, 2: Desktop',
            'after' => 'user_agent',
        ];

        if ($table->hasColumn('device_type')) {
            $table->changeColumn('device_type', 'integer', $deviceTypeOptions);
        } else {
            $table->addColumn('device_type', 'integer', $deviceTypeOptions);
        }

        $table->update();
    }
}
