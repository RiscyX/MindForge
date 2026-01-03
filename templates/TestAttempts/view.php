<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttempt $testAttempt
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Test Attempt'), ['action' => 'edit', $testAttempt->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Test Attempt'), ['action' => 'delete', $testAttempt->id], ['confirm' => __('Are you sure you want to delete # {0}?', $testAttempt->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Test Attempts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Test Attempt'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testAttempts view content">
            <h3><?= h($testAttempt->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $testAttempt->hasValue('user') ? $this->Html->link($testAttempt->user->email, ['controller' => 'Users', 'action' => 'view', $testAttempt->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Test') ?></th>
                    <td><?= $testAttempt->hasValue('test') ? $this->Html->link($testAttempt->test->id, ['controller' => 'Tests', 'action' => 'view', $testAttempt->test->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Category') ?></th>
                    <td><?= $testAttempt->hasValue('category') ? $this->Html->link($testAttempt->category->id, ['controller' => 'Categories', 'action' => 'view', $testAttempt->category->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Difficulty') ?></th>
                    <td><?= $testAttempt->hasValue('difficulty') ? $this->Html->link($testAttempt->difficulty->name, ['controller' => 'Difficulties', 'action' => 'view', $testAttempt->difficulty->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Language') ?></th>
                    <td><?= $testAttempt->hasValue('language') ? $this->Html->link($testAttempt->language->name, ['controller' => 'Languages', 'action' => 'view', $testAttempt->language->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($testAttempt->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Score') ?></th>
                    <td><?= $testAttempt->score === null ? '' : $this->Number->format($testAttempt->score) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total Questions') ?></th>
                    <td><?= $testAttempt->total_questions === null ? '' : $this->Number->format($testAttempt->total_questions) ?></td>
                </tr>
                <tr>
                    <th><?= __('Correct Answers') ?></th>
                    <td><?= $testAttempt->correct_answers === null ? '' : $this->Number->format($testAttempt->correct_answers) ?></td>
                </tr>
                <tr>
                    <th><?= __('Started At') ?></th>
                    <td><?= h($testAttempt->started_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Finished At') ?></th>
                    <td><?= h($testAttempt->finished_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($testAttempt->created_at) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Test Attempt Answers') ?></h4>
                <?php if (!empty($testAttempt->test_attempt_answers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Test Attempt Id') ?></th>
                            <th><?= __('Question Id') ?></th>
                            <th><?= __('Answer Id') ?></th>
                            <th><?= __('User Answer Text') ?></th>
                            <th><?= __('Is Correct') ?></th>
                            <th><?= __('Answered At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($testAttempt->test_attempt_answers as $testAttemptAnswer) : ?>
                        <tr>
                            <td><?= h($testAttemptAnswer->id) ?></td>
                            <td><?= h($testAttemptAnswer->test_attempt_id) ?></td>
                            <td><?= h($testAttemptAnswer->question_id) ?></td>
                            <td><?= h($testAttemptAnswer->answer_id) ?></td>
                            <td><?= h($testAttemptAnswer->user_answer_text) ?></td>
                            <td><?= h($testAttemptAnswer->is_correct) ?></td>
                            <td><?= h($testAttemptAnswer->answered_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'TestAttemptAnswers', 'action' => 'view', $testAttemptAnswer->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'TestAttemptAnswers', 'action' => 'edit', $testAttemptAnswer->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'TestAttemptAnswers', 'action' => 'delete', $testAttemptAnswer->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $testAttemptAnswer->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>