<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveTestIdFromAiRequests extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('ai_requests');

        // Drop foreign key if exists
        if ($table->hasForeignKey('test_id')) { // This might check by column name
             $table->dropForeignKey('test_id');
        }
        
        // OR safer to use names if known
        // $table->dropForeignKey('fk_ai_requests_tests'); 
        // CakePHP migrations usually handle dropForeignKey by column name. 
        // But let's try strict approach if I can interact with adapter.
        
        // Let's use the explicit name if possible or just column.
        // Adapter specific... 
        // using dropForeignKey with string usually expects the key name in Phinx?
        // In CakePHP Migrations (Phinx), dropForeignKey(['test_id']) drops by column.
        
        if ($table->hasForeignKey('test_id')) {
            $table->dropForeignKey('test_id');
        }
        
        // Drop index
        if ($table->hasIndex(['test_id', 'language_id'])) {
            $table->removeIndex(['test_id', 'language_id']);
        }
        
        if ($table->hasColumn('test_id')) {
            $table->removeColumn('test_id');
        }
        $table->update();
    }
}
