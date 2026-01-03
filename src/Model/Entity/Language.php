<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Language Entity
 *
 * @property int $id
 * @property string $code
 * @property string $name
 *
 * @property \App\Model\Entity\AiRequest[] $ai_requests
 * @property \App\Model\Entity\AnswerTranslation[] $answer_translations
 * @property \App\Model\Entity\CategoryTranslation[] $category_translations
 * @property \App\Model\Entity\QuestionTranslation[] $question_translations
 * @property \App\Model\Entity\TestAttempt[] $test_attempts
 * @property \App\Model\Entity\TestTranslation[] $test_translations
 */
class Language extends Entity
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
        'code' => true,
        'name' => true,
        'ai_requests' => true,
        'answer_translations' => true,
        'category_translations' => true,
        'question_translations' => true,
        'test_attempts' => true,
        'test_translations' => true,
    ];
}
