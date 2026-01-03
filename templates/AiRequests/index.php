<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AiRequest> $aiRequests
 */
?>
<div class="aiRequests index content">
    <?= $this->Html->link(__('New Ai Request'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Ai Requests') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('test_id') ?></th>
                    <th><?= $this->Paginator->sort('language_id') ?></th>
                    <th><?= $this->Paginator->sort('source_medium') ?></th>
                    <th><?= $this->Paginator->sort('source_reference') ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('status') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aiRequests as $aiRequest): ?>
                <tr>
                    <td><?= $this->Number->format($aiRequest->id) ?></td>
                    <td><?= $aiRequest->hasValue('user') ? $this->Html->link($aiRequest->user->email, ['controller' => 'Users', 'action' => 'view', $aiRequest->user->id]) : '' ?></td>
                    <td><?= $aiRequest->hasValue('test') ? $this->Html->link($aiRequest->test->id, ['controller' => 'Tests', 'action' => 'view', $aiRequest->test->id]) : '' ?></td>
                    <td><?= $aiRequest->hasValue('language') ? $this->Html->link($aiRequest->language->name, ['controller' => 'Languages', 'action' => 'view', $aiRequest->language->id]) : '' ?></td>
                    <td><?= h($aiRequest->source_medium) ?></td>
                    <td><?= h($aiRequest->source_reference) ?></td>
                    <td><?= h($aiRequest->type) ?></td>
                    <td><?= h($aiRequest->status) ?></td>
                    <td><?= h($aiRequest->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $aiRequest->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $aiRequest->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $aiRequest->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $aiRequest->id),
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