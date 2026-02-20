<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Question;
use App\Model\Entity\Role;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use RuntimeException;
use Throwable;

// phpcs:disable Generic.Files.LineLength.TooLong

class SeedDummyDataCommand extends Command
{
    private const DUMMY_EMAIL_DOMAIN = 'mindforge.local';
    private const DUMMY_EMAIL_PREFIX = 'dummy.';
    private const TEST_TITLE_PREFIX = '[DUMMY]';
    private const TRANSLATION_SOURCE = 'human';
    private const TOPICS = [
        'algebra',
        'geometry',
        'world_history',
        'biology',
        'chemistry',
        'physics',
        'literature',
        'programming',
        'cybersecurity',
        'economics',
    ];

    /** @var array<string, array<string, int>> */
    private const PROFILES = [
        'small' => [
            'creators' => 4,
            'players' => 80,
            'tests' => 120,
            'min_questions' => 10,
            'max_questions' => 18,
            'attempts' => 4500,
            'activity_logs' => 1600,
            'device_logs' => 900,
        ],
        'medium' => [
            'creators' => 10,
            'players' => 320,
            'tests' => 650,
            'min_questions' => 10,
            'max_questions' => 22,
            'attempts' => 35000,
            'activity_logs' => 12000,
            'device_logs' => 7000,
        ],
        'large' => [
            'creators' => 24,
            'players' => 1200,
            'tests' => 2200,
            'min_questions' => 9,
            'max_questions' => 20,
            'attempts' => 130000,
            'activity_logs' => 40000,
            'device_logs' => 28000,
        ],
        'xl' => [
            'creators' => 40,
            'players' => 2500,
            'tests' => 4200,
            'min_questions' => 8,
            'max_questions' => 18,
            'attempts' => 300000,
            'activity_logs' => 90000,
            'device_logs' => 60000,
        ],
    ];

    /**
     * @var array<int, array{questions: array<int, array<string, mixed>>}>
     */
    private array $testCatalog = [];

    /**
     * Generates dummy users, tests, attempts, and optional cleanup datasets.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $profileName = strtolower((string)$args->getOption('profile'));
        $profile = self::PROFILES[$profileName] ?? null;
        if ($profile === null) {
            $io->err('Invalid profile. Use one of: small, medium, large, xl');

            return static::CODE_ERROR;
        }

        $seed = (int)$args->getOption('seed');
        if ($seed <= 0) {
            $seed = random_int(1000, 9999999);
        }

        $only = strtolower((string)$args->getOption('only'));
        if (!in_array($only, ['all', 'tests', 'attempts'], true)) {
            $io->err('Invalid --only option. Use all|tests|attempts');

            return static::CODE_ERROR;
        }

        $cleanup = (bool)$args->getOption('cleanup');
        $cleanupRun = trim((string)$args->getOption('cleanup_run'));
        $cleanupOnly = (bool)$args->getOption('cleanup_only');
        $attemptsOverride = $args->getOption('attempts');
        if ($attemptsOverride !== null && $attemptsOverride !== '') {
            $override = (int)$attemptsOverride;
            if ($override <= 0) {
                $io->err('Invalid --attempts value. Use a positive integer.');

                return static::CODE_ERROR;
            }
            $profile['attempts'] = $override;
        }

        mt_srand($seed);

        $connection = ConnectionManager::get('default');
        $now = FrozenTime::now();
        $runToken = $now->format('YmdHis') . '-s' . $seed;

        $io->out('Seed Dummy Data');
        $io->out('Profile: ' . $profileName);
        $io->out('Seed: ' . (string)$seed);
        $io->out('Run token: ' . $runToken);

        if ($cleanup) {
            $deleted = $this->cleanupDummyData($connection, $io, $cleanupRun !== '' ? $cleanupRun : null);
            $io->success('Cleanup finished. Deleted rows: ' . (string)$deleted);
            if ($cleanupOnly) {
                return static::CODE_SUCCESS;
            }
        }

        [$languageIds, $categoryIds, $difficultyIds] = $this->loadRequiredIds($connection);

        $playerIds = [];
        if ($only !== 'attempts') {
            [$creatorIds, $playerIds] = $this->createDummyUsers($connection, $profile, $runToken, $io);
            $testIds = $this->createTestsWithQuestions($connection, $profile, $runToken, $languageIds, $categoryIds, $difficultyIds, $creatorIds, $io);

            $this->createFavorites($connection, $playerIds, $testIds, $categoryIds, $io);
            $this->createLogs($connection, $playerIds, $profile, $io);

            $io->success('Generated users/tests/questions/answers successfully.');
            $io->out('Run again with --only=attempts to add more attempt load if needed.');

            if ($only === 'tests') {
                return static::CODE_SUCCESS;
            }
        }

        if ($only === 'attempts') {
            $playerIds = $this->findDummyUsersByRole($connection, Role::USER);
            $testIds = $this->findDummyTestIds($connection);
            if (!$playerIds || !$testIds) {
                throw new RuntimeException('No dummy players or tests found. Seed tests first or run with --only=all.');
            }
            $this->buildCatalogFromDb($connection, $testIds);
        } else {
            $testIds = array_keys($this->testCatalog);
        }

        $this->createAttemptsAndAnswers($connection, $profile, $playerIds, $testIds, $languageIds, $io);

        $io->success('Dummy dataset generation finished.');
        $io->out('You can now benchmark listing, attempt flow, and stats endpoints.');

        return static::CODE_SUCCESS;
    }

    /**
     * Builds CLI options for profile-based seeding and cleanup.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('profile', [
            'short' => 'p',
            'help' => 'Dataset profile: small, medium, large, xl',
            'default' => 'small',
        ]);
        $parser->addOption('seed', [
            'short' => 's',
            'help' => 'Deterministic random seed',
            'default' => '0',
        ]);
        $parser->addOption('cleanup', [
            'short' => 'c',
            'help' => 'Delete previous dummy data first',
            'boolean' => true,
            'default' => false,
        ]);
        $parser->addOption('only', [
            'short' => 'o',
            'help' => 'Generate only one domain: all, tests, attempts',
            'default' => 'all',
        ]);
        $parser->addOption('attempts', [
            'help' => 'Override attempt row count for faster/slower runs',
            'default' => null,
        ]);
        $parser->addOption('cleanup_run', [
            'help' => 'Cleanup only one run token (requires --cleanup)',
            'default' => null,
        ]);
        $parser->addOption('cleanup_only', [
            'help' => 'Run cleanup only and exit',
            'boolean' => true,
            'default' => false,
        ]);

        return $parser;
    }

    /**
     * @return array{0: array<string, int>, 1: array<int>, 2: array<int>}
     */
    private function loadRequiredIds(Connection $connection): array
    {
        $languageRows = $connection->execute("SELECT id, code FROM languages WHERE code IN ('en_US', 'hu_HU')")->fetchAll('assoc');
        $languageIds = [];
        foreach ($languageRows as $row) {
            $languageIds[(string)$row['code']] = (int)$row['id'];
        }

        if (!isset($languageIds['en_US']) || !isset($languageIds['hu_HU'])) {
            throw new RuntimeException('Required languages are missing (en_US, hu_HU).');
        }

        $categoryRows = $connection->execute('SELECT id FROM categories WHERE is_active = 1 ORDER BY id ASC')->fetchAll('assoc');
        $categoryIds = array_values(array_map(static fn(array $row): int => (int)$row['id'], $categoryRows));
        if (!$categoryIds) {
            throw new RuntimeException('No active categories found.');
        }

        $difficultyRows = $connection->execute('SELECT id FROM difficulties ORDER BY id ASC')->fetchAll('assoc');
        $difficultyIds = array_values(array_map(static fn(array $row): int => (int)$row['id'], $difficultyRows));
        if (!$difficultyIds) {
            throw new RuntimeException('No difficulties found.');
        }

        return [$languageIds, $categoryIds, $difficultyIds];
    }

