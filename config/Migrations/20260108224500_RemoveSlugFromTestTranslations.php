<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveSlugFromTestTranslations extends BaseMigration
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
        $table = $this->table('test_translations');
        
        // Remove the specific unique index by name
        if ($table->hasIndexByName('uq_test_trans_slug')) {
             $table->removeIndexByName('uq_test_trans_slug');
        } elseif ($table->hasIndex(['slug'])) {
             // Fallback if name check fails but index on column exists
             $table->removeIndex(['slug']);
        }

        if ($table->hasColumn('slug')) {
            $table->removeColumn('slug');
        }
        
        $table->update();
    }
}
