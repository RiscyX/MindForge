<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UserFavoriteTestsFixture
 */
class UserFavoriteTestsFixture extends TestFixture
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
                'created_at' => '2026-01-03 10:15:47',
            ],
        ];
        parent::init();
    }
}
