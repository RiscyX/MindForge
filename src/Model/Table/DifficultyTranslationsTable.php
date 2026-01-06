<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DifficultyTranslations Model
 *
 * @property \App\Model\Table\DifficultiesTable&\Cake\ORM\Association\BelongsTo $Difficulties
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 * @method \App\Model\Entity\DifficultyTranslation newEmptyEntity()
 * @method \App\Model\Entity\DifficultyTranslation newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\DifficultyTranslation> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DifficultyTranslation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\DifficultyTranslation findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\DifficultyTranslation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\DifficultyTranslation> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\DifficultyTranslation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\DifficultyTranslation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\DifficultyTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DifficultyTranslation>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DifficultyTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DifficultyTranslation> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DifficultyTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DifficultyTranslation>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DifficultyTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DifficultyTranslation> deleteManyOrFail(iterable $entities, array $options = [])
 */
class DifficultyTranslationsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('difficulty_translations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('Difficulties', [
            'foreignKey' => 'difficulty_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('difficulty_id')
            ->notEmptyString('difficulty_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->notEmptyString('language_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['difficulty_id'], 'Difficulties'), ['errorField' => 'difficulty_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);

        return $rules;
    }
}
