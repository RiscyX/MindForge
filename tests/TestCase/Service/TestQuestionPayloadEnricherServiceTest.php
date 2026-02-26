<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TestQuestionPayloadEnricherService;
use Cake\TestSuite\TestCase;

class TestQuestionPayloadEnricherServiceTest extends TestCase
{
    public function testEnrichForSaveNormalizesSourceTypeAcrossNestedPayload(): void
    {
        $data = [
            'category_id' => 3,
            'questions' => [
                [
                    'id' => 10,
                    'source_type' => '',
                    'question_translations' => [
                        [
                            'language_id' => 1,
                            'content' => 'Question HU',
                        ],
                    ],
                    'answers' => [
                        [
                            'id' => 20,
                            'source_type' => '',
                            'answer_translations' => [
                                [
                                    'language_id' => 1,
                                    'content' => 'Answer HU',
                                    'source_type' => 'invalid',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'source_type' => 'ai',
                    'question_translations' => [
                        [
                            'language_id' => 2,
                            'content' => 'Question EN',
                            'source_type' => '',
                        ],
                    ],
                    'answers' => [
                        [
                            'answer_translations' => [
                                [
                                    'language_id' => 2,
                                    'content' => 'Answer EN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $service = new TestQuestionPayloadEnricherService();
        $service->enrichForSave($data, 7, 1);

        $this->assertSame('human', $data['questions'][0]['source_type']);
        $this->assertSame('human', $data['questions'][0]['question_translations'][0]['source_type']);
        $this->assertSame(7, $data['questions'][0]['question_translations'][0]['created_by']);
        $this->assertSame('human', $data['questions'][0]['answers'][0]['source_type']);
        $this->assertSame('human', $data['questions'][0]['answers'][0]['answer_translations'][0]['source_type']);
        $this->assertSame(7, $data['questions'][0]['answers'][0]['answer_translations'][0]['created_by']);
        $this->assertSame(1, $data['questions'][0]['answers'][0]['position']);
        $this->assertSame(3, $data['questions'][0]['category_id']);

        $this->assertSame('ai', $data['questions'][1]['source_type']);
        $this->assertSame('ai', $data['questions'][1]['question_translations'][0]['source_type']);
        $this->assertSame('ai', $data['questions'][1]['answers'][0]['source_type']);
        $this->assertSame('ai', $data['questions'][1]['answers'][0]['answer_translations'][0]['source_type']);
    }
}
