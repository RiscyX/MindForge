<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\UserFavoriteCategory $userFavoriteCategory
 * @var string[]|\Cake\Collection\CollectionInterface $users
 * @var string[]|\Cake\Collection\CollectionInterface $categories
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $userFavoriteCategory->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $userFavoriteCategory->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List User Favorite Categories'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="userFavoriteCategories form content">
            <?= $this->Form->create($userFavoriteCategory) ?>
            <fieldset>
                <legend><?= __('Edit User Favorite Category') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('category_id', ['options' => $categories]);
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
