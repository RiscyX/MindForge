<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAsyncAiQuizGeneration extends BaseMigration
{
    public function change(): void
    {
        $ai = $this->table('ai_requests');

        if (!$ai->hasColumn('test_id')) {
            $ai->addColumn('test_id', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
            ]);
        }

        if (!$ai->hasColumn('updated_at')) {
            $ai->addColumn('updated_at', 'datetime', [
                'default' => null,
                'null' => true,
            ]);
        }

        if (!$ai->hasColumn('started_at')) {
            $ai->addColumn('started_at', 'datetime', [
                'default' => null,
                'null' => true,
            ]);
        }

        if (!$ai->hasColumn('finished_at')) {
            $ai->addColumn('finished_at', 'datetime', [
                'default' => null,
                'null' => true,
            ]);
        }

        if (!$ai->hasColumn('error_code')) {
            $ai->addColumn('error_code', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => true,
            ]);
        }

        if (!$ai->hasColumn('error_message')) {
            $ai->addColumn('error_message', 'text', [
                'default' => null,
                'null' => true,
            ]);
        }

        if (!$ai->hasColumn('meta')) {
            $ai->addColumn('meta', 'text', [
                'default' => null,
                'null' => true,
            ]);
        }

        if (!$ai->hasIndex(['status'])) {
            $ai->addIndex(['status']);
        }
        if (!$ai->hasIndex(['type'])) {
            $ai->addIndex(['type']);
        }
        if (!$ai->hasIndex(['user_id', 'created_at'])) {
            $ai->addIndex(['user_id', 'created_at']);
        }

        $ai->update();

        if (!$this->hasTable('ai_request_assets')) {
            $assets = $this->table('ai_request_assets');
            $assets
                ->addColumn('ai_request_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('storage_path', 'string', ['null' => false, 'limit' => 255])
                ->addColumn('mime_type', 'string', ['null' => false, 'limit' => 100])
                ->addColumn('size_bytes', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('sha256', 'string', ['null' => true, 'default' => null, 'limit' => 64])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['ai_request_id'])
                ->create();
        }
    }
}
