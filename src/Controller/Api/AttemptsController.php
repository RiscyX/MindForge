<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Question;
use App\Service\AiService;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query\SelectQuery;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;
use function Cake\Core\env;

#[OA\Tag(name: 'Attempts', description: 'Quiz attempt endpoints')]
class AttemptsController extends AppController
{
    /**
     * Get attempt payload for quiz taking.
     *
     * @param string|null $id Attempt id.
     * @return void
     */
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
                'TestTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['TestTranslations.language_id' => $langId])
                    : $q,
                'Categories.CategoryTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['CategoryTranslations.language_id' => $langId])
                    : $q,
                'Difficulties.DifficultyTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['DifficultyTranslations.language_id' => $langId])
                    : $q,
            ])
            ->first();

        $questions = $this->fetchQuestionsForTest($testId, $langId, includeCorrect: false)->all()->toArray();
        $questions = $this->orderQuestionsForAttempt($questions, (int)$attempt->id);

        $payload = [
            'attempt' => $this->attemptSummary($attempt),
            'test' => $test ? $this->testSummary($test) : null,
            'questions' => array_map(
                fn($q) => $this->questionPayload($q, includeCorrect: false, attemptId: (int)$attempt->id),
                $questions,
            ),
        ];

        $this->jsonSuccess($payload);
    }

    /**
     * Submit answers for an in-progress attempt.
     *
     * @param string|null $id Attempt id.
     * @return void
     */
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
            $userAnswerPayload = null;
            $isCorrect = false;

            if ($questionType === Question::TYPE_MATCHING) {
                $pairsInput = is_array($raw) && isset($raw['pairs']) && is_array($raw['pairs']) ? $raw['pairs'] : [];
                [$isCorrect, $normalizedPairs] = $this->evaluateMatchingAnswer($question, $pairsInput);
                $encoded = json_encode(['pairs' => $normalizedPairs], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $userAnswerPayload = is_string($encoded) ? $encoded : null;
            } elseif ($questionType === Question::TYPE_TEXT) {
                $userAnswerText = trim((string)$rawText);
                $correctTexts = $this->correctTextsForQuestion($question);
                if ($userAnswerText !== '' && $correctTexts) {
                    $normalizedUser = $this->normalizeTextAnswerForCompare($userAnswerText);
                    $isCorrect = in_array($normalizedUser, $correctTexts, true);
                    if (!$isCorrect) {
                        $isCorrect = $this->evaluateTextAnswerWithAi(
                            $userId,
                            $question,
                            $userAnswerText,
                            $this->correctTextsForQuestion($question, forResponse: true),
                            (int)$langId,
                        );
                    }
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
                'user_answer_payload' => $userAnswerPayload,
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

    /**
     * Return review payload for a finished attempt.
     *
     * @param string|null $id Attempt id.
     * @return void
     */
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
            $userPayload = $attemptAnswer?->user_answer_payload !== null
                ? (string)$attemptAnswer->user_answer_payload
                : null;

            $answersPayload = [];
            $correctTexts = [];
            $matching = null;

            if ((string)$question->question_type === Question::TYPE_TEXT) {
                $correctTexts = $this->correctTextsForQuestion($question, forResponse: true);
            } elseif ((string)$question->question_type === Question::TYPE_MATCHING) {
                $allAnswers = [];
                foreach (($question->answers ?? []) as $ans) {
                    $content = '';
                    if (!empty($ans->answer_translations)) {
                        $content = (string)($ans->answer_translations[0]->content ?? '');
                    }
                    if ($content === '' && isset($ans->source_text)) {
                        $content = (string)$ans->source_text;
                    }

                    $allAnswers[(int)$ans->id] = [
                        'id' => (int)$ans->id,
                        'content' => $content,
                        'match_side' => (string)($ans->match_side ?? ''),
                        'match_group' => (int)($ans->match_group ?? 0),
                    ];
                }

                $leftItems = [];
                $rightItems = [];
                $correctPairs = [];
                foreach ($allAnswers as $aid => $row) {
                    if ($row['match_side'] === 'left') {
                        $leftItems[] = ['id' => $aid, 'content' => $row['content']];
                        foreach ($allAnswers as $rid => $candidate) {
                            if ($candidate['match_side'] !== 'right') {
                                continue;
                            }
                            if ($candidate['match_group'] > 0 && $candidate['match_group'] === $row['match_group']) {
                                $correctPairs[(string)$aid] = $rid;
                                break;
                            }
                        }
                    } elseif ($row['match_side'] === 'right') {
                        $rightItems[] = ['id' => $aid, 'content' => $row['content']];
                    }
                }

                $userPairs = [];
                if (is_string($userPayload) && $userPayload !== '') {
                    $decoded = json_decode($userPayload, true);
                    if (is_array($decoded) && isset($decoded['pairs']) && is_array($decoded['pairs'])) {
                        foreach ($decoded['pairs'] as $leftId => $rightId) {
                            if (is_numeric($leftId) && is_numeric($rightId)) {
                                $userPairs[(string)(int)$leftId] = (int)$rightId;
                            }
                        }
                    }
                }

                $matching = [
                    'left' => $leftItems,
                    'right' => $rightItems,
                    'user_pairs' => $userPairs,
                    'correct_pairs' => $correctPairs,
                ];
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
                    'payload' => $userPayload,
                    'is_correct' => $attemptAnswer ? (bool)$attemptAnswer->is_correct : false,
                ],
                'answers' => $answersPayload,
                'correct_texts' => $correctTexts,
                'matching' => $matching,
            ];
        }

        $this->jsonSuccess([
            'attempt' => $this->attemptSummary($attempt),
            'review' => $reviewItems,
        ]);
    }

    /**
     * Resolve review language id.
     *
     * @param int $attemptLanguageId Language id from attempt.
     * @return int|null
     */
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

    /**
     * Build the base question query for an attempt.
     *
     * @param int $testId Test id.
     * @param int|null $langId Selected language id.
     * @param bool $includeCorrect Include correctness metadata.
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function fetchQuestionsForTest(int $testId, ?int $langId, bool $includeCorrect): SelectQuery
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
                    ->select(['id', 'question_id', 'position', 'source_text', 'match_side'])
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

    /**
     * Convert a question entity to response payload.
     *
     * @param object $question Question entity.
     * @param bool $includeCorrect Include correctness metadata.
     * @param int|null $attemptId Attempt id for deterministic ordering.
     * @return array<string, mixed>
     */
    private function questionPayload(object $question, bool $includeCorrect, ?int $attemptId = null): array
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

        $answers = array_values((array)($question->answers ?? []));
        if ($attemptId !== null && $attemptId > 0) {
            usort($answers, static function ($a, $b) use ($attemptId, $question): int {
                $questionId = (int)($question->id ?? 0);
                $aId = (int)($a->id ?? 0);
                $bId = (int)($b->id ?? 0);
                $aKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $questionId . ':option:' . $aId);
                $bKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $questionId . ':option:' . $bId);

                if ($aKey === $bKey) {
                    return $aId <=> $bId;
                }

                return $aKey <=> $bKey;
            });
        }

        foreach ($answers as $ans) {
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
            $side = trim((string)($ans->match_side ?? ''));
            if ($side !== '') {
                $row['match_side'] = $side;
            }
            if ($includeCorrect) {
                $row['is_correct'] = (bool)$ans->is_correct;
                $group = $ans->match_group !== null ? (int)$ans->match_group : null;
                if ($group !== null && $group > 0) {
                    $row['match_group'] = $group;
                }
            }
            $payload['answers'][] = $row;
        }

        return $payload;
    }

    /**
     * Collect accepted answers for text questions.
     *
     * @param object $question Question entity.
     * @param bool $forResponse Return raw values for response payload.
     * @return array<int, string>
     */
    private function correctTextsForQuestion(object $question, bool $forResponse = false): array
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

            $texts[] = $forResponse ? $t : $this->normalizeTextAnswerForCompare($t);
        }

        return array_values(array_unique($texts));
    }

    /**
     * Normalize text answer for strict compare fallback.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalizeTextAnswerForCompare(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = preg_replace('/[\p{P}\p{S}]+/u', '', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param int $userId
     * @param object $question
     * @param string $userAnswer
     * @param array<int, string> $acceptedAnswers
     * @param int $langId
     * @return bool
     */
    private function evaluateTextAnswerWithAi(
        int $userId,
        object $question,
        string $userAnswer,
        array $acceptedAnswers,
        int $langId,
    ): bool {
        $limit = $this->getAiTextEvaluationLimitInfo($userId);
        if (!$limit['allowed']) {
            return false;
        }

        $questionText = '';
        if (!empty($question->question_translations)) {
            $questionText = trim((string)($question->question_translations[0]->content ?? ''));
        }

        $langCode = $this->resolveLanguageCode($langId);
        $outputLanguage = str_starts_with(strtolower($langCode), 'hu') ? 'Hungarian' : 'English';

        $payload = [
            'question_type' => 'text',
            'question' => $questionText,
            'accepted_answers' => array_values($acceptedAnswers),
            'user_answer' => $userAnswer,
            'instruction' => 'Decide if user answer should be accepted as semantically equivalent.',
            'output_language' => $outputLanguage,
        ];
        $prompt = (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $systemMessage = 'You validate short text quiz answers. Return ONLY strict JSON: '
            . '{"is_correct":true|false,"confidence":0..1,"reason":"short"}. '
            . 'Accept minor phrasing, synonyms, and grammar differences, but reject different meaning.';

        $aiRequests = $this->fetchTable('AiRequests');
        try {
            $ai = new AiService();
            $content = $ai->generateContent(
                $prompt,
                $systemMessage,
                0.0,
                ['response_format' => ['type' => 'json_object']],
            );

            $decoded = json_decode((string)$content, true);
            $isCorrect = is_array($decoded) && isset($decoded['is_correct'])
                ? (bool)$decoded['is_correct']
                : false;

            $outputPayload = json_encode(['raw' => (string)$content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $req = $aiRequests->newEntity([
                'user_id' => $userId,
                'language_id' => $langId > 0 ? $langId : null,
                'source_medium' => 'mobile_app',
                'source_reference' => 'question:' . (int)($question->id ?? 0),
                'type' => 'text_answer_evaluation',
                'input_payload' => $prompt,
                'output_payload' => is_string($outputPayload) ? $outputPayload : '{}',
                'status' => 'success',
            ]);
            $aiRequests->save($req);

            return $isCorrect;
        } catch (Throwable $e) {
            $errorPayload = json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $req = $aiRequests->newEntity([
                'user_id' => $userId,
                'language_id' => $langId > 0 ? $langId : null,
                'source_medium' => 'mobile_app',
                'source_reference' => 'question:' . (int)($question->id ?? 0),
                'type' => 'text_answer_evaluation',
                'input_payload' => $prompt,
                'output_payload' => is_string($errorPayload) ? $errorPayload : '{}',
                'status' => 'failed',
            ]);
            $aiRequests->save($req);

            return false;
        }
    }

    /**
     * Resolve language code by id.
     *
     * @param int $langId Language id.
     * @return string
     */
    private function resolveLanguageCode(int $langId): string
    {
        if ($langId <= 0) {
            return 'en';
        }

        $lang = $this->fetchTable('Languages')->find()
            ->select(['code'])
            ->where(['id' => $langId])
            ->first();

        return (string)($lang->code ?? 'en');
    }

    /**
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    private function getAiTextEvaluationLimitInfo(?int $userId): array
    {
        $dailyLimit = max(1, (int)env('AI_TEXT_EVALUATION_DAILY_LIMIT', '80'));
        if ($userId === null) {
            return [
                'allowed' => false,
                'used' => $dailyLimit,
                'limit' => $dailyLimit,
                'remaining' => 0,
                'resets_at_iso' => FrozenTime::tomorrow()->format('c'),
            ];
        }

        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();
        $aiRequests = $this->fetchTable('AiRequests');
        $used = (int)$aiRequests->find()
            ->where([
                'user_id' => $userId,
                'type' => 'text_answer_evaluation',
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();

        $remaining = max(0, $dailyLimit - $used);

        return [
            'allowed' => $remaining > 0,
            'used' => $used,
            'limit' => $dailyLimit,
            'remaining' => $remaining,
            'resets_at_iso' => $tomorrowStart->format('c'),
        ];
    }

    /**
     * @param object $question Question entity with answers.
     * @param array<mixed> $pairsInput Raw user pairs (leftAnswerId => rightAnswerId).
     * @return array{0: bool, 1: array<string,int>}
     */
    private function evaluateMatchingAnswer(object $question, array $pairsInput): array
    {
        $leftById = [];
        $rightById = [];
        foreach (array_values((array)($question->answers ?? [])) as $index => $answer) {
            $aid = (int)$answer->id;
            $side = trim((string)($answer->match_side ?? ''));
            if ($side === '') {
                $side = $index % 2 === 0 ? 'left' : 'right';
            }
            $group = (int)($answer->match_group ?? 0);
            if ($group <= 0) {
                $group = (int)floor($index / 2) + 1;
                $answer->match_group = $group;
            }
            if ($aid <= 0 || !in_array($side, ['left', 'right'], true)) {
                continue;
            }

            if ($side === 'left') {
                $leftById[$aid] = $answer;
            } else {
                $rightById[$aid] = $answer;
            }
        }

        $normalizedPairs = [];
        $allValid = !empty($leftById) && !empty($rightById) && count($leftById) === count($rightById);
        $seenRights = [];

        if ($allValid) {
            foreach ($leftById as $leftId => $leftAnswer) {
                $rawRight = $pairsInput[(string)$leftId] ?? ($pairsInput[$leftId] ?? null);
                $rightId = is_numeric($rawRight) ? (int)$rawRight : null;

                if (
                    $rightId === null
                    || $rightId <= 0
                    || !isset($rightById[$rightId])
                    || isset($seenRights[$rightId])
                ) {
                    $allValid = false;
                    break;
                }

                $seenRights[$rightId] = true;
                $normalizedPairs[(string)$leftId] = $rightId;

                $leftGroup = (int)($leftAnswer->match_group ?? 0);
                $rightGroup = (int)($rightById[$rightId]->match_group ?? 0);
                if ($leftGroup <= 0 || $rightGroup <= 0 || $leftGroup !== $rightGroup) {
                    $allValid = false;
                    break;
                }
            }
        }

        $isCorrect = $allValid && count($normalizedPairs) === count($leftById);

        return [$isCorrect, $normalizedPairs];
    }

    /**
     * @param array<int, object> $questions
     * @param int $attemptId
     * @return array<int, object>
     */
    private function orderQuestionsForAttempt(array $questions, int $attemptId): array
    {
        usort($questions, static function ($a, $b) use ($attemptId): int {
            $aId = (int)($a->id ?? 0);
            $bId = (int)($b->id ?? 0);
            $aKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $aId);
            $bKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $bId);

            if ($aKey === $bKey) {
                return $aId <=> $bId;
            }

            return $aKey <=> $bKey;
        });

        return $questions;
    }

    /**
     * Convert attempt entity to summary payload.
     *
     * @param object $attempt Attempt entity.
     * @return array<string, mixed>
     */
    private function attemptSummary(object $attempt): array
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

    /**
     * Convert test entity to summary payload.
     *
     * @param object $test Test entity.
     * @return array<string, mixed>
     */
    private function testSummary(object $test): array
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
