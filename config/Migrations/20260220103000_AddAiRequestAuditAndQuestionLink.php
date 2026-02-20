<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAiRequestAuditAndQuestionLink extends BaseMigration
{
    public function change(): void
    {
        $ai = $this->table('ai_requests');

        if (!$ai->hasColumn('prompt_version')) {
            $ai->addColumn('prompt_version', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
            ]);
        }

        if (!$ai->hasColumn('provider')) {
            $ai->addColumn('provider', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 100,
            ]);
        }

        if (!$ai->hasColumn('model')) {
            $ai->addColumn('model', 'string', [
                'null' => true,
                'default' => null,
                'limit' => 150,
            ]);
        }

        if (!$ai->hasColumn('duration_ms')) {
            $ai->addColumn('duration_ms', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => false,
            ]);
        }

        if (!$ai->hasColumn('prompt_tokens')) {
            $ai->addColumn('prompt_tokens', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => false,
            ]);
        }

        if (!$ai->hasColumn('completion_tokens')) {
            $ai->addColumn('completion_tokens', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => false,
            ]);
        }

        if (!$ai->hasColumn('total_tokens')) {
            $ai->addColumn('total_tokens', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => false,
            ]);
        }

        if (!$ai->hasColumn('cost_usd')) {
            $ai->addColumn('cost_usd', 'decimal', [
                'null' => true,
                'default' => null,
                'precision' => 12,
                'scale' => 6,
            ]);
        }

        if (!$ai->hasIndex(['user_id', 'created_at'])) {
            $ai->addIndex(['user_id', 'created_at']);
        }
        if (!$ai->hasIndex(['status', 'created_at'])) {
            $ai->addIndex(['status', 'created_at']);
        }
        if (!$ai->hasIndex(['type', 'created_at'])) {
            $ai->addIndex(['type', 'created_at']);
        }
        if (!$ai->hasIndex(['source_medium', 'created_at'])) {
            $ai->addIndex(['source_medium', 'created_at']);
        }
        if (!$ai->hasIndex(['prompt_version', 'created_at'])) {
            $ai->addIndex(['prompt_version', 'created_at']);
        }

        $ai->update();

        $questions = $this->table('questions');
        if (!$questions->hasColumn('ai_request_id')) {
            $questions->addColumn('ai_request_id', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => false,
            ]);
        }

        if (!$questions->hasIndex(['ai_request_id'])) {
            $questions->addIndex(['ai_request_id']);
        }
        $questions->update();

        if (!$questions->hasForeignKey('ai_request_id')) {
            $questions->addForeignKey('ai_request_id', 'ai_requests', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ]);
            $questions->update();
        }
    }
}
