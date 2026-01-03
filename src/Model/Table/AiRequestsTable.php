<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AiRequests Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TestsTable&\Cake\ORM\Association\BelongsTo $Tests
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 * @method \App\Model\Entity\AiRequest newEmptyEntity()
 * @method \App\Model\Entity\AiRequest newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\AiRequest> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AiRequest get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\AiRequest findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\AiRequest patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\AiRequest> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AiRequest|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\AiRequest saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\AiRequest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AiRequest>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AiRequest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AiRequest> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AiRequest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AiRequest>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\AiRequest>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\AiRequest> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AiRequestsTable extends Table
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

        $this->setTable('ai_requests');
        $this->setDisplayField('source_medium');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Tests', [
            'foreignKey' => 'test_id',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
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
            ->allowEmptyString('test_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->allowEmptyString('language_id');

        $validator
            ->scalar('source_medium')
            ->notEmptyString('source_medium');

        $validator
            ->scalar('source_reference')
            ->maxLength('source_reference', 255)
            ->allowEmptyString('source_reference');

        $validator
            ->scalar('type')
            ->notEmptyString('type');

        $validator
            ->scalar('input_payload')
            ->maxLength('input_payload', 4294967295)
            ->allowEmptyString('input_payload');

        $validator
            ->scalar('output_payload')
            ->maxLength('output_payload', 4294967295)
            ->allowEmptyString('output_payload');

        $validator
            ->scalar('status')
            ->notEmptyString('status');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['test_id'], 'Tests'), ['errorField' => 'test_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);

        return $rules;
    }
}
