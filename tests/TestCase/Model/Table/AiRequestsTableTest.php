<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AiRequestsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AiRequestsTable Test Case
 */
class AiRequestsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AiRequestsTable
     */
    protected $AiRequests;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AiRequests',
        'app.Users',
        'app.Tests',
        'app.Languages',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('AiRequests') ? [] : ['className' => AiRequestsTable::class];
        $this->AiRequests = $this->getTableLocator()->get('AiRequests', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->AiRequests);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\AiRequestsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\AiRequestsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
