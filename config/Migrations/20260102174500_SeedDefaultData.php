<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeedDefaultData extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // Insert Roles
        $roles = [
            [
                'id' => 1,
                'name' => 'Admin',
                'description' => 'Administrator with full access',
            ],
            [
                'id' => 2,
                'name' => 'Creator',
                'description' => 'Content creator',
            ],
            [
                'id' => 3,
                'name' => 'User',
                'description' => 'Standard user',
            ],
        ];

        $this->table('roles')->insert($roles)->save();

        // Insert Admin User
        $adminUser = [
            'email' => 'admin@local.com',
            'password_hash' => '$2a$12$0tj/5zbHCd4LkbRI/yT7.ef9AzR8gtX4HfTFhAlWTjZ9SPhcnPFKi',
            'role_id' => 1, // Admin
            'is_active' => 1,
            'is_blocked' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->table('users')->insert($adminUser)->save();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove Admin User
        $this->execute("DELETE FROM users WHERE email = 'admin@local.com'");

        // Remove Roles (only if they match the IDs we inserted, to be safe)
        $this->execute('DELETE FROM roles WHERE id IN (1, 2, 3)');
    }
}
