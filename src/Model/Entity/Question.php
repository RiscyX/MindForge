<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Question Entity
 *
 * @property int $id
 * @property int|null $test_id
 * @property int $category_id
 * @property int|null $difficulty_id
 * @property string $question_type
 * @property int|null $original_language_id
 * @property string $source_type
 * @property int|null $created_by
 * @property bool $is_active
 * @property int|null $position
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 * @property string $type
 *
 * @property \App\Model\Entity\Test $test
 * @property \App\Model\Entity\Category $category
 * @property \App\Model\Entity\Difficulty $difficulty
 * @property \App\Model\Entity\Answer[] $answers
 * @property \App\Model\Entity\QuestionTranslation[] $question_translations
 * @property \App\Model\Entity\TestAttemptAnswer[] $test_attempt_answers
 */
class Question extends Entity
{
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TEXT = 'text';

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
        'test_id' => true,
        'category_id' => true,
        'difficulty_id' => true,
        'question_type' => true,
        'original_language_id' => true,
        'source_type' => true,
        'created_by' => true,
        'is_active' => true,
        'position' => true,
        'created_at' => true,
        'updated_at' => true,
        'test' => true,
        'category' => true,
        'difficulty' => true,
        'answers' => true,
        'question_translations' => true,
        'test_attempt_answers' => true,
    ];
}
