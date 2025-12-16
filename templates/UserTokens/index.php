<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\UserToken> $userTokens
 */
?>
<div class="userTokens index content">
    <?= $this->Html->link(__('New User Token'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('User Tokens') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('expires_at') ?></th>
                    <th><?= $this->Paginator->sort('used_at') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userTokens as $userToken): ?>
                <tr>
                    <td><?= $this->Number->format($userToken->id) ?></td>
                    <td><?= $userToken->hasValue('user') ? $this->Html->link($userToken->user->email, ['controller' => 'Users', 'action' => 'view', $userToken->user->id]) : '' ?></td>
                    <td><?= h($userToken->type) ?></td>
                    <td><?= h($userToken->expires_at) ?></td>
                    <td><?= h($userToken->used_at) ?></td>
                    <td><?= h($userToken->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $userToken->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $userToken->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $userToken->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $userToken->id),
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