<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAttemptAnswerExplanations extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('attempt_answer_explanations');

        if (!$this->hasTable('attempt_answer_explanations')) {
            $table
                ->addColumn('test_attempt_answer_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('language_id', 'integer', ['null' => true, 'default' => null, 'signed' => false])
                ->addColumn('ai_request_id', 'integer', ['null' => true, 'default' => null, 'signed' => false])
                ->addColumn('source', 'string', ['limit' => 20, 'default' => 'ai', 'null' => false])
                ->addColumn('explanation_text', 'text', ['null' => false])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['test_attempt_answer_id', 'language_id'], ['unique' => true, 'name' => 'uq_attempt_answer_lang'])
                ->addIndex(['ai_request_id'])
                ->create();
        }

        $table = $this->table('attempt_answer_explanations');
        $table
            ->changeColumn('test_attempt_answer_id', 'integer', ['null' => false, 'signed' => false])
            ->changeColumn('language_id', 'integer', ['null' => true, 'default' => null, 'signed' => false])
            ->changeColumn('ai_request_id', 'integer', ['null' => true, 'default' => null, 'signed' => false]);

        if (!$table->hasIndex(['test_attempt_answer_id', 'language_id'])) {
            $table->addIndex(['test_attempt_answer_id', 'language_id'], ['unique' => true, 'name' => 'uq_attempt_answer_lang']);
        }
        if (!$table->hasIndex(['ai_request_id'])) {
            $table->addIndex(['ai_request_id']);
        }
        if (!$table->hasForeignKey('test_attempt_answer_id')) {
            $table->addForeignKey('test_attempt_answer_id', 'test_attempt_answers', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ]);
        }
        if (!$table->hasForeignKey('language_id')) {
            $table->addForeignKey('language_id', 'languages', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ]);
        }
        if (!$table->hasForeignKey('ai_request_id')) {
            $table->addForeignKey('ai_request_id', 'ai_requests', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ]);
        }
        $table->update();
    }
}
