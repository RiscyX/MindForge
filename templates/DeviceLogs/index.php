<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\DeviceLog> $deviceLogs
 */
?>
<div class="deviceLogs index content">
    <?= $this->Html->link(__('New Device Log'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Device Logs') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('ip_address') ?></th>
                    <th><?= $this->Paginator->sort('device_type') ?></th>
                    <th><?= $this->Paginator->sort('country') ?></th>
                    <th><?= $this->Paginator->sort('city') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deviceLogs as $deviceLog): ?>
                <tr>
                    <td><?= $this->Number->format($deviceLog->id) ?></td>
                    <td><?= $deviceLog->hasValue('user') ? $this->Html->link($deviceLog->user->email, ['controller' => 'Users', 'action' => 'view', $deviceLog->user->id]) : '' ?></td>
                    <td><?= h($deviceLog->ip_address) ?></td>
                    <td><?= $this->Number->format($deviceLog->device_type) ?></td>
                    <td><?= h($deviceLog->country) ?></td>
                    <td><?= h($deviceLog->city) ?></td>
                    <td><?= h($deviceLog->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $deviceLog->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $deviceLog->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $deviceLog->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $deviceLog->id),
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