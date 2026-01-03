<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\TestAttempt> $testAttempts
 */
?>
<div class="testAttempts index content">
    <?= $this->Html->link(__('New Test Attempt'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Test Attempts') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('test_id') ?></th>
                    <th><?= $this->Paginator->sort('category_id') ?></th>
                    <th><?= $this->Paginator->sort('difficulty_id') ?></th>
                    <th><?= $this->Paginator->sort('language_id') ?></th>
                    <th><?= $this->Paginator->sort('started_at') ?></th>
                    <th><?= $this->Paginator->sort('finished_at') ?></th>
                    <th><?= $this->Paginator->sort('score') ?></th>
                    <th><?= $this->Paginator->sort('total_questions') ?></th>
                    <th><?= $this->Paginator->sort('correct_answers') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testAttempts as $testAttempt): ?>
                <tr>
                    <td><?= $this->Number->format($testAttempt->id) ?></td>
                    <td><?= $testAttempt->hasValue('user') ? $this->Html->link($testAttempt->user->email, ['controller' => 'Users', 'action' => 'view', $testAttempt->user->id]) : '' ?></td>
                    <td><?= $testAttempt->hasValue('test') ? $this->Html->link($testAttempt->test->id, ['controller' => 'Tests', 'action' => 'view', $testAttempt->test->id]) : '' ?></td>
                    <td><?= $testAttempt->hasValue('category') ? $this->Html->link($testAttempt->category->id, ['controller' => 'Categories', 'action' => 'view', $testAttempt->category->id]) : '' ?></td>
                    <td><?= $testAttempt->hasValue('difficulty') ? $this->Html->link($testAttempt->difficulty->name, ['controller' => 'Difficulties', 'action' => 'view', $testAttempt->difficulty->id]) : '' ?></td>
                    <td><?= $testAttempt->hasValue('language') ? $this->Html->link($testAttempt->language->name, ['controller' => 'Languages', 'action' => 'view', $testAttempt->language->id]) : '' ?></td>
                    <td><?= h($testAttempt->started_at) ?></td>
                    <td><?= h($testAttempt->finished_at) ?></td>
                    <td><?= $testAttempt->score === null ? '' : $this->Number->format($testAttempt->score) ?></td>
                    <td><?= $testAttempt->total_questions === null ? '' : $this->Number->format($testAttempt->total_questions) ?></td>
                    <td><?= $testAttempt->correct_answers === null ? '' : $this->Number->format($testAttempt->correct_answers) ?></td>
                    <td><?= h($testAttempt->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $testAttempt->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $testAttempt->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $testAttempt->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $testAttempt->id),
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