<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Question;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Questions Model
 *
 * @property \App\Model\Table\TestsTable&\Cake\ORM\Association\BelongsTo $Tests
 * @property \App\Model\Table\CategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 * @property \App\Model\Table\DifficultiesTable&\Cake\ORM\Association\BelongsTo $Difficulties
 * @property \App\Model\Table\AnswersTable&\Cake\ORM\Association\HasMany $Answers
 * @property \App\Model\Table\QuestionTranslationsTable&\Cake\ORM\Association\HasMany $QuestionTranslations
 * @property \App\Model\Table\TestAttemptAnswersTable&\Cake\ORM\Association\HasMany $TestAttemptAnswers
 * @method \App\Model\Entity\Question newEmptyEntity()
 * @method \App\Model\Entity\Question newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Question> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Question get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Question findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Question patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Question> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Question|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Question saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Question>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Question> deleteManyOrFail(iterable $entities, array $options = [])
 */
class QuestionsTable extends Table
{
    /**
     * Keep question difficulty in sync with parent test difficulty.
     *
     * @param \Cake\Event\EventInterface $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity Question entity.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$entity->has('test_id') || !is_numeric($entity->test_id)) {
            return;
        }

        $testId = (int)$entity->test_id;
        if ($testId <= 0) {
            return;
        }

        $test = $this->Tests->find()
            ->select(['difficulty_id'])
            ->where(['Tests.id' => $testId])
            ->enableHydration(false)
            ->first();

        if ($test !== null) {
            $entity->set('difficulty_id', $test['difficulty_id'] ?? null);
        }
    }

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('questions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Tests', [
            'foreignKey' => 'test_id',
        ]);
        $this->belongsTo('AiRequests', [
            'foreignKey' => 'ai_request_id',
        ]);
        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Difficulties', [
            'foreignKey' => 'difficulty_id',
        ]);
        $this->hasMany('Answers', [
            'foreignKey' => 'question_id',
        ]);
        $this->hasMany('QuestionTranslations', [
            'foreignKey' => 'question_id',
        ]);
        $this->hasMany('TestAttemptAnswers', [
            'foreignKey' => 'question_id',
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
            ->allowEmptyString('test_id');

        $validator
            ->nonNegativeInteger('ai_request_id')
            ->allowEmptyString('ai_request_id');

        $validator
            ->nonNegativeInteger('category_id')
            ->notEmptyString('category_id');

        $validator
            ->nonNegativeInteger('difficulty_id')
            ->allowEmptyString('difficulty_id');

        $validator
            ->scalar('question_type')
            ->maxLength('question_type', 50)
            ->notEmptyString('question_type')
            ->add('question_type', 'inList', [
                'rule' => ['inList', [
                    Question::TYPE_MULTIPLE_CHOICE,
                    Question::TYPE_TRUE_FALSE,
                    Question::TYPE_TEXT,
                    Question::TYPE_MATCHING,
                ]],
                'message' => __('Invalid question type.'),
            ]);

        $validator
            ->scalar('source_type')
            ->notEmptyString('source_type')
            ->add('source_type', 'inList', [
                'rule' => ['inList', ['human', 'ai']],
                'message' => __('Source type must be either human or ai.'),
            ]);

        $validator
            ->nonNegativeInteger('created_by')
            ->allowEmptyString('created_by');

        $validator
            ->boolean('is_active')
            ->notEmptyString('is_active');

        $validator
            ->boolean('needs_review')
            ->allowEmptyString('needs_review');

        $validator
            ->nonNegativeInteger('position')
            ->allowEmptyString('position');

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
        $rules->add($rules->existsIn(['test_id'], 'Tests'), ['errorField' => 'test_id']);
        $rules->add($rules->existsIn(['ai_request_id'], 'AiRequests'), ['errorField' => 'ai_request_id']);
        $rules->add($rules->existsIn(['category_id'], 'Categories'), ['errorField' => 'category_id']);
        $rules->add($rules->existsIn(['difficulty_id'], 'Difficulties'), ['errorField' => 'difficulty_id']);
        $rules->add(
            function (EntityInterface $entity): bool {
                $questionType = (string)($entity->get('question_type') ?? '');
                $hasMatchingMarkers = false;

                if ($entity->has('answers') && is_iterable($entity->answers)) {
                    foreach ($entity->answers as $answer) {
                        $side = trim((string)($answer->match_side ?? ''));
                        $group = $answer->match_group ?? null;
                        if ($side !== '' || $group !== null) {
                            $hasMatchingMarkers = true;
                            break;
                        }
                    }
                }

                if ($questionType === Question::TYPE_MATCHING || $hasMatchingMarkers) {
                    $answers = [];
                    if ($entity->has('answers') && is_iterable($entity->answers)) {
                        foreach ($entity->answers as $answer) {
                            $answers[] = $answer;
                        }
                    } elseif ($entity->has('id') && $entity->id !== null) {
                        $answers = $this->Answers->find()
                            ->select(['id', 'match_side', 'match_group'])
                            ->where(['Answers.question_id' => (int)$entity->id])
                            ->all()
                            ->toArray();
                    }

                    if (!$answers) {
                        return false;
                    }

                    $groups = [];
                    foreach ($answers as $answer) {
                        $side = trim((string)($answer->match_side ?? ''));
                        $group = $answer->match_group ?? null;
                        if ($side === '' || $group === null || !in_array($side, ['left', 'right'], true)) {
                            return false;
                        }

                        $groupId = (int)$group;
                        if ($groupId <= 0) {
                            return false;
                        }

                        if (!isset($groups[$groupId])) {
                            $groups[$groupId] = ['left' => 0, 'right' => 0];
                        }
                        $groups[$groupId][$side] += 1;
                    }

                    foreach ($groups as $counts) {
                        if ($counts['left'] !== 1 || $counts['right'] !== 1) {
                            return false;
                        }
                    }

                    return true;
                }

                if ($questionType === Question::TYPE_TEXT) {
                    $answers = [];
                    if ($entity->has('answers') && is_iterable($entity->answers)) {
                        foreach ($entity->answers as $answer) {
                            $answers[] = $answer;
                        }
                    } elseif ($entity->has('id') && $entity->id !== null) {
                        $answers = $this->Answers->find()
                            ->where(['Answers.question_id' => (int)$entity->id])
                            ->contain(['AnswerTranslations'])
                            ->all()
                            ->toArray();
                    }

                    if (!$answers) {
                        return false;
                    }

                    foreach ($answers as $answer) {
                        if (trim((string)($answer->source_text ?? '')) !== '') {
                            return true;
                        }

                        if (!empty($answer->answer_translations) && is_iterable($answer->answer_translations)) {
                            foreach ($answer->answer_translations as $translation) {
                                if (trim((string)($translation->content ?? '')) !== '') {
                                    return true;
                                }
                            }
                        }
                    }

                    return false;
                }

                if ($entity->has('answers') && is_iterable($entity->answers)) {
                    foreach ($entity->answers as $answer) {
                        if ((bool)($answer->is_correct ?? false)) {
                            return true;
                        }
                    }

                    return false;
                }

                if (!$entity->has('id') || $entity->id === null) {
                    return false;
                }

                return $this->Answers->find()
                    ->where([
                        'Answers.question_id' => (int)$entity->id,
                        'Answers.is_correct' => true,
                    ])
                    ->count() > 0;
            },
            'atLeastOneCorrectAnswer',
            [
                'errorField' => 'answers',
                'message' => __('Provide at least one correct/accepted answer, or valid matching pairs.'),
            ],
        );

        return $rules;
    }
}
