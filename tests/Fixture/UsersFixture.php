<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
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
                'email' => 'Lorem ipsum dolor sit amet',
                'avatar_url' => 'Lorem ipsum dolor sit amet',
                'password_hash' => 'Lorem ipsum dolor sit amet',
                'role_id' => 1,
                'is_active' => 1,
                'is_blocked' => 1,
                'last_login_at' => '2025-12-16 14:09:46',
                'created_at' => '2025-12-16 14:09:46',
                'updated_at' => '2025-12-16 14:09:46',
            ],
        ];
        parent::init();
    }
}
