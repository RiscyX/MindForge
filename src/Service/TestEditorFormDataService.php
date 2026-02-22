<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Builds form/view-model data for test add/edit screens.
 */
class TestEditorFormDataService
{
    /**
     * @param int|null $languageId
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function buildFormMeta(?int $languageId, ?int $userId): array
    {
        $categories = [];
        $difficulties = [];

        if ($languageId) {
            $categories = TableRegistry::getTableLocator()->get('CategoryTranslations')->find('list', [
                'keyField' => 'category_id',
                'valueField' => 'name',
            ])
                ->where(['language_id' => $languageId])
                ->all();

            $difficulties = TableRegistry::getTableLocator()->get('DifficultyTranslations')->find('list', [
                'keyField' => 'difficulty_id',
                'valueField' => 'name',
            ])
                ->where(['language_id' => $languageId])
                ->all();
        }

        $languagesTable = TableRegistry::getTableLocator()->get('Languages');
        $languages = $languagesTable->find('list')->all();
        $languagesMeta = $languagesTable->find()
            ->select(['id', 'code', 'name'])
            ->orderByAsc('id')
            ->enableHydration(false)
            ->toArray();

        $aiGenerationLimit = (new AiRateLimitService())->getGenerationLimitInfo($userId);

        return [
            'categories' => $categories,
            'difficulties' => $difficulties,
            'languages' => $languages,
            'languagesMeta' => $languagesMeta,
            'aiGenerationLimit' => $aiGenerationLimit,
            'currentLanguageId' => $languageId,
        ];
    }

    /**
     * Prepare edit-mode payloads from persisted test entity.
     *
     * @param object $test
     * @return array{questionsData: array<int, array<string, mixed>>}
     */
    public function prepareEditPayload(object $test): array
    {
        $indexedTranslations = [];
        if (isset($test->test_translations) && is_iterable($test->test_translations)) {
            foreach ($test->test_translations as $translation) {
                $indexedTranslations[$translation->language_id] = $translation;
            }
        }
        $test->test_translations = $indexedTranslations;

        $questionsData = [];
        if (isset($test->questions) && is_iterable($test->questions)) {
            foreach ($test->questions as $question) {
                $qData = [
                    'id' => $question->id,
                    'type' => $question->question_type,
                    'source_type' => (string)$question->source_type,
                    'translations' => [],
                    'answers' => [],
                ];

                if (isset($question->question_translations) && is_iterable($question->question_translations)) {
                    foreach ($question->question_translations as $qt) {
                        $qData['translations'][$qt->language_id] = [
                            'id' => $qt->id,
                            'content' => $qt->content,
                            'explanation' => $qt->explanation,
                        ];
                    }
                }

                if (isset($question->answers) && is_iterable($question->answers)) {
                    foreach ($question->answers as $answer) {
                        $aData = [
                            'id' => $answer->id,
                            'source_type' => (string)$answer->source_type,
                            'is_correct' => $answer->is_correct,
                            'match_side' => $answer->match_side,
                            'match_group' => $answer->match_group,
                            'translations' => [],
                        ];
                        if (isset($answer->answer_translations) && is_iterable($answer->answer_translations)) {
                            foreach ($answer->answer_translations as $at) {
                                $aData['translations'][$at->language_id] = [
                                    'id' => $at->id,
                                    'content' => $at->content,
                                ];
                            }
                        }
                        $qData['answers'][] = $aData;
                    }
                }

                $questionsData[] = $qData;
            }
        }

        return ['questionsData' => $questionsData];
    }
}
