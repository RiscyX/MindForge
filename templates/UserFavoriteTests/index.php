<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\UserFavoriteTest> $userFavoriteTests
 */
?>
<div class="userFavoriteTests index content">
    <?= $this->Html->link(__('New User Favorite Test'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('User Favorite Tests') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('test_id') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userFavoriteTests as $userFavoriteTest): ?>
                <tr>
                    <td><?= $this->Number->format($userFavoriteTest->id) ?></td>
                    <td><?= $userFavoriteTest->hasValue('user') ? $this->Html->link($userFavoriteTest->user->email, ['controller' => 'Users', 'action' => 'view', $userFavoriteTest->user->id]) : '' ?></td>
                    <td><?= $userFavoriteTest->hasValue('test') ? $this->Html->link($userFavoriteTest->test->id, ['controller' => 'Tests', 'action' => 'view', $userFavoriteTest->test->id]) : '' ?></td>
                    <td><?= h($userFavoriteTest->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $userFavoriteTest->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $userFavoriteTest->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $userFavoriteTest->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $userFavoriteTest->id),
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