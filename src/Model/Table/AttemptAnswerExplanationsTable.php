<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AttemptAnswerExplanationsTable extends Table
{
    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('attempt_answer_explanations');
        $this->setPrimaryKey('id');
        $this->setDisplayField('id');

        $this->belongsTo('TestAttemptAnswers', [
            'foreignKey' => 'test_attempt_answer_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
        ]);
        $this->belongsTo('AiRequests', [
            'foreignKey' => 'ai_request_id',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('test_attempt_answer_id')
            ->notEmptyString('test_attempt_answer_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->allowEmptyString('language_id');

        $validator
            ->nonNegativeInteger('ai_request_id')
            ->allowEmptyString('ai_request_id');

        $validator
            ->scalar('source')
            ->maxLength('source', 20)
            ->notEmptyString('source');

        $validator
            ->scalar('explanation_text')
            ->notEmptyString('explanation_text');

        $validator
            ->dateTime('created_at')
            ->notEmptyDateTime('created_at');

        $validator
            ->dateTime('updated_at')
            ->allowEmptyDateTime('updated_at');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(
            $rules->existsIn(['test_attempt_answer_id'], 'TestAttemptAnswers'),
            ['errorField' => 'test_attempt_answer_id'],
        );
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);
        $rules->add($rules->existsIn(['ai_request_id'], 'AiRequests'), ['errorField' => 'ai_request_id']);
        $rules->add(
            $rules->isUnique(['test_attempt_answer_id', 'language_id']),
            ['errorField' => 'test_attempt_answer_id'],
        );

        return $rules;
    }
}
