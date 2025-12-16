<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UserToken $userToken
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $userToken->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $userToken->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List User Tokens'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="userTokens form content">
            <?= $this->Form->create($userToken) ?>
            <fieldset>
                <legend><?= __('Edit User Token') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('token');
                    echo $this->Form->control('type');
                    echo $this->Form->control('expires_at');
                    echo $this->Form->control('used_at', ['empty' => true]);
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
