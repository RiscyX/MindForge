<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Answers Model
 *
 * @property \App\Model\Table\QuestionsTable&\Cake\ORM\Association\BelongsTo $Questions
 * @property \App\Model\Table\AnswerTranslationsTable&\Cake\ORM\Association\HasMany $AnswerTranslations
 * @property \App\Model\Table\TestAttemptAnswersTable&\Cake\ORM\Association\HasMany $TestAttemptAnswers
 * @method \App\Model\Entity\Answer newEmptyEntity()
 * @method \App\Model\Entity\Answer newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Answer> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Answer get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Answer findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Answer patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Answer> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Answer|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Answer saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Answer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Answer>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Answer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Answer> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Answer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Answer>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Answer>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Answer> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AnswersTable extends Table
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

        $this->setTable('answers');
        $this->setDisplayField('source_type');
        $this->setPrimaryKey('id');

        $this->belongsTo('Questions', [
            'foreignKey' => 'question_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('AnswerTranslations', [
            'foreignKey' => 'answer_id',
        ]);
        $this->hasMany('TestAttemptAnswers', [
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
            ->nonNegativeInteger('question_id')
            ->notEmptyString('question_id');

        $validator
            ->scalar('source_type')
            ->notEmptyString('source_type');

        $validator
            ->boolean('is_correct')
            ->notEmptyString('is_correct');

        $validator
            ->scalar('source_text')
            ->allowEmptyString('source_text');

        $validator
            ->nonNegativeInteger('position')
            ->allowEmptyString('position');

        $validator
            ->dateTime('created_at')
            ->notEmptyDateTime('created_at');

        $validator
            ->dateTime('updated_at')
            ->notEmptyDateTime('updated_at');

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
        $rules->add($rules->existsIn(['question_id'], 'Questions'), ['errorField' => 'question_id']);

        return $rules;
    }
}
