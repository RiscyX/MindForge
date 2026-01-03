<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AnswerTranslations Model
 *
 * @property \App\Model\Table\AnswersTable&\Cake\ORM\Association\BelongsTo $Answers
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 * @method \App\Model\Entity\AnswerTranslation newEmptyEntity()
 * @method \App\Model\Entity\AnswerTranslation newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\AnswerTranslation> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AnswerTranslation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AnswerTranslation findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\AnswerTranslation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\AnswerTranslation> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AnswerTranslation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\AnswerTranslation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\AnswerTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AnswerTranslation>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AnswerTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AnswerTranslation> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AnswerTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AnswerTranslation>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AnswerTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AnswerTranslation> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AnswerTranslationsTable extends Table
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

        $this->setTable('answer_translations');
        $this->setDisplayField('source_type');
        $this->setPrimaryKey('id');

        $this->belongsTo('Answers', [
            'foreignKey' => 'answer_id',
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
            ->nonNegativeInteger('answer_id')
            ->notEmptyString('answer_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->notEmptyString('language_id');

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

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
        $rules->add($rules->isUnique(['answer_id', 'language_id']), ['errorField' => 'answer_id']);
        $rules->add($rules->existsIn(['answer_id'], 'Answers'), ['errorField' => 'answer_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);

        return $rules;
    }
}
