<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ApiTokensTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('api_tokens');
        $this->setDisplayField('token_id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                    'updated_at' => 'always',
                ],
            ],
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('token_id')
            ->maxLength('token_id', 64)
            ->requirePresence('token_id', 'create')
            ->notEmptyString('token_id');

        $validator
            ->scalar('token_hash')
            ->lengthBetween('token_hash', [64, 64])
            ->requirePresence('token_hash', 'create')
            ->notEmptyString('token_hash');

        $validator
            ->scalar('token_type')
            ->inList('token_type', ['access', 'refresh'])
            ->requirePresence('token_type', 'create')
            ->notEmptyString('token_type');

        $validator
            ->scalar('family_id')
            ->maxLength('family_id', 64)
            ->requirePresence('family_id', 'create')
            ->notEmptyString('family_id');

        $validator
            ->dateTime('expires_at')
            ->requirePresence('expires_at', 'create')
            ->notEmptyDateTime('expires_at');

        $validator
            ->dateTime('used_at')
            ->allowEmptyDateTime('used_at');

        $validator
            ->dateTime('revoked_at')
            ->allowEmptyDateTime('revoked_at');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['token_id']), ['errorField' => 'token_id']);
        $rules->add($rules->isUnique(['token_hash']), ['errorField' => 'token_hash']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
