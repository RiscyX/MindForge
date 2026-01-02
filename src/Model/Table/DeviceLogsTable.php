<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DeviceLogs Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\DeviceLog newEmptyEntity()
 * @method \App\Model\Entity\DeviceLog newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\DeviceLog> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DeviceLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\DeviceLog findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\DeviceLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\DeviceLog> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\DeviceLog|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\DeviceLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\DeviceLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DeviceLog>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DeviceLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DeviceLog> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DeviceLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DeviceLog>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\DeviceLog>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\DeviceLog> deleteManyOrFail(iterable $entities, array $options = [])
 */
class DeviceLogsTable extends Table
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

        $this->setTable('device_logs');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                ],
            ],
        ]);

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
            ->scalar('ip_address')
            ->maxLength('ip_address', 45)
            ->allowEmptyString('ip_address');

        $validator
            ->scalar('user_agent')
            ->allowEmptyString('user_agent');

        $validator
            ->integer('device_type')
            ->notEmptyString('device_type');

        $validator
            ->scalar('country')
            ->maxLength('country', 100)
            ->allowEmptyString('country');

        $validator
            ->scalar('city')
            ->maxLength('city', 100)
            ->allowEmptyString('city');

        $validator
            ->scalar('extra_info')
            ->maxLength('extra_info', 4294967295)
            ->allowEmptyString('extra_info');

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
