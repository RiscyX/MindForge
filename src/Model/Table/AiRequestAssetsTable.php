<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AiRequestAssetsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('ai_request_assets');
        $this->setDisplayField('storage_path');
        $this->setPrimaryKey('id');

        $this->belongsTo('AiRequests', [
            'foreignKey' => 'ai_request_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('ai_request_id')
            ->notEmptyString('ai_request_id');

        $validator
            ->scalar('storage_path')
            ->maxLength('storage_path', 255)
            ->notEmptyString('storage_path');

        $validator
            ->scalar('mime_type')
            ->maxLength('mime_type', 100)
            ->notEmptyString('mime_type');

        $validator
            ->nonNegativeInteger('size_bytes')
            ->notEmptyString('size_bytes');

        $validator
            ->scalar('sha256')
            ->maxLength('sha256', 64)
            ->allowEmptyString('sha256');

        $validator
            ->dateTime('created_at')
            ->notEmptyDateTime('created_at');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['ai_request_id'], 'AiRequests'), ['errorField' => 'ai_request_id']);

        return $rules;
    }
}
