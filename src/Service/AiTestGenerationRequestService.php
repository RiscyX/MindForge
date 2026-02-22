<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use RuntimeException;
use Throwable;

/**
 * Handles creation of asynchronous AI test generation requests:
 * daily limit enforcement, input validation, entity persistence, and asset upload.
 *
 * Extracted from Api\CreatorAiController::createTestGeneration().
 */
class AiTestGenerationRequestService
{
    public const CODE_OK = 'ok';
    public const CODE_LIMIT_REACHED = 'AI_LIMIT_REACHED';
    public const CODE_CATEGORY_REQUIRED = 'CATEGORY_REQUIRED';
    public const CODE_CATEGORY_INVALID = 'CATEGORY_INVALID';
    public const CODE_DIFFICULTY_REQUIRED = 'DIFFICULTY_REQUIRED';
    public const CODE_DIFFICULTY_INVALID = 'DIFFICULTY_INVALID';
    public const CODE_REQUEST_CREATE_FAILED = 'REQUEST_CREATE_FAILED';
    public const CODE_UPLOAD_FAILED = 'UPLOAD_FAILED';

    /**
     * Create an async AI test generation request.
     *
     * @param int $userId Creator user ID.
     * @param string $prompt The generation prompt.
     * @param int|null $categoryId Category ID.
     * @param int|null $difficultyId Difficulty ID.
     * @param int|null $questionCount Requested number of questions (optional).
     * @param bool $isPublic Whether the generated test should be public.
     * @param int|null $languageId Resolved language ID.
     * @param array<string, \Psr\Http\Message\UploadedFileInterface|\Psr\Http\Message\UploadedFileInterface[]> $uploadedFiles
     * @return array{ok: bool, code: string, request_id?: int, status?: string, created_at?: string, resets_at?: string, error_message?: string}
     */
    public function create(
        int $userId,
        string $prompt,
        ?int $categoryId,
        ?int $difficultyId,
        ?int $questionCount,
        bool $isPublic,
        ?int $languageId,
        array $uploadedFiles = [],
    ): array {
        // Daily limit check
        $dailyLimit = max(1, (int)env('AI_TEST_GENERATION_DAILY_LIMIT', '20'));
        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();

        $aiRequests = TableRegistry::getTableLocator()->get('AiRequests');
        $used = (int)$aiRequests->find()
            ->where([
                'user_id' => $userId,
                'type IN' => ['test_generation_async', 'test_generation'],
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();

        if ($used >= $dailyLimit) {
            return [
                'ok' => false,
                'code' => self::CODE_LIMIT_REACHED,
                'resets_at' => $tomorrowStart->format('c'),
            ];
        }

        // Validate category
        if ($categoryId === null) {
            return ['ok' => false, 'code' => self::CODE_CATEGORY_REQUIRED];
        }
        if (!$this->isEntityValidAndActive('Categories', $categoryId)) {
            return ['ok' => false, 'code' => self::CODE_CATEGORY_INVALID];
        }

        // Validate difficulty
        if ($difficultyId === null) {
            return ['ok' => false, 'code' => self::CODE_DIFFICULTY_REQUIRED];
        }
        if (!$this->isEntityValidAndActive('Difficulties', $difficultyId)) {
            return ['ok' => false, 'code' => self::CODE_DIFFICULTY_INVALID];
        }

        // Create AI request entity
        $now = FrozenTime::now();
        $promptService = new AiQuizPromptService();
        $req = $aiRequests->newEntity([
            'user_id' => $userId,
            'language_id' => $languageId,
            'source_medium' => 'mobile_app',
            'source_reference' => 'creator_ai_test_generation',
            'type' => 'test_generation_async',
            'prompt_version' => $promptService->getGenerationPromptVersion(),
            'input_payload' => json_encode([
                'prompt' => $prompt,
                'category_id' => $categoryId,
                'difficulty_id' => $difficultyId,
                'question_count' => $questionCount,
                'is_public' => $isPublic,
            ], JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!$aiRequests->save($req)) {
            return ['ok' => false, 'code' => self::CODE_REQUEST_CREATE_FAILED];
        }

        // Handle asset uploads
        try {
            $assetService = new AssetUploadService();
            $assetService->saveUploadedAssets((int)$req->id, $uploadedFiles);
        } catch (RuntimeException $e) {
            $this->markRequestFailed($aiRequests, $req, 'UPLOAD_FAILED', $e->getMessage());

            return [
                'ok' => false,
                'code' => self::CODE_UPLOAD_FAILED,
                'error_message' => $e->getMessage(),
            ];
        } catch (Throwable) {
            $this->markRequestFailed($aiRequests, $req, 'UPLOAD_FAILED', 'Failed to store uploaded assets.');

            return [
                'ok' => false,
                'code' => self::CODE_UPLOAD_FAILED,
                'error_message' => 'Failed to store uploaded assets.',
            ];
        }

        return [
            'ok' => true,
            'code' => self::CODE_OK,
            'request_id' => (int)$req->id,
            'status' => (string)$req->status,
            'created_at' => $req->created_at?->format('c'),
        ];
    }

    /**
     * Check whether an entity exists and is active (if is_active column exists).
     *
     * @param string $tableName Table alias (e.g. 'Categories', 'Difficulties').
     * @param int $entityId
     * @return bool
     */
    private function isEntityValidAndActive(string $tableName, int $entityId): bool
    {
        $table = TableRegistry::getTableLocator()->get($tableName);
        $query = $table->find()->where([$tableName . '.id' => $entityId]);
        if ($table->getSchema()->hasColumn('is_active')) {
            $query->where([$tableName . '.is_active' => true]);
        }

        return $query->count() > 0;
    }

    /**
     * Mark an AI request as failed with error details.
     *
     * @param \Cake\ORM\Table $aiRequests
     * @param \Cake\Datasource\EntityInterface $req
     * @param string $errorCode
     * @param string $errorMessage
     * @return void
     */
    private function markRequestFailed(object $aiRequests, object $req, string $errorCode, string $errorMessage): void
    {
        $req->status = 'failed';
        $req->error_code = $errorCode;
        $req->error_message = $errorMessage;
        $req->updated_at = FrozenTime::now();
        $aiRequests->save($req);
    }
}
