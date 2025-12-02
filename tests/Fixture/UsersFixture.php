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
                'password_hash' => 'Lorem ipsum dolor sit amet',
                'role_id' => 1,
                'is_active' => 1,
                'is_blocked' => 1,
                'last_login_at' => '2025-12-02 19:25:58',
                'created_at' => '2025-12-02 19:25:58',
                'updated_at' => '2025-12-02 19:25:58',
                'avatar_url' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
