<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TestTranslation Entity
 *
 * @property int $id
 * @property int $test_id
 * @property int $language_id
 * @property string $title
 * @property string|null $description
 * @property int|null $translator_id

 * @property bool $is_complete
 * @property \Cake\I18n\DateTime|null $translated_at
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\Test $test
 * @property \App\Model\Entity\Language $language
 * @property \App\Model\Entity\User $translator
 */
class TestTranslation extends Entity
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
        'test_id' => true,
        'language_id' => true,
        'title' => true,
        'description' => true,
        'translator_id' => true,
        'is_complete' => true,
        'translated_at' => true,
        'created_at' => true,
        'updated_at' => true,
        'test' => true,
        'language' => true,
        'translator' => true,
    ];
}
