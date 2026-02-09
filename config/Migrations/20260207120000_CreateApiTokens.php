<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateApiTokens extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('api_tokens')) {
            $table = $this->table('api_tokens');

            $table
                ->addColumn('user_id', 'integer', [
                    'limit' => 10,
                    'signed' => false,
                    'null' => false,
                ])
                ->addColumn('token_id', 'string', [
                    'limit' => 64,
                    'null' => false,
                ])
                ->addColumn('token_hash', 'string', [
                    'limit' => 64,
                    'null' => false,
                ])
                ->addColumn('token_type', 'string', [
                    'limit' => 20,
                    'null' => false,
                ])
                ->addColumn('family_id', 'string', [
                    'limit' => 64,
                    'null' => false,
                ])
                ->addColumn('parent_token_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('replaced_by_token_id', 'integer', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('expires_at', 'datetime', [
                    'null' => false,
                ])
                ->addColumn('used_at', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('revoked_at', 'datetime', [
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('revoked_reason', 'string', [
                    'limit' => 50,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('issued_ip', 'string', [
                    'limit' => 45,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('issued_user_agent', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'null' => false,
                ])
                ->addColumn('updated_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                    'null' => false,
                ])
                ->addIndex(['token_id'], ['unique' => true])
                ->addIndex(['token_hash'], ['unique' => true])
                ->addIndex(['user_id', 'token_type', 'revoked_at'])
                ->addIndex(['family_id'])
                ->addIndex(['expires_at'])
                ->create();
        }

        $this->execute('ALTER TABLE `api_tokens` MODIFY `parent_token_id` INT(11) NULL, MODIFY `replaced_by_token_id` INT(11) NULL');

        $table = $this->table('api_tokens');
        if (!$table->hasForeignKey('user_id')) {
            $table->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ]);
        }

        if (!$table->hasForeignKey('parent_token_id')) {
            $table->addForeignKey('parent_token_id', 'api_tokens', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ]);
        }

        if (!$table->hasForeignKey('replaced_by_token_id')) {
            $table->addForeignKey('replaced_by_token_id', 'api_tokens', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if ($this->hasTable('api_tokens')) {
            $this->table('api_tokens')->drop()->save();
        }
    }
}
