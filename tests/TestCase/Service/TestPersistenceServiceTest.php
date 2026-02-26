<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TestPersistenceService;
use Cake\TestSuite\TestCase;

class TestPersistenceServiceTest extends TestCase
{
    public function testBackfillNestedTranslationIdsMatchesByLanguageId(): void
    {
        $data = [
            'questions' => [
                [
                    'id' => 10,
                    'question_translations' => [
                        [
                            'language_id' => 1,
                            'content' => 'Updated question',
                        ],
                    ],
                    'answers' => [
                        [
                            'id' => 100,
                            'answer_translations' => [
                                [
                                    'language_id' => 1,
                                    'content' => 'Updated answer',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $test = (object)[
            'questions' => [
                (object)[
                    'id' => 10,
                    'question_translations' => [
                        (object)[
                            'id' => 501,
                            'language_id' => 1,
                        ],
                    ],
                    'answers' => [
                        (object)[
                            'id' => 100,
                            'answer_translations' => [
                                (object)[
                                    'id' => 701,
                                    'language_id' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $service = new TestPersistenceService();
        $method = new \ReflectionMethod(TestPersistenceService::class, 'backfillNestedTranslationIds');
        $method->setAccessible(true);
        $method->invokeArgs($service, [&$data, $test]);

        $this->assertSame(501, $data['questions'][0]['question_translations'][0]['id']);
        $this->assertSame(701, $data['questions'][0]['answers'][0]['answer_translations'][0]['id']);
    }
}
