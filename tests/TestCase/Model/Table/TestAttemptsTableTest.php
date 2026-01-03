<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TestAttemptsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TestAttemptsTable Test Case
 */
class TestAttemptsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TestAttemptsTable
     */
    protected $TestAttempts;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.TestAttempts',
        'app.Users',
        'app.Tests',
        'app.Categories',
        'app.Difficulties',
        'app.Languages',
        'app.TestAttemptAnswers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TestAttempts') ? [] : ['className' => TestAttemptsTable::class];
        $this->TestAttempts = $this->getTableLocator()->get('TestAttempts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->TestAttempts);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\TestAttemptsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\TestAttemptsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
