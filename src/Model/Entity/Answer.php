<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Answer Entity
 *
 * @property int $id
 * @property int $question_id
 * @property string $source_type
 * @property bool $is_correct
 * @property string|null $match_side
 * @property int|null $match_group
 * @property string|null $source_text
 * @property int|null $position
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\Question $question
 * @property \App\Model\Entity\AnswerTranslation[] $answer_translations
 * @property \App\Model\Entity\TestAttemptAnswer[] $test_attempt_answers
 */
class Answer extends Entity
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
        'question_id' => true,
        'source_type' => true,
        'is_correct' => true,
        'match_side' => true,
        'match_group' => true,
        'source_text' => true,
        'position' => true,
        'created_at' => true,
        'updated_at' => true,
        'question' => true,
        'answer_translations' => true,
        'test_attempt_answers' => true,
    ];
}
