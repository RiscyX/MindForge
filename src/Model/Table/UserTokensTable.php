<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UserTokens Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\UserToken newEmptyEntity()
 * @method \App\Model\Entity\UserToken newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\UserToken> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UserToken get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\UserToken findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\UserToken patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\UserToken> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UserToken|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\UserToken saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\UserToken>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserToken>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserToken>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserToken> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserToken>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserToken>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\UserToken>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserToken> deleteManyOrFail(iterable $entities, array $options = [])
 */
class UserTokensTable extends Table
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

        $this->setTable('user_tokens');
        $this->setDisplayField('type');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->scalar('token')
            ->maxLength('token', 255)
            ->requirePresence('token', 'create')
            ->notEmptyString('token')
            ->add('token', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('type')
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->dateTime('expires_at')
            ->requirePresence('expires_at', 'create')
            ->notEmptyDateTime('expires_at');

        $validator
            ->dateTime('used_at')
            ->allowEmptyDateTime('used_at');

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
        $rules->add($rules->isUnique(['token']), ['errorField' => 'token']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
