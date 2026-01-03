<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DifficultyTranslationsFixture
 */
class DifficultyTranslationsFixture extends TestFixture
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
                'difficulty_id' => 1,
                'language_id' => 1,
                'name' => 'Lorem ipsum dolor sit amet',
                'created_at' => '2026-01-03 12:44:38',
            ],
        ];
        parent::init();
    }
}
