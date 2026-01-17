<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ModifyQuestionTypeInQuestions extends BaseMigration
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
        $table->changeColumn('question_type', 'string', [
            'limit' => 50,
            'null' => false,
            'default' => 'single_choice',
        ]);
        $table->update();
    }
}
