<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AnswerTranslation Entity
 *
 * @property int $id
 * @property int $answer_id
 * @property int $language_id
 * @property string $content
 * @property string $source_type
 * @property int|null $created_by
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\Answer $answer
 * @property \App\Model\Entity\Language $language
 */
class AnswerTranslation extends Entity
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
        'answer_id' => true,
        'language_id' => true,
        'content' => true,
        'source_type' => true,
        'created_by' => true,
        'created_at' => true,
        'answer' => true,
        'language' => true,
    ];
}
