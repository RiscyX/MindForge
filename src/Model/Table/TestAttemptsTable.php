<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TestAttempts Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TestsTable&\Cake\ORM\Association\BelongsTo $Tests
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @property \App\Model\Table\DifficultiesTable&\Cake\ORM\Association\BelongsTo $Difficulties
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 * @property \App\Model\Table\TestAttemptAnswersTable&\Cake\ORM\Association\HasMany $TestAttemptAnswers
 * @method \App\Model\Entity\TestAttempt newEmptyEntity()
 * @method \App\Model\Entity\TestAttempt newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\TestAttempt> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TestAttempt get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TestAttempt findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\TestAttempt patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\TestAttempt> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TestAttempt|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\TestAttempt saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttempt>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttempt>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttempt>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttempt>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TestAttemptsTable extends Table
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

        $this->setTable('test_attempts');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Tests', [
            'foreignKey' => 'test_id',
        ]);
        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
        ]);
        $this->belongsTo('Difficulties', [
            'foreignKey' => 'difficulty_id',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
        ]);
        $this->hasMany('TestAttemptAnswers', [
            'foreignKey' => 'test_attempt_id',
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
            ->nonNegativeInteger('user_id')
            ->notEmptyString('user_id');

        $validator
            ->nonNegativeInteger('test_id')
            ->allowEmptyString('test_id');

        $validator
            ->nonNegativeInteger('category_id')
            ->allowEmptyString('category_id');

        $validator
            ->nonNegativeInteger('difficulty_id')
            ->allowEmptyString('difficulty_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->allowEmptyString('language_id');

        $validator
            ->dateTime('started_at')
            ->notEmptyDateTime('started_at');

        $validator
            ->dateTime('finished_at')
            ->allowEmptyDateTime('finished_at');

        $validator
            ->decimal('score')
            ->allowEmptyString('score');

        $validator
            ->nonNegativeInteger('total_questions')
            ->allowEmptyString('total_questions');

        $validator
            ->nonNegativeInteger('correct_answers')
            ->allowEmptyString('correct_answers');

        $validator
            ->dateTime('created_at')
            ->notEmptyDateTime('created_at');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['test_id'], 'Tests'), ['errorField' => 'test_id']);
        $rules->add($rules->existsIn(['category_id'], 'Categories'), ['errorField' => 'category_id']);
        $rules->add($rules->existsIn(['difficulty_id'], 'Difficulties'), ['errorField' => 'difficulty_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);

        return $rules;
    }
}
