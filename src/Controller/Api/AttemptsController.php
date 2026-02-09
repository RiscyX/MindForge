<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Question;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query\SelectQuery;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;

#[OA\Tag(name: 'Attempts', description: 'Quiz attempt endpoints')]
class AttemptsController extends AppController
{
    #[OA\Get(
        path: '/api/v1/attempts/{id}',
        summary: 'Get an attempt with questions (no correct answers)',
        tags: ['Attempts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attempt payload',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'attempt', type: 'object'),
                        new OA\Property(property: 'test', type: 'object', nullable: true),
                        new OA\Property(property: 'questions', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Invalid attempt'),
        ],
    )]
    public function view(?string $id = null): void
    {
        $this->request->allowMethod(['get']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get((int)$id);
        if ((int)$attempt->user_id !== $userId) {
            $this->jsonError(403, 'FORBIDDEN', 'Attempt does not belong to user.');

            return;
        }

        $langId = $this->resolveLanguageId((int)($attempt->language_id ?? 0));
        $testId = (int)($attempt->test_id ?? 0);
        if ($testId <= 0) {
            $this->jsonError(422, 'ATTEMPT_INVALID', 'Attempt does not have a test_id.');

            return;
        }

        $test = $this->fetchTable('Tests')->find()
            ->where(['Tests.id' => $testId])
            ->contain([
                'TestTranslations' => fn(SelectQuery $q) => $langId ? $q->where(['TestTranslations.language_id' => $langId]) : $q,
                'Categories.CategoryTranslations' => fn(SelectQuery $q) => $langId ? $q->where(['CategoryTranslations.language_id' => $langId]) : $q,
                'Difficulties.DifficultyTranslations' => fn(SelectQuery $q) => $langId ? $q->where(['DifficultyTranslations.language_id' => $langId]) : $q,
            ])
            ->first();

        $questions = $this->fetchQuestionsForTest($testId, $langId, includeCorrect: false)->all()->toArray();

        $payload = [
            'attempt' => $this->attemptSummary($attempt),
            'test' => $test ? $this->testSummary($test) : null,
            'questions' => array_map(fn($q) => $this->questionPayload($q, includeCorrect: false), $questions),
        ];

        $this->jsonSuccess($payload);
    }

    #[OA\Post(
        path: '/api/v1/attempts/{id}/submit',
        summary: 'Submit answers for an attempt (finalizes score)',
        tags: ['Attempts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Submit result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'attempt', type: 'object'),
                        new OA\Property(property: 'submitted', type: 'boolean', example: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Invalid attempt or questions'),
            new OA\Response(response: 500, description: 'Submit failed'),
        ],
    )]
    public function submit(?string $id = null): void
    {
        $this->request->allowMethod(['post']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get((int)$id);
        if ((int)$attempt->user_id !== $userId) {
            $this->jsonError(403, 'FORBIDDEN', 'Attempt does not belong to user.');

            return;
        }

        $attemptAnswers = $this->fetchTable('TestAttemptAnswers');
        $existing = (int)$attemptAnswers->find()->where(['test_attempt_id' => (int)$attempt->id])->count();
        if ($attempt->finished_at !== null || $existing > 0) {
            // Idempotent: return the current attempt summary.
            $this->jsonSuccess([
                'attempt' => $this->attemptSummary($attempt),
                'submitted' => true,
            ]);

            return;
        }

        $testId = (int)($attempt->test_id ?? 0);
        if ($testId <= 0) {
            $this->jsonError(422, 'ATTEMPT_INVALID', 'Attempt does not have a test_id.');

            return;
        }

        $langId = $this->resolveLanguageId((int)($attempt->language_id ?? 0));
        $questions = $this->fetchQuestionsForTest($testId, $langId, includeCorrect: true)->all()->toArray();
        if (!$questions) {
            $this->jsonError(422, 'NO_ACTIVE_QUESTIONS', 'This test has no active questions.');

            return;
        }

        $input = $this->request->getData('answers');
        $input = is_array($input) ? $input : [];

        $now = FrozenTime::now();
        $entities = [];
        $correct = 0;

        foreach ($questions as $question) {
            $qid = (int)$question->id;
            $questionType = (string)$question->question_type;

            $raw = $input[$qid] ?? null;
            if (is_array($raw)) {
                $rawAnswerId = $raw['answer_id'] ?? null;
                $rawText = $raw['text'] ?? ($raw['user_answer_text'] ?? null);
            } else {
                $rawAnswerId = $raw;
                $rawText = $raw;
            }

            $chosenAnswerId = null;
            $userAnswerText = null;
            $isCorrect = false;

            if ($questionType === Question::TYPE_TEXT) {
                $userAnswerText = trim((string)$rawText);
                $correctTexts = $this->correctTextsForQuestion($question);
                if ($userAnswerText !== '' && $correctTexts) {
                    $isCorrect = in_array(strtolower($userAnswerText), $correctTexts, true);
                }
            } else {
                $chosenAnswerId = is_numeric($rawAnswerId) ? (int)$rawAnswerId : null;
                $answerCorrectMap = [];
                foreach (($question->answers ?? []) as $ans) {
                    $answerCorrectMap[(int)$ans->id] = (bool)$ans->is_correct;
                }
                if ($chosenAnswerId !== null && $chosenAnswerId > 0) {
                    if (!array_key_exists($chosenAnswerId, $answerCorrectMap)) {
                        $chosenAnswerId = null;
                        $isCorrect = false;
                    } else {
                        $isCorrect = (bool)$answerCorrectMap[$chosenAnswerId];
                    }
                }
            }

            if ($isCorrect) {
                $correct += 1;
            }

            $entities[] = $attemptAnswers->newEntity([
                'test_attempt_id' => (int)$attempt->id,
                'question_id' => $qid,
                'answer_id' => $chosenAnswerId,
                'user_answer_text' => $userAnswerText,
                'is_correct' => $isCorrect,
                'answered_at' => $now,
            ]);
        }

        $total = count($questions);
        $pct = $total > 0 ? $correct / $total * 100.0 : 0.0;
        $score = number_format($pct, 2, '.', '');

        $conn = $attempts->getConnection();
        $conn->begin();
        try {
            if (!$attemptAnswers->saveMany($entities)) {
                throw new RuntimeException('Failed to save attempt answers.');
            }

            $attempt->finished_at = $now;
            $attempt->total_questions = $total;
            $attempt->correct_answers = $correct;
            $attempt->score = $score;
            if (!$attempts->save($attempt)) {
                throw new RuntimeException('Failed to finalize attempt.');
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();

            $this->jsonError(500, 'SUBMIT_FAILED', 'Could not submit attempt answers.');

            return;
        }

        $this->jsonSuccess([
            'attempt' => $this->attemptSummary($attempt),
            'submitted' => true,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/attempts/{id}/review',
        summary: 'Get a review payload for a finished attempt',
        tags: ['Attempts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Review payload',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'attempt', type: 'object'),
                        new OA\Property(property: 'review', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 409, description: 'Attempt not finished'),
            new OA\Response(response: 422, description: 'Invalid attempt'),
        ],
    )]
    public function review(?string $id = null): void
    {
        $this->request->allowMethod(['get']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get((int)$id);
        if ((int)$attempt->user_id !== $userId) {
            $this->jsonError(403, 'FORBIDDEN', 'Attempt does not belong to user.');

            return;
        }

        if ($attempt->finished_at === null) {
            $this->jsonError(409, 'ATTEMPT_NOT_FINISHED', 'Attempt is not finished yet.');

            return;
        }

        $testId = (int)($attempt->test_id ?? 0);
        if ($testId <= 0) {
            $this->jsonError(422, 'ATTEMPT_INVALID', 'Attempt does not have a test_id.');

            return;
        }

        $langId = $this->resolveLanguageId((int)($attempt->language_id ?? 0));
        $questions = $this->fetchQuestionsForTest($testId, $langId, includeCorrect: true)->all()->toArray();

        $attemptAnswersTable = $this->fetchTable('TestAttemptAnswers');
        $attemptAnswers = $attemptAnswersTable->find()
            ->where(['test_attempt_id' => (int)$attempt->id])
            ->all()
            ->indexBy('question_id')
            ->toArray();

        $reviewItems = [];
        foreach ($questions as $question) {
            $qid = (int)$question->id;
            $attemptAnswer = $attemptAnswers[$qid] ?? null;
            $chosenId = $attemptAnswer?->answer_id !== null ? (int)$attemptAnswer->answer_id : null;
            $userText = $attemptAnswer?->user_answer_text !== null ? (string)$attemptAnswer->user_answer_text : null;

            $answersPayload = [];
            $correctTexts = [];

            if ((string)$question->question_type === Question::TYPE_TEXT) {
                $correctTexts = $this->correctTextsForQuestion($question, forResponse: true);
            } else {
                foreach (($question->answers ?? []) as $ans) {
                    $content = '';
                    if (!empty($ans->answer_translations)) {
                        $content = (string)($ans->answer_translations[0]->content ?? '');
                    }
                    if ($content === '' && isset($ans->source_text)) {
                        $content = (string)$ans->source_text;
                    }

                    $answersPayload[] = [
                        'id' => (int)$ans->id,
                        'content' => $content,
                        'is_correct' => (bool)$ans->is_correct,
                        'is_chosen' => ($chosenId !== null && (int)$ans->id === $chosenId),
                    ];
                }
            }

            $reviewItems[] = [
                'question' => $this->questionPayload($question, includeCorrect: false),
                'answer' => [
                    'answer_id' => $chosenId,
                    'text' => $userText,
                    'is_correct' => $attemptAnswer ? (bool)$attemptAnswer->is_correct : false,
                ],
                'answers' => $answersPayload,
                'correct_texts' => $correctTexts,
            ];
        }

        $this->jsonSuccess([
            'attempt' => $this->attemptSummary($attempt),
            'review' => $reviewItems,
        ]);
    }

    private function resolveLanguageId(int $attemptLanguageId): ?int
    {
        if ($attemptLanguageId > 0) {
            return $attemptLanguageId;
        }

        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $languages = $this->fetchTable('Languages');
        $lang = $languages->find()->where(['code LIKE' => $langCode . '%'])->first();
        if (!$lang) {
            $lang = $languages->find()->first();
        }

        return $lang?->id;
    }

    private function fetchQuestionsForTest(int $testId, ?int $langId, bool $includeCorrect)
    {
        $contain = [
            'QuestionTranslations' => function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['QuestionTranslations.language_id' => $langId]) : $q;
            },
        ];

        if ($includeCorrect) {
            $contain['Answers'] = function (SelectQuery $q) {
                return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
            };
            $contain['Answers.AnswerTranslations'] = function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['AnswerTranslations.language_id' => $langId]) : $q;
            };
        } else {
            $contain['Answers'] = function (SelectQuery $q) {
                return $q
                    ->select(['id', 'question_id', 'position', 'source_text'])
                    ->orderByAsc('Answers.position')
                    ->orderByAsc('Answers.id');
            };
            $contain['Answers.AnswerTranslations'] = function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['AnswerTranslations.language_id' => $langId]) : $q;
            };
        }

        return $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => $testId,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain($contain);
    }

    private function questionPayload($question, bool $includeCorrect): array
    {
        $content = '';
        if (!empty($question->question_translations)) {
            $content = (string)($question->question_translations[0]->content ?? '');
        }

        $payload = [
            'id' => (int)$question->id,
            'content' => $content,
            'type' => (string)$question->question_type,
            'position' => $question->position,
            'answers' => [],
        ];

        if ((string)$question->question_type === Question::TYPE_TEXT) {
            return $payload;
        }

        foreach (($question->answers ?? []) as $ans) {
            $aContent = '';
            if (!empty($ans->answer_translations)) {
                $aContent = (string)($ans->answer_translations[0]->content ?? '');
            }
            if ($aContent === '' && isset($ans->source_text)) {
                $aContent = (string)$ans->source_text;
            }

            $row = [
                'id' => (int)$ans->id,
                'content' => $aContent,
                'position' => $ans->position,
            ];
            if ($includeCorrect) {
                $row['is_correct'] = (bool)$ans->is_correct;
            }
            $payload['answers'][] = $row;
        }

        return $payload;
    }

    private function correctTextsForQuestion($question, bool $forResponse = false): array
    {
        $texts = [];
        foreach (($question->answers ?? []) as $ans) {
            if (!(bool)$ans->is_correct) {
                continue;
            }

            $t = '';
            if (!empty($ans->answer_translations)) {
                $t = (string)($ans->answer_translations[0]->content ?? '');
            }
            if ($t === '' && isset($ans->source_text)) {
                $t = (string)$ans->source_text;
            }
            $t = trim($t);
            if ($t === '') {
                continue;
            }

            $texts[] = $forResponse ? $t : strtolower($t);
        }

        return array_values(array_unique($texts));
    }

    private function attemptSummary($attempt): array
    {
        return [
            'id' => (int)$attempt->id,
            'test_id' => $attempt->test_id !== null ? (int)$attempt->test_id : null,
            'started_at' => $attempt->started_at?->format('c'),
            'finished_at' => $attempt->finished_at?->format('c'),
            'score' => $attempt->score !== null ? (float)$attempt->score : null,
            'total_questions' => $attempt->total_questions !== null ? (int)$attempt->total_questions : null,
            'correct_answers' => $attempt->correct_answers !== null ? (int)$attempt->correct_answers : null,
            'language_id' => $attempt->language_id !== null ? (int)$attempt->language_id : null,
        ];
    }

    private function testSummary($test): array
    {
        $translation = $test->test_translations[0] ?? null;
        $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
        $catTrans = $test->category?->category_translations[0] ?? null;

        return [
            'id' => (int)$test->id,
            'title' => $translation?->title ?? 'Untitled Test',
            'description' => $translation?->description ?? '',
            'difficulty' => $diffTrans?->name ?? null,
            'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
            'category' => $catTrans?->name ?? null,
            'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
        ];
    }
}
