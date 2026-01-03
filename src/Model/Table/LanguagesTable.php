<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Languages Model
 *
 * @property \App\Model\Table\AiRequestsTable&\Cake\ORM\Association\HasMany $AiRequests
 * @property \App\Model\Table\AnswerTranslationsTable&\Cake\ORM\Association\HasMany $AnswerTranslations
 * @property \App\Model\Table\CategoryTranslationsTable&\Cake\ORM\Association\HasMany $CategoryTranslations
 * @property \App\Model\Table\QuestionTranslationsTable&\Cake\ORM\Association\HasMany $QuestionTranslations
 * @property \App\Model\Table\TestAttemptsTable&\Cake\ORM\Association\HasMany $TestAttempts
 * @property \App\Model\Table\TestTranslationsTable&\Cake\ORM\Association\HasMany $TestTranslations
 * @method \App\Model\Entity\Language newEmptyEntity()
 * @method \App\Model\Entity\Language newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Language> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Language get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Language findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Language patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Language> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Language|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Language saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Language>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Language>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Language>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Language> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Language>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Language>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Language>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Language> deleteManyOrFail(iterable $entities, array $options = [])
 */
class LanguagesTable extends Table
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

        $this->setTable('languages');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('AiRequests', [
            'foreignKey' => 'language_id',
        ]);
        $this->hasMany('AnswerTranslations', [
            'foreignKey' => 'language_id',
        ]);
        $this->hasMany('CategoryTranslations', [
            'foreignKey' => 'language_id',
        ]);
        $this->hasMany('QuestionTranslations', [
            'foreignKey' => 'language_id',
        ]);
        $this->hasMany('TestAttempts', [
            'foreignKey' => 'language_id',
        ]);
        $this->hasMany('TestTranslations', [
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
            ->scalar('code')
            ->maxLength('code', 10)
            ->requirePresence('code', 'create')
            ->notEmptyString('code')
            ->add('code', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

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
        $rules->add($rules->isUnique(['code']), ['errorField' => 'code']);

        return $rules;
    }
}
