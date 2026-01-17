<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTypeToQuestions extends BaseMigration
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
        $table = $this->table('questions');
        $table->addColumn('type', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => false,
        ]);
        $table->update();
    }
}
