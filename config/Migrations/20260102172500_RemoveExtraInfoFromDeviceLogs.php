<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveExtraInfoFromDeviceLogs extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('device_logs');
        $table->removeColumn('extra_info')
              ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('device_logs');
        $table->addColumn('extra_info', 'text', [
            'default' => null,
            'limit' => 4294967295,
            'null' => true,
        ])->update();
    }
}
