<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIspToDeviceLogs extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('device_logs');
        if (!$table->hasColumn('isp')) {
            $table
                ->addColumn('isp', 'string', [
                    'default' => null,
                    'limit' => 150,
                    'null' => true,
                    'after' => 'city',
                ])
                ->update();
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('device_logs');
        if ($table->hasColumn('isp')) {
            $table->removeColumn('isp')->update();
        }
    }
}
