<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttemptAnswer $testAttemptAnswer
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Test Attempt Answer'), ['action' => 'edit', $testAttemptAnswer->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Test Attempt Answer'), ['action' => 'delete', $testAttemptAnswer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $testAttemptAnswer->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Test Attempt Answers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Test Attempt Answer'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testAttemptAnswers view content">
            <h3><?= h($testAttemptAnswer->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Test Attempt') ?></th>
                    <td><?= $testAttemptAnswer->hasValue('test_attempt') ? $this->Html->link($testAttemptAnswer->test_attempt->id, ['controller' => 'TestAttempts', 'action' => 'view', $testAttemptAnswer->test_attempt->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Question') ?></th>
                    <td><?= $testAttemptAnswer->hasValue('question') ? $this->Html->link($testAttemptAnswer->question->question_type, ['controller' => 'Questions', 'action' => 'view', $testAttemptAnswer->question->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Answer') ?></th>
                    <td><?= $testAttemptAnswer->hasValue('answer') ? $this->Html->link($testAttemptAnswer->answer->source_type, ['controller' => 'Answers', 'action' => 'view', $testAttemptAnswer->answer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($testAttemptAnswer->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Answered At') ?></th>
                    <td><?= h($testAttemptAnswer->answered_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Correct') ?></th>
                    <td><?= $testAttemptAnswer->is_correct ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('User Answer Text') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($testAttemptAnswer->user_answer_text)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>