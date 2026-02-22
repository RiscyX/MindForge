<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Builds the detail/status payload for a single AI generation request.
 *
 * Extracts the DTO assembly, language-filtered test query, and draft
 * resolution logic previously inlined in Api\CreatorAiController.
 */
class AiRequestDetailService
{
    use LocatorAwareTrait;

    /**
     * Build the full view payload for an AI request.
     *
     * @param object $req AiRequest entity.
     * @param int $userId Authenticated user id (for test ownership check).
     * @param int|null $langId Resolved language id (null = no filter).
     * @return array<string, mixed>
     */
    public function buildViewPayload(object $req, int $userId, ?int $langId): array
    {
        $payload = [
            'ai_request' => $this->aiRequestSummary($req),
        ];

        if ($req->test_id !== null) {
            $test = $this->loadTestWithTranslations((int)$req->test_id, $userId, $langId);
            if ($test) {
                $payload['test'] = $this->testSummary($test);
            }
        }

        if ((string)$req->status === 'success' && is_string($req->output_payload) && $req->output_payload !== '') {
            $draft = json_decode($req->output_payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($draft)) {
                $payload['draft'] = $draft;
            }
        }

        return $payload;
    }

    /**
     * Resolve the draft for the apply action.
     *
     * Decodes the stored output_payload and optionally merges a client-provided override.
     *
     * @param object $req AiRequest entity.
     * @param array|null $incomingDraft Client-provided draft override (or null).
     * @return array{ok: bool, draft: array<string, mixed>|null, error_code?: string, error_message?: string}
     */
    public function resolveDraft(object $req, ?array $incomingDraft): array
    {
        $decodedDraft = null;
        if (is_string($req->output_payload) && $req->output_payload !== '') {
            $decodedDraft = json_decode($req->output_payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedDraft = null;
            }
        }

        $draft = is_array($decodedDraft) ? $decodedDraft : null;

        // Allow clients (mobile/web) to apply an edited draft.
        if (is_array($incomingDraft) && $incomingDraft) {
            $draft = $incomingDraft;
        }

        if (!is_array($draft)) {
            return [
                'ok' => false,
                'draft' => null,
                'error_code' => 'DRAFT_INVALID',
                'error_message' => 'AI draft is not valid JSON.',
            ];
        }

        return ['ok' => true, 'draft' => $draft];
    }

    /**
     * Convert an AI request entity to a summary DTO.
     *
     * @param object $req AiRequest entity.
     * @return array<string, mixed>
     */
    private function aiRequestSummary(object $req): array
    {
        return [
            'id' => (int)$req->id,
            'status' => (string)$req->status,
            'test_id' => $req->test_id !== null ? (int)$req->test_id : null,
            'created_at' => $req->created_at?->format('c'),
            'updated_at' => $req->updated_at?->format('c'),
            'started_at' => $req->started_at?->format('c'),
            'finished_at' => $req->finished_at?->format('c'),
            'duration_ms' => $req->duration_ms !== null ? (int)$req->duration_ms : null,
            'prompt_tokens' => $req->prompt_tokens !== null ? (int)$req->prompt_tokens : null,
            'completion_tokens' => $req->completion_tokens !== null ? (int)$req->completion_tokens : null,
            'total_tokens' => $req->total_tokens !== null ? (int)$req->total_tokens : null,
            'cost_usd' => $req->cost_usd !== null ? (float)$req->cost_usd : null,
            'prompt_version' => $req->prompt_version,
            'error_code' => $req->error_code,
            'error_message' => $req->error_message,
        ];
    }

    /**
     * Load a test with language-filtered translations, scoped to owner.
     *
     * @param int $testId Test id.
     * @param int $userId Owner user id.
     * @param int|null $langId Language id filter (null = no filter).
     * @return object|null
     */
    private function loadTestWithTranslations(int $testId, int $userId, ?int $langId): ?object
    {
        return $this->fetchTable('Tests')->find()
            ->where([
                'Tests.id' => $testId,
                'Tests.created_by' => $userId,
            ])
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($langId) {
                    return $langId ? $q->where(['CategoryTranslations.language_id' => $langId]) : $q;
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($langId) {
                    return $langId ? $q->where(['DifficultyTranslations.language_id' => $langId]) : $q;
                },
                'TestTranslations' => function ($q) use ($langId) {
                    return $langId ? $q->where(['TestTranslations.language_id' => $langId]) : $q;
                },
            ])
            ->first();
    }

    /**
     * Convert a test entity to a summary DTO.
     *
     * @param object $test Test entity with translations loaded.
     * @return array<string, mixed>
     */
    private function testSummary(object $test): array
    {
        $tTrans = $test->test_translations[0] ?? null;
        $catTrans = $test->category?->category_translations[0] ?? null;
        $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;

        return [
            'id' => (int)$test->id,
            'title' => $tTrans?->title ?? 'Untitled Test',
            'description' => $tTrans?->description ?? '',
            'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
            'category' => $catTrans?->name ?? null,
            'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
            'difficulty' => $diffTrans?->name ?? null,
            'number_of_questions' => $test->number_of_questions !== null
                ? (int)$test->number_of_questions
                : null,
            'is_public' => (bool)$test->is_public,
        ];
    }
}
