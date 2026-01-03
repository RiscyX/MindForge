<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Category Entity
 *
 * @property int $id
 * @property bool $is_active
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\CategoryTranslation[] $category_translations
 * @property \App\Model\Entity\Question[] $questions
 * @property \App\Model\Entity\TestAttempt[] $test_attempts
 * @property \App\Model\Entity\Test[] $tests
 * @property \App\Model\Entity\UserFavoriteCategory[] $user_favorite_categories
 */
class Category extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'is_active' => true,
        'created_at' => true,
        'updated_at' => true,
        'category_translations' => true,
        'questions' => true,
        'test_attempts' => true,
        'tests' => true,
        'user_favorite_categories' => true,
    ];
}
