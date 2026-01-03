<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Difficulties Model
 *
 * @property \App\Model\Table\QuestionsTable&\Cake\ORM\Association\HasMany $Questions
 * @property \App\Model\Table\TestAttemptsTable&\Cake\ORM\Association\HasMany $TestAttempts
 * @property \App\Model\Table\TestsTable&\Cake\ORM\Association\HasMany $Tests
 * @method \App\Model\Entity\Difficulty newEmptyEntity()
 * @method \App\Model\Entity\Difficulty newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Difficulty> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Difficulty get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Difficulty findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Difficulty patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Difficulty> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Difficulty|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Difficulty saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Difficulty>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Difficulty>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Difficulty>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Difficulty> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Difficulty>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Difficulty>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Difficulty>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Difficulty> deleteManyOrFail(iterable $entities, array $options = [])
 */
class DifficultiesTable extends Table
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

        $this->setTable('difficulties');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('Questions', [
            'foreignKey' => 'difficulty_id',
        ]);
        $this->hasMany('TestAttempts', [
            'foreignKey' => 'difficulty_id',
        ]);
        $this->hasMany('Tests', [
            'foreignKey' => 'difficulty_id',
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
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->nonNegativeInteger('level')
            ->requirePresence('level', 'create')
            ->notEmptyString('level')
            ->add('level', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

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
        $rules->add($rules->isUnique(['name']), ['errorField' => 'name']);
        $rules->add($rules->isUnique(['level']), ['errorField' => 'level']);

        return $rules;
    }
}
