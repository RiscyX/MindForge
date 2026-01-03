<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\TestAttemptAnswer> $testAttemptAnswers
 */
?>
<div class="testAttemptAnswers index content">
    <?= $this->Html->link(__('New Test Attempt Answer'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Test Attempt Answers') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('test_attempt_id') ?></th>
                    <th><?= $this->Paginator->sort('question_id') ?></th>
                    <th><?= $this->Paginator->sort('answer_id') ?></th>
                    <th><?= $this->Paginator->sort('is_correct') ?></th>
                    <th><?= $this->Paginator->sort('answered_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testAttemptAnswers as $testAttemptAnswer): ?>
                <tr>
                    <td><?= $this->Number->format($testAttemptAnswer->id) ?></td>
                    <td><?= $testAttemptAnswer->hasValue('test_attempt') ? $this->Html->link($testAttemptAnswer->test_attempt->id, ['controller' => 'TestAttempts', 'action' => 'view', $testAttemptAnswer->test_attempt->id]) : '' ?></td>
                    <td><?= $testAttemptAnswer->hasValue('question') ? $this->Html->link($testAttemptAnswer->question->question_type, ['controller' => 'Questions', 'action' => 'view', $testAttemptAnswer->question->id]) : '' ?></td>
                    <td><?= $testAttemptAnswer->hasValue('answer') ? $this->Html->link($testAttemptAnswer->answer->source_type, ['controller' => 'Answers', 'action' => 'view', $testAttemptAnswer->answer->id]) : '' ?></td>
                    <td><?= h($testAttemptAnswer->is_correct) ?></td>
                    <td><?= h($testAttemptAnswer->answered_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $testAttemptAnswer->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $testAttemptAnswer->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $testAttemptAnswer->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $testAttemptAnswer->id),
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>