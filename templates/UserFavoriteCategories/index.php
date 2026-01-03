<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\UserFavoriteCategory> $userFavoriteCategories
 */
?>
<div class="userFavoriteCategories index content">
    <?= $this->Html->link(__('New User Favorite Category'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('User Favorite Categories') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('category_id') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userFavoriteCategories as $userFavoriteCategory): ?>
                <tr>
                    <td><?= $this->Number->format($userFavoriteCategory->id) ?></td>
                    <td><?= $userFavoriteCategory->hasValue('user') ? $this->Html->link($userFavoriteCategory->user->email, ['controller' => 'Users', 'action' => 'view', $userFavoriteCategory->user->id]) : '' ?></td>
                    <td><?= $userFavoriteCategory->hasValue('category') ? $this->Html->link($userFavoriteCategory->category->id, ['controller' => 'Categories', 'action' => 'view', $userFavoriteCategory->category->id]) : '' ?></td>
                    <td><?= h($userFavoriteCategory->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $userFavoriteCategory->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $userFavoriteCategory->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $userFavoriteCategory->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $userFavoriteCategory->id),
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