<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAvatarUrlToUsers extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('users');

        if (!$table->hasColumn('avatar_url')) {
            $table->addColumn('avatar_url', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ]);
            $table->update();
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('users');

        if ($table->hasColumn('avatar_url')) {
            $table->removeColumn('avatar_url');
            $table->update();
        }
    }
}
