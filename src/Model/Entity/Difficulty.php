<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Difficulty Entity
 *
 * @property int $id
 * @property string $name
 * @property int $level
 *
 * @property \App\Model\Entity\Question[] $questions
 * @property \App\Model\Entity\TestAttempt[] $test_attempts
 * @property \App\Model\Entity\Test[] $tests
 */
class Difficulty extends Entity
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
        'level' => true,
        'difficulty_translations' => true,
        'questions' => true,
        'test_attempts' => true,
        'tests' => true,
    ];
}
