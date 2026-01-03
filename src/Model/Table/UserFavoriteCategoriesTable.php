<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UserFavoriteCategories Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @method \App\Model\Entity\UserFavoriteCategory newEmptyEntity()
 * @method \App\Model\Entity\UserFavoriteCategory newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\UserFavoriteCategory> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UserFavoriteCategory get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\UserFavoriteCategory findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\UserFavoriteCategory patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\UserFavoriteCategory> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UserFavoriteCategory|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\UserFavoriteCategory saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteCategory>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteCategory> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteCategory>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserFavoriteCategory>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserFavoriteCategory> deleteManyOrFail(iterable $entities, array $options = [])
 */
class UserFavoriteCategoriesTable extends Table
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

        $this->setTable('user_favorite_categories');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
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
            ->nonNegativeInteger('category_id')
            ->notEmptyString('category_id');

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
        $rules->add($rules->isUnique(['user_id', 'category_id']), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['category_id'], 'Categories'), ['errorField' => 'category_id']);

        return $rules;
    }
}
