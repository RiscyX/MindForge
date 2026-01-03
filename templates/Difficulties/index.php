<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Difficulty> $difficulties
 */
?>
<div class="difficulties index content">
    <?= $this->Html->link(__('New Difficulty'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Difficulties') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('level') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($difficulties as $difficulty): ?>
                <tr>
                    <td><?= $this->Number->format($difficulty->id) ?></td>
                    <td><?= h($difficulty->name) ?></td>
                    <td><?= $this->Number->format($difficulty->level) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $difficulty->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $difficulty->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $difficulty->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id),
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