    /**
     * @param array<string, int> $profile
     * @return array{0: array<int>, 1: array<int>}
     */
    private function createDummyUsers(Connection $connection, array $profile, string $runToken, ConsoleIo $io): array
    {
        $now = FrozenTime::now()->format('Y-m-d H:i:s');

        $creatorRows = [];
        for ($i = 1; $i <= $profile['creators']; $i++) {
            $email = self::DUMMY_EMAIL_PREFIX . 'creator.' . $runToken . '.' . $i . '@' . self::DUMMY_EMAIL_DOMAIN;
            $creatorRows[] = [
                'email' => $email,
                'username' => 'dummy_creator_' . $runToken . '_' . $i,
                'password_hash' => '$2a$12$0tj/5zbHCd4LkbRI/yT7.ef9AzR8gtX4HfTFhAlWTjZ9SPhcnPFKi',
                'role_id' => Role::CREATOR,
                'is_active' => 1,
                'is_blocked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $playerRows = [];
        for ($i = 1; $i <= $profile['players']; $i++) {
            $email = self::DUMMY_EMAIL_PREFIX . 'user.' . $runToken . '.' . $i . '@' . self::DUMMY_EMAIL_DOMAIN;
            $playerRows[] = [
                'email' => $email,
                'username' => 'dummy_user_' . $runToken . '_' . $i,
                'password_hash' => '$2a$12$0tj/5zbHCd4LkbRI/yT7.ef9AzR8gtX4HfTFhAlWTjZ9SPhcnPFKi',
                'role_id' => Role::USER,
                'is_active' => 1,
                'is_blocked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $io->out('Creating dummy users...');
        $creatorIds = $this->insertRowsWithIds($connection, 'users', $creatorRows);
        $playerIds = $this->insertRowsWithIds($connection, 'users', $playerRows);

        $io->out('Creators: ' . count($creatorIds) . ', Players: ' . count($playerIds));

        return [$creatorIds, $playerIds];
    }

    /**
     * @param array<string, int> $profile
     * @param array<string, int> $languageIds
     * @param array<int> $categoryIds
     * @param array<int> $difficultyIds
     * @param array<int> $creatorIds
     * @return array<int>
     */
    private function createTestsWithQuestions(
        Connection $connection,
        array $profile,
        string $runToken,
        array $languageIds,
        array $categoryIds,
        array $difficultyIds,
        array $creatorIds,
        ConsoleIo $io,
    ): array {
        $io->out('Creating tests, questions, answers...');

        $testsTotal = $profile['tests'];
        $testIds = [];
        $now = FrozenTime::now()->format('Y-m-d H:i:s');
        $chunkSize = 40;

        for ($start = 1; $start <= $testsTotal; $start += $chunkSize) {
            $end = min($testsTotal, $start + $chunkSize - 1);
            $testsRows = [];
            $testMeta = [];

            for ($i = $start; $i <= $end; $i++) {
                $categoryId = $categoryIds[array_rand($categoryIds)];
                $difficultyId = $difficultyIds[array_rand($difficultyIds)];
                $creatorId = $creatorIds[array_rand($creatorIds)];
                $questionsCount = mt_rand($profile['min_questions'], $profile['max_questions']);
                $topic = self::TOPICS[array_rand(self::TOPICS)];

                $testsRows[] = [
                    'category_id' => $categoryId,
                    'difficulty_id' => $difficultyId,
                    'number_of_questions' => $questionsCount,
                    'is_public' => 1,
                    'created_by' => $creatorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $testMeta[] = [
                    'index' => $i,
                    'category_id' => $categoryId,
                    'creator_id' => $creatorId,
                    'questions' => $questionsCount,
                    'topic' => $topic,
                ];
            }

            $insertedTestIds = $this->insertRowsWithIds($connection, 'tests', $testsRows);
            $testIds = array_merge($testIds, $insertedTestIds);

            $testTranslationRows = [];
            foreach ($insertedTestIds as $pos => $testId) {
                $index = (int)$testMeta[$pos]['index'];
                $topic = (string)$testMeta[$pos]['topic'];
                $topicLabelEn = $this->topicLabel($topic, 'en');
                $topicLabelHu = $this->topicLabel($topic, 'hu');
                $baseTitle = self::TEST_TITLE_PREFIX . ' ' . $topicLabelEn . ' Quiz #' . $index;
                $this->testCatalog[$testId]['category_id'] = (int)$testMeta[$pos]['category_id'];
                $this->testCatalog[$testId]['difficulty_id'] = (int)$testsRows[$pos]['difficulty_id'];
                $this->testCatalog[$testId]['questions'] = $this->testCatalog[$testId]['questions'] ?? [];

                $testTranslationRows[] = [
                    'test_id' => $testId,
                    'language_id' => $languageIds['en_US'],
                    'title' => $baseTitle . ' (' . $runToken . ')',
                    'description' => 'Scenario-based quiz focused on ' . mb_strtolower($topicLabelEn) . ' for realistic performance testing.',
                    'translator_id' => $testMeta[$pos]['creator_id'],
                    'is_complete' => 1,
                    'translated_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $testTranslationRows[] = [
                    'test_id' => $testId,
                    'language_id' => $languageIds['hu_HU'],
                    'title' => self::TEST_TITLE_PREFIX . ' ' . $topicLabelHu . ' kviz #' . $index . ' (' . $runToken . ')',
                    'description' => 'Valoszeru teljesitmenyteszthez generalt, ' . mb_strtolower($topicLabelHu) . ' temaju kviz.',
                    'translator_id' => $testMeta[$pos]['creator_id'],
                    'is_complete' => 1,
                    'translated_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->insertRows($connection, 'test_translations', $testTranslationRows);

            $this->createQuestionsAndAnswersForTests($connection, $insertedTestIds, $testMeta, $languageIds);

            $io->out('Generated tests ' . $start . '-' . $end . ' / ' . $testsTotal);
        }

        return $testIds;
    }

    /**
     * @param array<int> $testIds
     * @param array<int, array<string, int|string>> $testMeta
     * @param array<string, int> $languageIds
     * @return void
     */
    private function createQuestionsAndAnswersForTests(Connection $connection, array $testIds, array $testMeta, array $languageIds): void
    {
        $now = FrozenTime::now()->format('Y-m-d H:i:s');

        $questionRows = [];
        $questionMeta = [];
        foreach ($testIds as $pos => $testId) {
            $categoryId = (int)$testMeta[$pos]['category_id'];
            $creatorId = (int)$testMeta[$pos]['creator_id'];
            $questionCount = (int)$testMeta[$pos]['questions'];
            $topic = (string)$testMeta[$pos]['topic'];

            for ($q = 1; $q <= $questionCount; $q++) {
                $type = $this->pickQuestionType();
                $questionRows[] = [
                    'test_id' => $testId,
                    'category_id' => $categoryId,
                    'difficulty_id' => null,
                    'question_type' => $type,
                    'source_type' => self::TRANSLATION_SOURCE,
                    'created_by' => $creatorId,
                    'is_active' => 1,
                    'position' => $q,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $questionMeta[] = [
                    'test_id' => $testId,
                    'q_pos' => $q,
                    'type' => $type,
                    'topic' => $topic,
                ];
            }
        }

        $questionIds = $this->insertRowsWithIds($connection, 'questions', $questionRows);

        $questionTranslations = [];
        $answerRows = [];
        $answerMeta = [];

        foreach ($questionIds as $idx => $questionId) {
            $meta = $questionMeta[$idx];
            $type = (string)$meta['type'];
            $topic = (string)$meta['topic'];
            $topicLabelEn = $this->topicLabel($topic, 'en');
            $topicLabelHu = $this->topicLabel($topic, 'hu');
            $questionTextBase = 'Scenario ' . $meta['q_pos'] . ' in ' . $topicLabelEn;

            $questionTranslations[] = [
                'question_id' => $questionId,
                'language_id' => $languageIds['en_US'],
                'content' => $questionTextBase . ': choose the most accurate answer.',
                'explanation' => 'Generated realistic dummy question for performance testing.',
                'source_type' => self::TRANSLATION_SOURCE,
                'created_by' => null,
                'created_at' => $now,
            ];
            $questionTranslations[] = [
                'question_id' => $questionId,
                'language_id' => $languageIds['hu_HU'],
                'content' => $topicLabelHu . ' tema, ' . $meta['q_pos'] . '. szituacio: valaszd a legpontosabb valaszt.',
                'explanation' => 'Valoszeru dummy kerdes teljesitmenyteszthez.',
                'source_type' => self::TRANSLATION_SOURCE,
                'created_by' => null,
                'created_at' => $now,
            ];

            $this->testCatalog[(int)$meta['test_id']]['questions'][$questionId] = [
                'type' => $type,
                'answers' => [],
                'correct_answer_id' => null,
                'correct_texts' => [],
                'matching_left' => [],
                'matching_right' => [],
            ];

            if ($type === Question::TYPE_TRUE_FALSE) {
                $correctIsTrue = (bool)mt_rand(0, 1);
                $answerRows[] = [
                    'question_id' => $questionId,
                    'source_type' => self::TRANSLATION_SOURCE,
                    'is_correct' => $correctIsTrue ? 1 : 0,
                    'match_side' => null,
                    'match_group' => null,
                    'source_text' => 'True',
                    'position' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $answerMeta[] = ['question_id' => $questionId, 'content_en' => 'True', 'content_hu' => 'Igaz', 'is_correct' => $correctIsTrue ? 1 : 0, 'type' => $type, 'match_side' => null, 'match_group' => null];

                $answerRows[] = [
                    'question_id' => $questionId,
                    'source_type' => self::TRANSLATION_SOURCE,
                    'is_correct' => $correctIsTrue ? 0 : 1,
                    'match_side' => null,
                    'match_group' => null,
                    'source_text' => 'False',
                    'position' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $answerMeta[] = ['question_id' => $questionId, 'content_en' => 'False', 'content_hu' => 'Hamis', 'is_correct' => $correctIsTrue ? 0 : 1, 'type' => $type, 'match_side' => null, 'match_group' => null];
            } elseif ($type === Question::TYPE_TEXT) {
                $accepted = str_replace('_', '-', $topic) . '-key-' . $questionId;
                $answerRows[] = [
                    'question_id' => $questionId,
                    'source_type' => self::TRANSLATION_SOURCE,
                    'is_correct' => 1,
                    'match_side' => null,
                    'match_group' => null,
                    'source_text' => $accepted,
                    'position' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $answerMeta[] = ['question_id' => $questionId, 'content_en' => $accepted, 'content_hu' => $accepted, 'is_correct' => 1, 'type' => $type, 'match_side' => null, 'match_group' => null];
            } elseif ($type === Question::TYPE_MATCHING) {
                $topicPairs = $this->matchingPairsForTopic($topic);
                $pairs = min(count($topicPairs), mt_rand(3, 5));
                for ($group = 1; $group <= $pairs; $group++) {
                    $pair = $topicPairs[$group - 1];
                    $left = $pair['left'];
                    $right = $pair['right'];

                    $answerRows[] = [
                        'question_id' => $questionId,
                        'source_type' => self::TRANSLATION_SOURCE,
                        'is_correct' => 1,
                        'match_side' => 'left',
                        'match_group' => $group,
                        'source_text' => $left,
                        'position' => ($group * 2) - 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $answerMeta[] = ['question_id' => $questionId, 'content_en' => $left, 'content_hu' => $left, 'is_correct' => 1, 'type' => $type, 'match_side' => 'left', 'match_group' => $group];

                    $answerRows[] = [
                        'question_id' => $questionId,
                        'source_type' => self::TRANSLATION_SOURCE,
                        'is_correct' => 1,
                        'match_side' => 'right',
                        'match_group' => $group,
                        'source_text' => $right,
                        'position' => $group * 2,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $answerMeta[] = ['question_id' => $questionId, 'content_en' => $right, 'content_hu' => $right, 'is_correct' => 1, 'type' => $type, 'match_side' => 'right', 'match_group' => $group];
                }
            } else {
                $answersCount = mt_rand(3, 5);
                $correctIndex = mt_rand(1, $answersCount);
                for ($a = 1; $a <= $answersCount; $a++) {
                    $content = $a === $correctIndex
                        ? $this->correctOptionForTopic($topic, $meta['q_pos'])
                        : $this->distractorOptionForTopic($topic, $a, $meta['q_pos']);
                    $isCorrect = $a === $correctIndex ? 1 : 0;
                    $answerRows[] = [
                        'question_id' => $questionId,
                        'source_type' => self::TRANSLATION_SOURCE,
                        'is_correct' => $isCorrect,
                        'match_side' => null,
                        'match_group' => null,
                        'source_text' => $content,
                        'position' => $a,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $answerMeta[] = ['question_id' => $questionId, 'content_en' => $content, 'content_hu' => $content, 'is_correct' => $isCorrect, 'type' => $type, 'match_side' => null, 'match_group' => null];
                }
            }
        }

        $this->insertRows($connection, 'question_translations', $questionTranslations);

        $answerIds = $this->insertRowsWithIds($connection, 'answers', $answerRows);
        $answerTranslations = [];
        foreach ($answerIds as $i => $answerId) {
            $meta = $answerMeta[$i];

            $answerTranslations[] = [
                'answer_id' => $answerId,
                'language_id' => $languageIds['en_US'],
                'content' => (string)$meta['content_en'],
                'source_type' => self::TRANSLATION_SOURCE,
                'created_by' => null,
                'created_at' => $now,
            ];
            $answerTranslations[] = [
                'answer_id' => $answerId,
                'language_id' => $languageIds['hu_HU'],
                'content' => (string)$meta['content_hu'],
                'source_type' => self::TRANSLATION_SOURCE,
                'created_by' => null,
                'created_at' => $now,
            ];
        }
        $this->insertRows($connection, 'answer_translations', $answerTranslations);

        $questionToTest = [];
        foreach ($questionIds as $idx => $qid) {
            $questionToTest[$qid] = (int)$questionMeta[$idx]['test_id'];
        }

        foreach ($answerIds as $i => $answerId) {
            $meta = $answerMeta[$i];
            $questionId = (int)$meta['question_id'];
            $testId = (int)$questionToTest[$questionId];

            $questionRef = &$this->testCatalog[$testId]['questions'][$questionId];
            $questionRef['answers'][] = [
                'id' => $answerId,
                'is_correct' => (int)$meta['is_correct'] === 1,
                'match_side' => $meta['match_side'],
                'match_group' => $meta['match_group'],
                'source_text' => $meta['content_en'],
            ];

            if ((int)$meta['is_correct'] === 1 && (string)$meta['type'] !== Question::TYPE_MATCHING && (string)$meta['type'] !== Question::TYPE_TEXT) {
                $questionRef['correct_answer_id'] = $answerId;
            }
            if ((string)$meta['type'] === Question::TYPE_TEXT && (int)$meta['is_correct'] === 1) {
                $questionRef['correct_texts'][] = (string)$meta['content_en'];
            }
            if ((string)$meta['type'] === Question::TYPE_MATCHING) {
                if ($meta['match_side'] === 'left') {
                    $questionRef['matching_left'][(int)$answerId] = (int)$meta['match_group'];
                }
                if ($meta['match_side'] === 'right') {
                    $questionRef['matching_right'][(int)$answerId] = (int)$meta['match_group'];
                }
            }
            unset($questionRef);
        }
    }

    /**
     * @param array<int> $playerIds
     * @param array<int> $testIds
     * @param array<int> $categoryIds
     * @return void
     */
    private function createFavorites(Connection $connection, array $playerIds, array $testIds, array $categoryIds, ConsoleIo $io): void
    {
        $io->out('Creating favorites...');
        $now = FrozenTime::now()->format('Y-m-d H:i:s');

        $favCategories = [];
        $favTests = [];
        foreach ($playerIds as $userId) {
            $catPick = min(count($categoryIds), mt_rand(1, 3));
            $pickedCats = (array)array_rand(array_flip($categoryIds), $catPick);
            foreach ($pickedCats as $categoryId) {
                $favCategories[] = [
                    'user_id' => $userId,
                    'category_id' => (int)$categoryId,
                    'created_at' => $now,
                ];
            }

            $testPick = min(count($testIds), mt_rand(4, 14));
            if ($testPick <= 0) {
                continue;
            }
            $pickedTests = (array)array_rand(array_flip($testIds), $testPick);
            foreach ($pickedTests as $testId) {
                $favTests[] = [
                    'user_id' => $userId,
                    'test_id' => (int)$testId,
                    'created_at' => $now,
                ];
            }
        }

        $this->insertRowsIgnoreDuplicates($connection, 'user_favorite_categories', $favCategories);
        $this->insertRowsIgnoreDuplicates($connection, 'user_favorite_tests', $favTests);
    }

    /**
     * @param array<int> $playerIds
     * @param array<string, int> $profile
     * @return void
     */
    private function createLogs(Connection $connection, array $playerIds, array $profile, ConsoleIo $io): void
    {
        $io->out('Creating activity/device logs...');
        $now = FrozenTime::now();

        $activityRows = [];
        for ($i = 0; $i < $profile['activity_logs']; $i++) {
            $actions = ['login', 'test_started', 'test_submitted'];
            $activityRows[] = [
                'user_id' => $playerIds[array_rand($playerIds)],
                'action' => $actions[array_rand($actions)],
                'ip_address' => '192.168.' . mt_rand(0, 30) . '.' . mt_rand(2, 240),
                'user_agent' => 'Dummy/LoadTestAgent',
                'created_at' => $now->subMinutes(mt_rand(0, 300000))->format('Y-m-d H:i:s'),
            ];
        }
        $this->insertRows($connection, 'activity_logs', $activityRows);

        $deviceRows = [];
        for ($i = 0; $i < $profile['device_logs']; $i++) {
            $countries = ['HU', 'DE', 'AT', 'RO'];
            $cities = ['Budapest', 'Debrecen', 'Szeged', 'Pecs'];
            $deviceRows[] = [
                'user_id' => $playerIds[array_rand($playerIds)],
                'ip_address' => '10.1.' . mt_rand(0, 25) . '.' . mt_rand(10, 240),
                'user_agent' => 'Dummy/DeviceLogAgent',
                'device_type' => mt_rand(0, 2),
                'country' => $countries[array_rand($countries)],
                'city' => $cities[array_rand($cities)],
                'created_at' => $now->subMinutes(mt_rand(0, 300000))->format('Y-m-d H:i:s'),
            ];
        }
        $this->insertRows($connection, 'device_logs', $deviceRows);
    }

    /**
     * @param array<string, int> $profile
     * @param array<int> $playerIds
     * @param array<int> $testIds
     * @param array<string, int> $languageIds
     * @return void
     */
    private function createAttemptsAndAnswers(Connection $connection, array $profile, array $playerIds, array $testIds, array $languageIds, ConsoleIo $io): void
    {
        $io->out('Creating attempts + attempt answers...');
        $attemptsTarget = $profile['attempts'];
        $chunkSize = 500;

        $created = 0;
        while ($created < $attemptsTarget) {
            $batch = min($chunkSize, $attemptsTarget - $created);
            $attemptRows = [];
            $attemptMeta = [];
            for ($i = 0; $i < $batch; $i++) {
                $userId = $playerIds[array_rand($playerIds)];
                $testId = $testIds[array_rand($testIds)];
                $catalog = $this->testCatalog[$testId] ?? null;
                if ($catalog === null || empty($catalog['questions'])) {
                    continue;
                }

                $started = FrozenTime::now()->subDays(mt_rand(0, 180))->subMinutes(mt_rand(0, 1440));
                $isFinished = mt_rand(1, 100) <= 92;
                $finished = $isFinished ? $started->addMinutes(mt_rand(2, 45)) : null;
                $languageId = mt_rand(0, 1) === 0 ? $languageIds['en_US'] : $languageIds['hu_HU'];

                $attemptRows[] = [
                    'user_id' => $userId,
                    'test_id' => $testId,
                    'category_id' => (int)($catalog['category_id'] ?? 0) ?: null,
                    'difficulty_id' => (int)($catalog['difficulty_id'] ?? 0) ?: null,
                    'language_id' => $languageId,
                    'started_at' => $started->format('Y-m-d H:i:s'),
                    'finished_at' => $finished?->format('Y-m-d H:i:s'),
                    'score' => null,
                    'total_questions' => count($catalog['questions']),
                    'correct_answers' => 0,
                    'created_at' => $started->format('Y-m-d H:i:s'),
                ];
                $attemptMeta[] = [
                    'test_id' => $testId,
                    'finished' => $isFinished,
                    'finished_at' => $finished?->format('Y-m-d H:i:s'),
                ];
            }

            if (!$attemptRows) {
                break;
            }

            $connection->begin();
            try {
                $attemptIds = $this->insertRowsWithIds($connection, 'test_attempts', $attemptRows);

                $attemptUpdates = [];
                $attemptAnswerRows = [];
                foreach ($attemptIds as $idx => $attemptId) {
                    $meta = $attemptMeta[$idx];
                    if (!(bool)$meta['finished']) {
                        continue;
                    }

                    $testId = (int)$meta['test_id'];
                    $questions = $this->testCatalog[$testId]['questions'] ?? [];
                    $correctCount = 0;
                    $total = count($questions);

                    foreach ($questions as $questionId => $questionData) {
                        $type = (string)$questionData['type'];
                        $isCorrect = mt_rand(1, 100) <= 64;
                        $answerId = null;
                        $textAnswer = null;
                        $payload = null;

                        if ($type === Question::TYPE_TEXT) {
                            $accepted = (string)($questionData['correct_texts'][0] ?? 'answer');
                            $textAnswer = $isCorrect ? $accepted : 'wrong_' . mt_rand(100, 999);
                        } elseif ($type === Question::TYPE_MATCHING) {
                            $pairs = [];
                            $left = (array)$questionData['matching_left'];
                            $right = (array)$questionData['matching_right'];

                            if ($isCorrect) {
                                foreach ($left as $leftId => $group) {
                                    foreach ($right as $rightId => $rGroup) {
                                        if ($group === $rGroup) {
                                            $pairs[(string)$leftId] = (int)$rightId;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                $rightIds = array_keys($right);
                                shuffle($rightIds);
                                $leftIds = array_keys($left);
                                foreach ($leftIds as $p => $leftId) {
                                    $pairs[(string)$leftId] = (int)$rightIds[$p % max(1, count($rightIds))];
                                }
                            }

                            $encoded = json_encode(['pairs' => $pairs], JSON_UNESCAPED_SLASHES);
                            $payload = is_string($encoded) ? $encoded : null;
                        } else {
                            $allAnswerIds = array_map(static fn(array $a): int => (int)$a['id'], (array)$questionData['answers']);
                            if ($isCorrect && $questionData['correct_answer_id']) {
                                $answerId = (int)$questionData['correct_answer_id'];
                            } else {
                                $wrong = array_values(array_filter($allAnswerIds, static fn(int $id): bool => $id !== (int)$questionData['correct_answer_id']));
                                if ($wrong) {
                                    $answerId = $wrong[array_rand($wrong)];
                                } else {
                                    $answerId = $allAnswerIds[array_rand($allAnswerIds)] ?? null;
                                    $isCorrect = $answerId === (int)$questionData['correct_answer_id'];
                                }
                            }
                        }

                        if ($isCorrect) {
                            $correctCount++;
                        }

                        $attemptAnswerRows[] = [
                            'test_attempt_id' => $attemptId,
                            'question_id' => (int)$questionId,
                            'answer_id' => $answerId,
                            'user_answer_text' => $textAnswer,
                            'user_answer_payload' => $payload,
                            'is_correct' => $isCorrect ? 1 : 0,
                            'answered_at' => (string)$meta['finished_at'],
                        ];
                    }

                    $score = $total > 0 ? round($correctCount / $total * 100, 2) : 0.0;
                    $attemptUpdates[] = [
                        'id' => (int)$attemptId,
                        'correct_answers' => $correctCount,
                        'score' => $score,
                    ];
                }

                if ($attemptAnswerRows) {
                    $this->insertRows($connection, 'test_attempt_answers', $attemptAnswerRows);
                }

                if ($attemptUpdates) {
                    $this->bulkUpdateAttemptScores($connection, $attemptUpdates);
                }

                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollback();
                throw $e;
            }

            $created += count($attemptIds);
            $io->out('Attempts created: ' . $created . ' / ' . $attemptsTarget);
        }
    }

    /**
     * @param array<int, array{id: int, correct_answers: int, score: float}> $updates
     * @return void
     */
    private function bulkUpdateAttemptScores(Connection $connection, array $updates): void
    {
        foreach (array_chunk($updates, 500) as $chunk) {
            $params = [];
            $ids = [];
            $correctCase = 'CASE `id`';
            $scoreCase = 'CASE `id`';

            foreach ($chunk as $i => $row) {
                $idKey = 'id' . $i;
                $correctKey = 'c' . $i;
                $scoreKey = 's' . $i;

                $params[$idKey] = (int)$row['id'];
                $params[$correctKey] = (int)$row['correct_answers'];
                $params[$scoreKey] = (float)$row['score'];

                $ids[] = ':' . $idKey;
                $correctCase .= ' WHEN :' . $idKey . ' THEN :' . $correctKey;
                $scoreCase .= ' WHEN :' . $idKey . ' THEN :' . $scoreKey;
            }

            $correctCase .= ' END';
            $scoreCase .= ' END';

            $sql = 'UPDATE `test_attempts` SET `correct_answers` = ' . $correctCase
                . ', `score` = ' . $scoreCase
                . ' WHERE `id` IN (' . implode(', ', $ids) . ')';

            $connection->execute($sql, $params);
        }
    }

    /**
     * Picks a question type using weighted random distribution.
     */
    private function pickQuestionType(): string
    {
        $roll = mt_rand(1, 100);
        if ($roll <= 50) {
            return Question::TYPE_MULTIPLE_CHOICE;
        }
        if ($roll <= 65) {
            return Question::TYPE_TRUE_FALSE;
        }
        if ($roll <= 82) {
            return Question::TYPE_TEXT;
        }

        return Question::TYPE_MATCHING;
    }

    /**
     * Returns localized topic labels for generated content.
     */
    private function topicLabel(string $topic, string $lang): string
    {
        $labels = [
            'algebra' => ['en' => 'Algebra', 'hu' => 'Algebra'],
            'geometry' => ['en' => 'Geometry', 'hu' => 'Geometria'],
            'world_history' => ['en' => 'World History', 'hu' => 'Vilagtortenelem'],
            'biology' => ['en' => 'Biology', 'hu' => 'Biologia'],
            'chemistry' => ['en' => 'Chemistry', 'hu' => 'Kemiai ismeretek'],
            'physics' => ['en' => 'Physics', 'hu' => 'Fizika'],
            'literature' => ['en' => 'Literature', 'hu' => 'Irodalom'],
            'programming' => ['en' => 'Programming', 'hu' => 'Programozas'],
            'cybersecurity' => ['en' => 'Cybersecurity', 'hu' => 'Kiberbiztonsag'],
            'economics' => ['en' => 'Economics', 'hu' => 'Kozgazdasagtan'],
        ];

        return $labels[$topic][$lang] ?? ucfirst(str_replace('_', ' ', $topic));
    }

    /**
     * Builds a plausible correct answer option by topic.
     */
    private function correctOptionForTopic(string $topic, int|string $index): string
    {
        return match ($topic) {
            'algebra' => 'Apply variable isolation and verify by substitution (' . $index . ')',
            'geometry' => 'Use angle and side constraints consistently (' . $index . ')',
            'world_history' => 'Place events in the correct chronological order (' . $index . ')',
            'biology' => 'Connect function to the proper cellular process (' . $index . ')',
            'chemistry' => 'Balance reactants and products before evaluating outcome (' . $index . ')',
            'physics' => 'Relate force, mass, and acceleration with consistent units (' . $index . ')',
            'literature' => 'Interpret motif from textual evidence (' . $index . ')',
            'programming' => 'Select the approach with clear time complexity benefits (' . $index . ')',
            'cybersecurity' => 'Apply least-privilege and layered controls (' . $index . ')',
            'economics' => 'Differentiate short-term demand shock from structural change (' . $index . ')',
            default => 'Choose the most evidence-based interpretation (' . $index . ')',
        };
    }

    /**
     * Builds a plausible distractor answer option by topic.
     */
    private function distractorOptionForTopic(string $topic, int|string $option, int|string $index): string
    {
        $base = match ($topic) {
            'programming' => 'Ignore edge cases and optimize prematurely',
            'cybersecurity' => 'Rely on one defensive control only',
            'economics' => 'Treat correlation as direct causation',
            default => 'Use a simplified but inaccurate shortcut',
        };

        return $base . ' (' . $option . '/' . $index . ')';
    }

    /**
     * @return array<int, array{left: string, right: string}>
     */
    private function matchingPairsForTopic(string $topic): array
    {
        $pairs = [
            'algebra' => [
                ['left' => 'Linear equation', 'right' => 'Degree 1 polynomial relation'],
                ['left' => 'Quadratic form', 'right' => 'Second degree expression'],
                ['left' => 'Variable elimination', 'right' => 'Remove unknown via substitution'],
                ['left' => 'Factorization', 'right' => 'Rewrite as product of terms'],
                ['left' => 'Domain restriction', 'right' => 'Valid input constraints'],
            ],
            'programming' => [
                ['left' => 'Array', 'right' => 'Indexed contiguous structure'],
                ['left' => 'Hash map', 'right' => 'Key-value lookup table'],
                ['left' => 'Queue', 'right' => 'FIFO processing order'],
                ['left' => 'Recursion', 'right' => 'Self-referential function calls'],
                ['left' => 'Memoization', 'right' => 'Cache repeated computation'],
            ],
            'cybersecurity' => [
                ['left' => 'MFA', 'right' => 'Multiple independent authentication factors'],
                ['left' => 'Least privilege', 'right' => 'Minimum required permissions'],
                ['left' => 'Phishing', 'right' => 'Social engineering via impersonation'],
                ['left' => 'Patch management', 'right' => 'Timely vulnerability remediation'],
                ['left' => 'Encryption in transit', 'right' => 'Protected network communication'],
            ],
        ];

        return $pairs[$topic] ?? [
            ['left' => 'Cause', 'right' => 'Primary driver in scenario'],
            ['left' => 'Effect', 'right' => 'Observed measurable outcome'],
            ['left' => 'Constraint', 'right' => 'Limiting boundary condition'],
            ['left' => 'Metric', 'right' => 'Quantifiable success indicator'],
            ['left' => 'Intervention', 'right' => 'Applied corrective action'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    private function insertRows(Connection $connection, string $table, array $rows): void
    {
        if (!$rows) {
            return;
        }

        $chunkSize = 500;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            [$sql, $params] = $this->buildInsertSql($table, $chunk, false);
            $connection->execute($sql, $params);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int>
     */
    private function insertRowsWithIds(Connection $connection, string $table, array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $ids = [];
        $chunkSize = 500;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            [$sql, $params] = $this->buildInsertSql($table, $chunk, false);
            $connection->execute($sql, $params);

            $firstId = (int)$connection->getDriver()->lastInsertId();
            if ($firstId <= 0) {
                throw new RuntimeException('Failed to resolve inserted ids for table ' . $table);
            }

            $count = count($chunk);
            for ($i = 0; $i < $count; $i++) {
                $ids[] = $firstId + $i;
            }
        }

        return $ids;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return void
     */
    private function insertRowsIgnoreDuplicates(Connection $connection, string $table, array $rows): void
    {
        if (!$rows) {
            return;
        }

        $chunkSize = 500;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            [$sql, $params] = $this->buildInsertSql($table, $chunk, true);
            $connection->execute($sql, $params);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildInsertSql(string $table, array $rows, bool $ignoreDuplicates): array
    {
        $columns = array_keys($rows[0]);
        $quotedColumns = array_map(static fn(string $column): string => '`' . $column . '`', $columns);

        $params = [];
        $valueGroups = [];
        $counter = 0;

        foreach ($rows as $row) {
            $placeholders = [];
            foreach ($columns as $column) {
                $key = 'p' . $counter;
                $counter++;
                $placeholders[] = ':' . $key;
                $params[$key] = $row[$column] ?? null;
            }
            $valueGroups[] = '(' . implode(', ', $placeholders) . ')';
        }

        $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ') VALUES ' . implode(', ', $valueGroups);
        if ($ignoreDuplicates) {
            $sql .= ' ON DUPLICATE KEY UPDATE id = id';
        }

        return [$sql, $params];
    }

    /**
     * @return int
     */
    private function cleanupDummyData(Connection $connection, ConsoleIo $io, ?string $runToken = null): int
    {
        $io->out('Cleaning previous dummy data' . ($runToken ? ' for run token ' . $runToken : '') . '...');

        $dummyUserIds = $this->findDummyUsers($connection, $runToken);
        $dummyTestIds = $this->findDummyTestIds($connection, $runToken);

        $deleted = 0;

        if ($dummyTestIds) {
            $in = implode(',', array_map('intval', $dummyTestIds));
            $attemptIdsRows = $connection->execute('SELECT id FROM test_attempts WHERE test_id IN (' . $in . ')')->fetchAll('assoc');
            $attemptIds = array_values(array_map(static fn(array $row): int => (int)$row['id'], $attemptIdsRows));
            if ($attemptIds) {
                $inAttempts = implode(',', array_map('intval', $attemptIds));
                $deleted += $connection->execute('DELETE FROM test_attempt_answers WHERE test_attempt_id IN (' . $inAttempts . ')')->rowCount();
                $deleted += $connection->execute('DELETE FROM test_attempts WHERE id IN (' . $inAttempts . ')')->rowCount();
            }

            $deleted += $connection->execute('DELETE FROM user_favorite_tests WHERE test_id IN (' . $in . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM tests WHERE id IN (' . $in . ')')->rowCount();
        }

        if ($dummyUserIds) {
            $inUsers = implode(',', array_map('intval', $dummyUserIds));
            $attemptIdsRows = $connection->execute('SELECT id FROM test_attempts WHERE user_id IN (' . $inUsers . ')')->fetchAll('assoc');
            $attemptIds = array_values(array_map(static fn(array $row): int => (int)$row['id'], $attemptIdsRows));
            if ($attemptIds) {
                $inAttempts = implode(',', array_map('intval', $attemptIds));
                $deleted += $connection->execute('DELETE FROM test_attempt_answers WHERE test_attempt_id IN (' . $inAttempts . ')')->rowCount();
                $deleted += $connection->execute('DELETE FROM test_attempts WHERE id IN (' . $inAttempts . ')')->rowCount();
            }

            $deleted += $connection->execute('DELETE FROM activity_logs WHERE user_id IN (' . $inUsers . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM device_logs WHERE user_id IN (' . $inUsers . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM ai_requests WHERE user_id IN (' . $inUsers . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM api_tokens WHERE user_id IN (' . $inUsers . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM user_tokens WHERE user_id IN (' . $inUsers . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM user_favorite_categories WHERE user_id IN (' . $inUsers . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM user_favorite_tests WHERE user_id IN (' . $inUsers . ')')->rowCount();
            $deleted += $connection->execute('DELETE FROM users WHERE id IN (' . $inUsers . ')')->rowCount();
        }

        return $deleted;
    }

    /**
     * @return array<int>
     */
    private function findDummyUsers(Connection $connection, ?string $runToken = null): array
    {
        $pattern = self::DUMMY_EMAIL_PREFIX . '%@' . self::DUMMY_EMAIL_DOMAIN;
        if ($runToken !== null && $runToken !== '') {
            $pattern = self::DUMMY_EMAIL_PREFIX . '%.' . $runToken . '.%@' . self::DUMMY_EMAIL_DOMAIN;
        }

        $rows = $connection->execute(
            'SELECT id FROM users WHERE email LIKE :pattern',
            ['pattern' => $pattern],
        )->fetchAll('assoc');

        return array_values(array_map(static fn(array $row): int => (int)$row['id'], $rows));
    }

    /**
     * @return array<int>
     */
    private function findDummyUsersByRole(Connection $connection, int $roleId): array
    {
        $rows = $connection->execute(
            'SELECT id FROM users WHERE role_id = :role_id AND email LIKE :pattern',
            [
                'role_id' => $roleId,
                'pattern' => self::DUMMY_EMAIL_PREFIX . '%@' . self::DUMMY_EMAIL_DOMAIN,
            ],
        )->fetchAll('assoc');

        return array_values(array_map(static fn(array $row): int => (int)$row['id'], $rows));
    }

    /**
     * @return array<int>
     */
    private function findDummyTestIds(Connection $connection, ?string $runToken = null): array
    {
        $pattern = self::TEST_TITLE_PREFIX . '%';
        if ($runToken !== null && $runToken !== '') {
            $pattern = self::TEST_TITLE_PREFIX . '%(' . $runToken . ')%';
        }

        $rows = $connection->execute(
            'SELECT DISTINCT test_id FROM test_translations WHERE title LIKE :pattern',
            ['pattern' => $pattern],
        )->fetchAll('assoc');

        return array_values(array_filter(array_map(static fn(array $row): int => (int)$row['test_id'], $rows)));
    }

    /**
     * @param array<int> $testIds
     * @return void
     */
    private function buildCatalogFromDb(Connection $connection, array $testIds): void
    {
        $this->testCatalog = [];
        if (!$testIds) {
            return;
        }

        $in = implode(',', array_map('intval', $testIds));
        $testRows = $connection->execute(
            'SELECT id, category_id, difficulty_id FROM tests WHERE id IN (' . $in . ')',
        )->fetchAll('assoc');

        foreach ($testRows as $row) {
            $tid = (int)$row['id'];
            $this->testCatalog[$tid]['category_id'] = (int)($row['category_id'] ?? 0);
            $this->testCatalog[$tid]['difficulty_id'] = (int)($row['difficulty_id'] ?? 0);
            $this->testCatalog[$tid]['questions'] = $this->testCatalog[$tid]['questions'] ?? [];
        }

        $questionRows = $connection->execute(
            'SELECT id, test_id, question_type FROM questions WHERE test_id IN (' . $in . ') AND is_active = 1 ORDER BY id ASC',
        )->fetchAll('assoc');

        if (!$questionRows) {
            return;
        }

        $questionIds = array_values(array_map(static fn(array $row): int => (int)$row['id'], $questionRows));
        $questionIn = implode(',', array_map('intval', $questionIds));
        $answerRows = $connection->execute(
            'SELECT id, question_id, is_correct, source_text, match_side, match_group FROM answers WHERE question_id IN (' . $questionIn . ') ORDER BY id ASC',
        )->fetchAll('assoc');

        $questionToTest = [];

        foreach ($questionRows as $row) {
            $tid = (int)$row['test_id'];
            $qid = (int)$row['id'];
            $questionToTest[$qid] = $tid;
            $this->testCatalog[$tid]['questions'][$qid] = [
                'type' => (string)$row['question_type'],
                'answers' => [],
                'correct_answer_id' => null,
                'correct_texts' => [],
                'matching_left' => [],
                'matching_right' => [],
            ];
        }

        foreach ($answerRows as $answer) {
            $questionId = (int)$answer['question_id'];
            $testId = (int)($questionToTest[$questionId] ?? 0);
            if ($testId <= 0) {
                continue;
            }

            $questionRef = &$this->testCatalog[$testId]['questions'][$questionId];
            $type = (string)$questionRef['type'];
            $answerId = (int)$answer['id'];
            $isCorrect = (bool)$answer['is_correct'];

            $questionRef['answers'][] = [
                'id' => $answerId,
                'is_correct' => $isCorrect,
                'match_side' => $answer['match_side'],
                'match_group' => $answer['match_group'],
                'source_text' => (string)($answer['source_text'] ?? ''),
            ];

            if ($isCorrect && $type !== Question::TYPE_TEXT && $type !== Question::TYPE_MATCHING && $questionRef['correct_answer_id'] === null) {
                $questionRef['correct_answer_id'] = $answerId;
            }
            if ($isCorrect && $type === Question::TYPE_TEXT) {
                $questionRef['correct_texts'][] = (string)($answer['source_text'] ?? '');
            }
            if ($type === Question::TYPE_MATCHING) {
                $side = (string)($answer['match_side'] ?? '');
                $group = (int)($answer['match_group'] ?? 0);
                if ($side === 'left') {
                    $questionRef['matching_left'][$answerId] = $group;
                }
                if ($side === 'right') {
                    $questionRef['matching_right'][$answerId] = $group;
                }
            }
            unset($questionRef);
        }
    }
}
