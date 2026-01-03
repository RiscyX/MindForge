<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Test Entity
 *
 * @property int $id
 * @property int $category_id
 * @property int|null $difficulty_id
 * @property int|null $number_of_questions
 * @property bool $is_public
 * @property int|null $created_by
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\Category $category
 * @property \App\Model\Entity\Difficulty $difficulty
 * @property \App\Model\Entity\AiRequest[] $ai_requests
 * @property \App\Model\Entity\Question[] $questions
 * @property \App\Model\Entity\TestAttempt[] $test_attempts
 * @property \App\Model\Entity\TestTranslation[] $test_translations
 * @property \App\Model\Entity\UserFavoriteTest[] $user_favorite_tests
 */
class Test extends Entity
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
        'category_id' => true,
        'difficulty_id' => true,
        'number_of_questions' => true,
        'is_public' => true,
        'created_by' => true,
        'created_at' => true,
        'updated_at' => true,
        'category' => true,
        'difficulty' => true,
        'ai_requests' => true,
        'questions' => true,
        'test_attempts' => true,
        'test_translations' => true,
        'user_favorite_tests' => true,
    ];
}
