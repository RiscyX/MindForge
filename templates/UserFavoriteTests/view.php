<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UserFavoriteTest $userFavoriteTest
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit User Favorite Test'), ['action' => 'edit', $userFavoriteTest->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete User Favorite Test'), ['action' => 'delete', $userFavoriteTest->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userFavoriteTest->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List User Favorite Tests'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New User Favorite Test'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="userFavoriteTests view content">
            <h3><?= h($userFavoriteTest->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $userFavoriteTest->hasValue('user') ? $this->Html->link($userFavoriteTest->user->email, ['controller' => 'Users', 'action' => 'view', $userFavoriteTest->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Test') ?></th>
                    <td><?= $userFavoriteTest->hasValue('test') ? $this->Html->link($userFavoriteTest->test->id, ['controller' => 'Tests', 'action' => 'view', $userFavoriteTest->test->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($userFavoriteTest->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($userFavoriteTest->created_at) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>