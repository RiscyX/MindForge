<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TestAttemptAnswersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TestAttemptAnswersTable Test Case
 */
class TestAttemptAnswersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TestAttemptAnswersTable
     */
    protected $TestAttemptAnswers;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.TestAttemptAnswers',
        'app.TestAttempts',
        'app.Questions',
        'app.Answers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TestAttemptAnswers') ? [] : ['className' => TestAttemptAnswersTable::class];
        $this->TestAttemptAnswers = $this->getTableLocator()->get('TestAttemptAnswers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->TestAttemptAnswers);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\TestAttemptAnswersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\TestAttemptAnswersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
