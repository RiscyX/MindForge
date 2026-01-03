<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ActivityLogs Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\ActivityLog newEmptyEntity()
 * @method \App\Model\Entity\ActivityLog newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\ActivityLog> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ActivityLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ActivityLog findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\ActivityLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\ActivityLog> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ActivityLog|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\ActivityLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\ActivityLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ActivityLog>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ActivityLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ActivityLog> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ActivityLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ActivityLog>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\ActivityLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\ActivityLog> deleteManyOrFail(iterable $entities, array $options = [])
 */
class ActivityLogsTable extends Table
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

        $this->setTable('activity_logs');
        $this->setDisplayField('action');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->allowEmptyString('user_id');

        $validator
            ->scalar('action')
            ->maxLength('action', 100)
            ->requirePresence('action', 'create')
            ->notEmptyString('action');

        $validator
            ->scalar('ip_address')
            ->maxLength('ip_address', 45)
            ->allowEmptyString('ip_address');

        $validator
            ->scalar('user_agent')
            ->allowEmptyString('user_agent');

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

        return $rules;
    }
}
