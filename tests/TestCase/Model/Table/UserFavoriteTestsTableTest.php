<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UserFavoriteTestsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UserFavoriteTestsTable Test Case
 */
class UserFavoriteTestsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UserFavoriteTestsTable
     */
    protected $UserFavoriteTests;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.UserFavoriteTests',
        'app.Users',
        'app.Tests',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('UserFavoriteTests') ? [] : ['className' => UserFavoriteTestsTable::class];
        $this->UserFavoriteTests = $this->getTableLocator()->get('UserFavoriteTests', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->UserFavoriteTests);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\UserFavoriteTestsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\UserFavoriteTestsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
