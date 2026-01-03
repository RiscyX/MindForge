<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UserFavoriteCategory $userFavoriteCategory
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit User Favorite Category'), ['action' => 'edit', $userFavoriteCategory->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete User Favorite Category'), ['action' => 'delete', $userFavoriteCategory->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userFavoriteCategory->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List User Favorite Categories'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New User Favorite Category'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="userFavoriteCategories view content">
            <h3><?= h($userFavoriteCategory->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $userFavoriteCategory->hasValue('user') ? $this->Html->link($userFavoriteCategory->user->email, ['controller' => 'Users', 'action' => 'view', $userFavoriteCategory->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Category') ?></th>
                    <td><?= $userFavoriteCategory->hasValue('category') ? $this->Html->link($userFavoriteCategory->category->id, ['controller' => 'Categories', 'action' => 'view', $userFavoriteCategory->category->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($userFavoriteCategory->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($userFavoriteCategory->created_at) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>