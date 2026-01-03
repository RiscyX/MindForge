<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\TestAttemptAnswersController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\TestAttemptAnswersController Test Case
 *
 * @link \App\Controller\TestAttemptAnswersController
 */
class TestAttemptAnswersControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
     * Test index method
     *
     * @return void
     * @link \App\Controller\TestAttemptAnswersController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\TestAttemptAnswersController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\TestAttemptAnswersController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\TestAttemptAnswersController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\TestAttemptAnswersController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
