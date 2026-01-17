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
 * @property string|null $input_payload
 * @property string|null $output_payload
 * @property string $status
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
        'input_payload' => true,
        'output_payload' => true,
        'status' => true,
        'created_at' => true,
        'user' => true,
        'test' => true,
        'language' => true,
    ];
}
