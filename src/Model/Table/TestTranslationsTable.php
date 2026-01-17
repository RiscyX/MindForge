<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TestTranslations Model
 *
 * @property \App\Model\Table\TestsTable&\Cake\ORM\Association\BelongsTo $Tests
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Translators
 * @method \App\Model\Entity\TestTranslation newEmptyEntity()
 * @method \App\Model\Entity\TestTranslation newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\TestTranslation> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TestTranslation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TestTranslation findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\TestTranslation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\TestTranslation> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TestTranslation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\TestTranslation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\TestTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestTranslation>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestTranslation> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestTranslation>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\TestTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestTranslation> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TestTranslationsTable extends Table
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

        $this->setTable('test_translations');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('Tests', [
            'foreignKey' => 'test_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Translators', [
            'foreignKey' => 'translator_id',
            'className' => 'Users',
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
            ->notEmptyString('test_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->notEmptyString('language_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->nonNegativeInteger('translator_id')
            ->allowEmptyString('translator_id');

        $validator
            ->boolean('is_complete')
            ->notEmptyString('is_complete');

        $validator
            ->dateTime('translated_at')
            ->allowEmptyDateTime('translated_at');

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
        $rules->add($rules->isUnique(['test_id', 'language_id']), ['errorField' => 'test_id']);
        $rules->add($rules->existsIn(['test_id'], 'Tests'), ['errorField' => 'test_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);
        $rules->add($rules->existsIn(['translator_id'], 'Translators'), ['errorField' => 'translator_id']);

        return $rules;
    }
}
