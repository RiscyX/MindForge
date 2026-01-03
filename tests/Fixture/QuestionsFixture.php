<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * QuestionsFixture
 */
class QuestionsFixture extends TestFixture
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
                'test_id' => 1,
                'category_id' => 1,
                'difficulty_id' => 1,
                'question_type' => 'Lorem ipsum dolor sit amet',
                'original_language_id' => 1,
                'source_type' => 'Lorem ipsum dolor sit amet',
                'created_by' => 1,
                'is_active' => 1,
                'position' => 1,
                'created_at' => '2026-01-03 10:15:46',
                'updated_at' => '2026-01-03 10:15:46',
            ],
        ];
        parent::init();
    }
}
