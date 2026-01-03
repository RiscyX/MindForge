<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TestsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TestsTable Test Case
 */
class TestsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TestsTable
     */
    protected $Tests;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Tests',
        'app.Categories',
        'app.Difficulties',
        'app.AiRequests',
        'app.Questions',
        'app.TestAttempts',
        'app.TestTranslations',
        'app.UserFavoriteTests',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Tests') ? [] : ['className' => TestsTable::class];
        $this->Tests = $this->getTableLocator()->get('Tests', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Tests);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\TestsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\TestsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
