<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Questions Model
 *
 * @property \App\Model\Table\TestsTable&\Cake\ORM\Association\BelongsTo $Tests
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @property \App\Model\Table\DifficultiesTable&\Cake\ORM\Association\BelongsTo $Difficulties
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $OriginalLanguages
 * @property \App\Model\Table\AnswersTable&\Cake\ORM\Association\HasMany $Answers
 * @property \App\Model\Table\QuestionTranslationsTable&\Cake\ORM\Association\HasMany $QuestionTranslations
 * @property \App\Model\Table\TestAttemptAnswersTable&\Cake\ORM\Association\HasMany $TestAttemptAnswers
 * @method \App\Model\Entity\Question newEmptyEntity()
 * @method \App\Model\Entity\Question newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Question> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Question get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Question findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Question patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Question> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Question|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Question saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question> deleteManyOrFail(iterable $entities, array $options = [])
 */
class QuestionsTable extends Table
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

        $this->setTable('questions');
        $this->setDisplayField('question_type');
        $this->setPrimaryKey('id');

        $this->belongsTo('Tests', [
            'foreignKey' => 'test_id',
        ]);
        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Difficulties', [
            'foreignKey' => 'difficulty_id',
        ]);
        $this->belongsTo('OriginalLanguages', [
            'foreignKey' => 'original_language_id',
            'className' => 'Languages',
        ]);
        $this->hasMany('Answers', [
            'foreignKey' => 'question_id',
        ]);
        $this->hasMany('QuestionTranslations', [
            'foreignKey' => 'question_id',
        ]);
        $this->hasMany('TestAttemptAnswers', [
            'foreignKey' => 'question_id',
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
            ->nonNegativeInteger('test_id')
            ->allowEmptyString('test_id');

        $validator
            ->nonNegativeInteger('category_id')
            ->notEmptyString('category_id');

        $validator
            ->nonNegativeInteger('difficulty_id')
            ->allowEmptyString('difficulty_id');

        $validator
            ->scalar('question_type')
            ->notEmptyString('question_type');

        $validator
            ->nonNegativeInteger('original_language_id')
            ->allowEmptyString('original_language_id');

        $validator
            ->scalar('source_type')
            ->notEmptyString('source_type');

        $validator
            ->nonNegativeInteger('created_by')
            ->allowEmptyString('created_by');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

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
        $rules->add($rules->existsIn(['test_id'], 'Tests'), ['errorField' => 'test_id']);
        $rules->add($rules->existsIn(['category_id'], 'Categories'), ['errorField' => 'category_id']);
        $rules->add($rules->existsIn(['difficulty_id'], 'Difficulties'), ['errorField' => 'difficulty_id']);
        $rules->add($rules->existsIn(
            ['original_language_id'],
            'OriginalLanguages',
        ), ['errorField' => 'original_language_id']);

        return $rules;
    }
}
