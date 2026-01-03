<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UserFavoriteTest $userFavoriteTest
 * @var string[]|\Cake\Collection\CollectionInterface $users
 * @var string[]|\Cake\Collection\CollectionInterface $tests
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $userFavoriteTest->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $userFavoriteTest->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List User Favorite Tests'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="userFavoriteTests form content">
            <?= $this->Form->create($userFavoriteTest) ?>
            <fieldset>
                <legend><?= __('Edit User Favorite Test') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('test_id', ['options' => $tests]);
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
