<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TestAttemptsFixture
 */
class TestAttemptsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'user_id' => 1,
                'test_id' => 1,
                'category_id' => 1,
                'difficulty_id' => 1,
                'language_id' => 1,
                'started_at' => '2026-01-03 10:15:46',
                'finished_at' => '2026-01-03 10:15:46',
                'score' => 1.5,
                'total_questions' => 1,
                'correct_answers' => 1,
                'created_at' => '2026-01-03 10:15:46',
            ],
        ];
        parent::init();
    }
}
