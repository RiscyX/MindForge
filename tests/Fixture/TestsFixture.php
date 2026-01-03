<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TestsFixture
 */
class TestsFixture extends TestFixture
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
                'category_id' => 1,
                'difficulty_id' => 1,
                'number_of_questions' => 1,
                'is_public' => 1,
                'created_by' => 1,
                'created_at' => '2026-01-03 10:15:47',
                'updated_at' => '2026-01-03 10:15:47',
            ],
        ];
        parent::init();
    }
}
