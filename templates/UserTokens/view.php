<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UserToken $userToken
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit User Token'), ['action' => 'edit', $userToken->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete User Token'), ['action' => 'delete', $userToken->id], ['confirm' => __('Are you sure you want to delete # {0}?', $userToken->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List User Tokens'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New User Token'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="userTokens view content">
            <h3><?= h($userToken->type) ?></h3>
            <table>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $userToken->hasValue('user') ? $this->Html->link($userToken->user->email, ['controller' => 'Users', 'action' => 'view', $userToken->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Type') ?></th>
                    <td><?= h($userToken->type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($userToken->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Expires At') ?></th>
                    <td><?= h($userToken->expires_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Used At') ?></th>
                    <td><?= h($userToken->used_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($userToken->created_at) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>