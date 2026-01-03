<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tests Model
 *
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @property \App\Model\Table\DifficultiesTable&\Cake\ORM\Association\BelongsTo $Difficulties
 * @property \App\Model\Table\AiRequestsTable&\Cake\ORM\Association\HasMany $AiRequests
 * @property \App\Model\Table\QuestionsTable&\Cake\ORM\Association\HasMany $Questions
 * @property \App\Model\Table\TestAttemptsTable&\Cake\ORM\Association\HasMany $TestAttempts
 * @property \App\Model\Table\TestTranslationsTable&\Cake\ORM\Association\HasMany $TestTranslations
 * @property \App\Model\Table\UserFavoriteTestsTable&\Cake\ORM\Association\HasMany $UserFavoriteTests
 * @method \App\Model\Entity\Test newEmptyEntity()
 * @method \App\Model\Entity\Test newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Test> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Test get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Test findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Test patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Test> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Test|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Test saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Test>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Test>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Test>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Test> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Test>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Test>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Test>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Test> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TestsTable extends Table
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

        $this->setTable('tests');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Difficulties', [
            'foreignKey' => 'difficulty_id',
        ]);
        $this->hasMany('AiRequests', [
            'foreignKey' => 'test_id',
        ]);
        $this->hasMany('Questions', [
            'foreignKey' => 'test_id',
        ]);
        $this->hasMany('TestAttempts', [
            'foreignKey' => 'test_id',
        ]);
        $this->hasMany('TestTranslations', [
            'foreignKey' => 'test_id',
        ]);
        $this->hasMany('UserFavoriteTests', [
            'foreignKey' => 'test_id',
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
            ->nonNegativeInteger('category_id')
            ->notEmptyString('category_id');

        $validator
            ->nonNegativeInteger('difficulty_id')
            ->allowEmptyString('difficulty_id');

        $validator
            ->nonNegativeInteger('number_of_questions')
            ->allowEmptyString('number_of_questions');

        $validator
            ->boolean('is_public')
            ->notEmptyString('is_public');

        $validator
            ->nonNegativeInteger('created_by')
            ->allowEmptyString('created_by');

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
        $rules->add($rules->existsIn(['category_id'], 'Categories'), ['errorField' => 'category_id']);
        $rules->add($rules->existsIn(['difficulty_id'], 'Difficulties'), ['errorField' => 'difficulty_id']);

        return $rules;
    }
}
