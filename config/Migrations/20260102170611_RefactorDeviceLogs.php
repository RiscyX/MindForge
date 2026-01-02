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
        $table = $this->table('device_logs');
        $table
            ->removeColumn('os')
            ->removeColumn('browser')
            ->removeColumn('is_mobile')
            ->removeColumn('is_tablet')
            ->removeColumn('is_desktop')
            ->addColumn('device_type', 'integer', [
                'default' => 2,
                'limit' => 1, // TINYINT
                'null' => false,
                'comment' => '0: Mobile, 1: Tablet, 2: Desktop',
                'after' => 'user_agent'
            ])
            ->update();
    }
}
