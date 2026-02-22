<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSet;

/**
 * Builds the filtered question list and dropdown options for the admin index page.
 */
class QuestionIndexService
{
    use LocatorAwareTrait;

    /**
     * Build a filtered question result set for the admin index.
     *
     * @param array<string, string> $filters Filter values (category, question_type, is_active, source_type).
     * @param int|null $languageId The resolved language id (null = all languages).
     * @return \Cake\ORM\ResultSet
     */
    public function getFilteredQuestions(array $filters, ?int $languageId): ResultSet
    {
        $questions = $this->fetchTable('Questions');

        $query = $questions
            ->find()
            ->contain([
                'Tests',
                'Categories.CategoryTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['CategoryTranslations.language_id' => $languageId]);
                },
                'Difficulties.DifficultyTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['DifficultyTranslations.language_id' => $languageId]);
                },
                'QuestionTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
            ])
            ->orderByAsc('Questions.id');

        if (($filters['category'] ?? '') !== '' && ctype_digit($filters['category'])) {
            $query->where(['Questions.category_id' => (int)$filters['category']]);
        }

        $questionTypes = [
            Question::TYPE_MULTIPLE_CHOICE,
            Question::TYPE_TRUE_FALSE,
            Question::TYPE_TEXT,
        ];
        if (in_array($filters['question_type'] ?? '', $questionTypes, true)) {
            $query->where(['Questions.question_type' => $filters['question_type']]);
        }

        if (in_array($filters['source_type'] ?? '', ['human', 'ai'], true)) {
            $query->where(['Questions.source_type' => $filters['source_type']]);
        }

        $isActive = $filters['is_active'] ?? '';
        if ($isActive === '1') {
            $query->where(['Questions.is_active' => true]);
        } elseif ($isActive === '0') {
            $query->where(['Questions.is_active' => false]);
        }

        $needsReview = $filters['needs_review'] ?? '';
        if ($needsReview === '1') {
            $query->where(['Questions.needs_review' => true]);
        } elseif ($needsReview === '0') {
            $query->where(['Questions.needs_review' => false]);
        }

        return $query->all();
    }

    /**
     * Build the category dropdown options for the question index filter.
     *
     * @param int|null $languageId The resolved language id.
     * @return array<int, string>
     */
    public function getCategoryOptions(?int $languageId): array
    {
        $questions = $this->fetchTable('Questions');

        return $questions->Categories->CategoryTranslations->find('list', [
            'keyField' => 'category_id',
            'valueField' => 'name',
        ])
            ->where($languageId === null ? [] : ['language_id' => $languageId])
            ->all()
            ->toArray();
    }

    /**
     * Build dropdown option arrays for the question index filter sidebar.
     *
     * @return array{questionTypeOptions: array<string, string>, sourceTypeOptions: array<string, string>, activeOptions: array<string, string>, needsReviewOptions: array<string, string>}
     */
    public function getStaticFilterOptions(): array
    {
        return [
            'questionTypeOptions' => [
                Question::TYPE_MULTIPLE_CHOICE => __('Multiple Choice'),
                Question::TYPE_TRUE_FALSE => __('True/False'),
                Question::TYPE_TEXT => __('Text'),
            ],
            'sourceTypeOptions' => [
                'human' => __('Human'),
                'ai' => __('AI'),
            ],
            'activeOptions' => [
                '1' => __('Active'),
                '0' => __('Inactive'),
            ],
            'needsReviewOptions' => [
                '1' => __('Needs Review'),
                '0' => __('Reviewed'),
            ],
        ];
    }
}
