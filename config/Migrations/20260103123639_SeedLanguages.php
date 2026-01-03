<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeedLanguages extends BaseMigration
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
        $table = $this->table('languages');
        
        // Check if data already exists to avoid duplicate errors
        if ($this->isMigratingUp()) {
            $data = [
                [
                    'code' => 'hu_HU',
                    'name' => 'Magyar',
                ],
                [
                    'code' => 'en_US',
                    'name' => 'English',
                ],
            ];

            foreach ($data as $row) {
                $exists = $this->getAdapter()->fetchAll('SELECT count(*) as count FROM languages WHERE code = \'' . $row['code'] . '\'');
                if ($exists[0]['count'] == 0) {
                    $table->insert([$row])->save();
                }
            }
        }
    }
}
