<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SchemaUpdates extends BaseMigration
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
        // Remove slug from category_translations
        $table = $this->table('category_translations');
        if ($table->hasColumn('slug')) {
            $table->removeColumn('slug');
        }
        $table->update();

        // Add username to users
        $usersTable = $this->table('users');
        if (!$usersTable->hasColumn('username')) {
            $usersTable->addColumn('username', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
                'after' => 'email',
            ]);
            $usersTable->addIndex(['username'], ['unique' => true]);
        }
        $usersTable->update();
    }
}
