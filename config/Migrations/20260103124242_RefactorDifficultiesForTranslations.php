<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RefactorDifficultiesForTranslations extends BaseMigration
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
        $table = $this->table('difficulty_translations');
        $table->addColumn('difficulty_id', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => false,
            'signed' => false,
        ]);
        $table->addColumn('language_id', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => false,
            'signed' => false,
        ]);
        $table->addColumn('name', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => false,
        ]);
        $table->addColumn('created_at', 'datetime', [
            'default' => 'CURRENT_TIMESTAMP',
            'null' => false,
        ]);
        $table->addForeignKey('difficulty_id', 'difficulties', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
        ]);
        $table->addForeignKey('language_id', 'languages', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
        ]);
        $table->create();

        $this->table('difficulties')
            ->removeColumn('name')
            ->update();
    }
}
