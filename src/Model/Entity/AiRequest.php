<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AiRequest Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $language_id
 * @property string|null $source_medium

 * @property string|null $source_reference
 * @property string $type
 * @property string|null $prompt_version
 * @property string|null $provider
 * @property string|null $model
 * @property int|null $duration_ms
 * @property int|null $prompt_tokens
 * @property int|null $completion_tokens
 * @property int|null $total_tokens
 * @property string|null $cost_usd
 * @property string|null $input_payload
 * @property string|null $output_payload
 * @property string $status
 * @property int|null $test_id
 * @property \Cake\I18n\DateTime|null $updated_at
 * @property \Cake\I18n\DateTime|null $started_at
 * @property \Cake\I18n\DateTime|null $finished_at
 * @property string|null $error_code
 * @property string|null $error_message
 * @property string|null $meta
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Test $test
 * @property \App\Model\Entity\Language $language
 */
class AiRequest extends Entity
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
        'user_id' => true,
        'language_id' => true,
        'source_medium' => true,
        'source_reference' => true,
        'type' => true,
        'prompt_version' => true,
        'provider' => true,
        'model' => true,
        'duration_ms' => true,
        'prompt_tokens' => true,
        'completion_tokens' => true,
        'total_tokens' => true,
        'cost_usd' => true,
        'input_payload' => true,
        'output_payload' => true,
        'status' => true,
        'test_id' => true,
        'updated_at' => true,
        'started_at' => true,
        'finished_at' => true,
        'error_code' => true,
        'error_message' => true,
        'meta' => true,
        'created_at' => true,
        'user' => true,
        'test' => true,
        'language' => true,
        'ai_request_assets' => true,
    ];
}
