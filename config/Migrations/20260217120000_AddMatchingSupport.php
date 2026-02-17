<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddMatchingSupport extends BaseMigration
{
    public function change(): void
    {
        $answers = $this->table('answers');
        if (!$answers->hasColumn('match_side')) {
            $answers->addColumn('match_side', 'string', [
                'limit' => 10,
                'null' => true,
                'default' => null,
                'after' => 'is_correct',
            ]);
        }
        if (!$answers->hasColumn('match_group')) {
            $answers->addColumn('match_group', 'integer', [
                'null' => true,
                'default' => null,
                'after' => 'match_side',
            ]);
        }

        if (!$answers->hasIndex(['question_id', 'match_side', 'position'])) {
            $answers->addIndex(['question_id', 'match_side', 'position'], [
                'name' => 'idx_answers_question_side_pos',
            ]);
        }
        if (!$answers->hasIndex(['question_id', 'match_group'])) {
            $answers->addIndex(['question_id', 'match_group'], [
                'name' => 'idx_answers_question_group',
            ]);
        }
        $answers->update();

        $attemptAnswers = $this->table('test_attempt_answers');
        if (!$attemptAnswers->hasColumn('user_answer_payload')) {
            $attemptAnswers->addColumn('user_answer_payload', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'user_answer_text',
            ]);
        }
        $attemptAnswers->update();

        $questions = $this->table('questions');
        if ($questions->hasColumn('question_type')) {
            $questions->changeColumn('question_type', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => 'multiple_choice',
            ]);
            $questions->update();
        }

        $this->execute("UPDATE questions SET question_type = 'multiple_choice' WHERE question_type = 'single_choice'");
    }
}
