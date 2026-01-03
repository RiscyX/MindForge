<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TestAttemptAnswers Model
 *
 * @property \App\Model\Table\TestAttemptsTable&\Cake\ORM\Association\BelongsTo $TestAttempts
 * @property \App\Model\Table\QuestionsTable&\Cake\ORM\Association\BelongsTo $Questions
 * @property \App\Model\Table\AnswersTable&\Cake\ORM\Association\BelongsTo $Answers
 * @method \App\Model\Entity\TestAttemptAnswer newEmptyEntity()
 * @method \App\Model\Entity\TestAttemptAnswer newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\TestAttemptAnswer> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TestAttemptAnswer get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TestAttemptAnswer findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\TestAttemptAnswer patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\TestAttemptAnswer> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TestAttemptAnswer|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\TestAttemptAnswer saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttemptAnswer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttemptAnswer>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttemptAnswer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttemptAnswer> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttemptAnswer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttemptAnswer>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestAttemptAnswer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttemptAnswer> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TestAttemptAnswersTable extends Table
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

        $this->setTable('test_attempt_answers');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('TestAttempts', [
            'foreignKey' => 'test_attempt_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Questions', [
            'foreignKey' => 'question_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Answers', [
            'foreignKey' => 'answer_id',
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
            ->nonNegativeInteger('test_attempt_id')
            ->notEmptyString('test_attempt_id');

        $validator
            ->nonNegativeInteger('question_id')
            ->notEmptyString('question_id');

        $validator
            ->nonNegativeInteger('answer_id')
            ->allowEmptyString('answer_id');

        $validator
            ->scalar('user_answer_text')
            ->allowEmptyString('user_answer_text');

        $validator
            ->boolean('is_correct')
            ->notEmptyString('is_correct');

        $validator
            ->dateTime('answered_at')
            ->notEmptyDateTime('answered_at');

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
        $rules->add($rules->existsIn(['test_attempt_id'], 'TestAttempts'), ['errorField' => 'test_attempt_id']);
        $rules->add($rules->existsIn(['question_id'], 'Questions'), ['errorField' => 'question_id']);
        $rules->add($rules->existsIn(['answer_id'], 'Answers'), ['errorField' => 'answer_id']);

        return $rules;
    }
}
