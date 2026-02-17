<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AttemptAnswerExplanation Entity
 *
 * @property int $id
 * @property int $test_attempt_answer_id
 * @property int|null $language_id
 * @property int|null $ai_request_id
 * @property string $source
 * @property string $explanation_text
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime|null $updated_at
 */
class AttemptAnswerExplanation extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'test_attempt_answer_id' => true,
        'language_id' => true,
        'ai_request_id' => true,
        'source' => true,
        'explanation_text' => true,
        'created_at' => true,
        'updated_at' => true,
    ];
}
