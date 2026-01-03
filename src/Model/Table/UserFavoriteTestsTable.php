<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UserFavoriteTests Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TestsTable&\Cake\ORM\Association\BelongsTo $Tests
 * @method \App\Model\Entity\UserFavoriteTest newEmptyEntity()
 * @method \App\Model\Entity\UserFavoriteTest newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\UserFavoriteTest> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UserFavoriteTest get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\UserFavoriteTest findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\UserFavoriteTest patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\UserFavoriteTest> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UserFavoriteTest|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\UserFavoriteTest saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteTest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteTest>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteTest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteTest> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteTest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteTest>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteTest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteTest> deleteManyOrFail(iterable $entities, array $options = [])
 */
class UserFavoriteTestsTable extends Table
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

        $this->setTable('user_favorite_tests');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Tests', [
            'foreignKey' => 'test_id',
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
            ->nonNegativeInteger('user_id')
            ->notEmptyString('user_id');

        $validator
            ->nonNegativeInteger('test_id')
            ->notEmptyString('test_id');

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
        $rules->add($rules->isUnique(['user_id', 'test_id']), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['test_id'], 'Tests'), ['errorField' => 'test_id']);

        return $rules;
    }
}
