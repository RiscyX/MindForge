<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * QuestionTranslations Model
 *
 * @property \App\Model\Table\QuestionsTable&\Cake\ORM\Association\BelongsTo $Questions
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 * @method \App\Model\Entity\QuestionTranslation newEmptyEntity()
 * @method \App\Model\Entity\QuestionTranslation newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\QuestionTranslation> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\QuestionTranslation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\QuestionTranslation findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\QuestionTranslation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\QuestionTranslation> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\QuestionTranslation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\QuestionTranslation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\QuestionTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuestionTranslation>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\QuestionTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuestionTranslation> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\QuestionTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuestionTranslation>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\QuestionTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuestionTranslation> deleteManyOrFail(iterable $entities, array $options = [])
 */
class QuestionTranslationsTable extends Table
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

        $this->setTable('question_translations');
        $this->setDisplayField('source_type');
        $this->setPrimaryKey('id');

        $this->belongsTo('Questions', [
            'foreignKey' => 'question_id',
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
            ->nonNegativeInteger('question_id')
            ->notEmptyString('question_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->notEmptyString('language_id');

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

        $validator
            ->scalar('explanation')
            ->allowEmptyString('explanation');

        $validator
            ->scalar('source_type')
            ->notEmptyString('source_type');

        $validator
            ->nonNegativeInteger('created_by')
            ->allowEmptyString('created_by');

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
        $rules->add($rules->isUnique(['question_id', 'language_id']), ['errorField' => 'question_id']);
        $rules->add($rules->existsIn(['question_id'], 'Questions'), ['errorField' => 'question_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);

        return $rules;
    }
}
