<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $difficulty->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Difficulties'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="difficulties form content">
            <?= $this->Form->create($difficulty) ?>
            <fieldset>
                <legend><?= __('Edit Difficulty') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('level');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
