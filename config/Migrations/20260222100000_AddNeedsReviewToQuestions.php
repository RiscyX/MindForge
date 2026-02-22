<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNeedsReviewToQuestions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('questions');

        if (!$table->hasColumn('needs_review')) {
            $table->addColumn('needs_review', 'boolean', [
                'null' => false,
                'default' => false,
                'after' => 'is_active',
            ]);
            $table->update();
        }
    }
}
