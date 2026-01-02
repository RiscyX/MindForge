<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ActivityLogsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ActivityLogsTable Test Case
 */
class ActivityLogsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ActivityLogsTable
     */
    protected $ActivityLogs;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.ActivityLogs',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ActivityLogs') ? [] : ['className' => ActivityLogsTable::class];
        $this->ActivityLogs = $this->getTableLocator()->get('ActivityLogs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ActivityLogs);

        parent::tearDown();
    }
}
