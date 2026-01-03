<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CategoryTranslations Model
 *
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @property \App\Model\Table\LanguagesTable&\Cake\ORM\Association\BelongsTo $Languages
 * @method \App\Model\Entity\CategoryTranslation newEmptyEntity()
 * @method \App\Model\Entity\CategoryTranslation newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\CategoryTranslation> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CategoryTranslation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CategoryTranslation findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\CategoryTranslation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\CategoryTranslation> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\CategoryTranslation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\CategoryTranslation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\CategoryTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CategoryTranslation>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CategoryTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CategoryTranslation> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CategoryTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CategoryTranslation>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\CategoryTranslation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\CategoryTranslation> deleteManyOrFail(iterable $entities, array $options = [])
 */
class CategoryTranslationsTable extends Table
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

        $this->setTable('category_translations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Languages', [
            'foreignKey' => 'language_id',
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
            ->nonNegativeInteger('category_id')
            ->notEmptyString('category_id');

        $validator
            ->nonNegativeInteger('language_id')
            ->notEmptyString('language_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

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
        $rules->add($rules->isUnique(['category_id', 'language_id']), ['errorField' => 'category_id']);
        $rules->add($rules->existsIn(['category_id'], 'Categories'), ['errorField' => 'category_id']);
        $rules->add($rules->existsIn(['language_id'], 'Languages'), ['errorField' => 'language_id']);

        return $rules;
    }
}
