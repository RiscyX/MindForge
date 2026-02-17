<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TestAttemptAnswer Entity
 *
 * @property int $id
 * @property int $test_attempt_id
 * @property int $question_id
 * @property int|null $answer_id
 * @property string|null $user_answer_text
 * @property string|null $user_answer_payload
 * @property bool $is_correct
 * @property \Cake\I18n\DateTime $answered_at
 *
 * @property \App\Model\Entity\TestAttempt $test_attempt
 * @property \App\Model\Entity\Question $question
 * @property \App\Model\Entity\Answer $answer
 * @property \App\Model\Entity\AttemptAnswerExplanation[] $attempt_answer_explanations
 */
class TestAttemptAnswer extends Entity
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
        'test_attempt_id' => true,
        'question_id' => true,
        'answer_id' => true,
        'user_answer_text' => true,
        'user_answer_payload' => true,
        'is_correct' => true,
        'answered_at' => true,
        'test_attempt' => true,
        'question' => true,
        'answer' => true,
        'attempt_answer_explanations' => true,
    ];
}
