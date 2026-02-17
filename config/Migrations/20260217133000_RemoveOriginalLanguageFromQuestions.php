<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveOriginalLanguageFromQuestions extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('questions');

        if ($table->hasForeignKey('original_language_id')) {
            $table->dropForeignKey('original_language_id');
        }
        if ($table->hasIndex(['original_language_id'])) {
            $table->removeIndex(['original_language_id']);
        }
        if ($table->hasColumn('original_language_id')) {
            $table->removeColumn('original_language_id');
        }

        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('questions');

        if (!$table->hasColumn('original_language_id')) {
            $table->addColumn('original_language_id', 'integer', [
                'limit' => 10,
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'question_type',
            ]);
        }
        if (!$table->hasIndex(['original_language_id'])) {
            $table->addIndex(['original_language_id'], [
                'name' => 'fk_questions_original_language',
            ]);
        }
        if (!$table->hasForeignKey('original_language_id')) {
            $table->addForeignKey('original_language_id', 'languages', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'fk_questions_original_language',
            ]);
        }

        $table->update();
    }
}
