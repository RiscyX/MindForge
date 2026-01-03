<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ActivityLog $activityLog
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $activityLog->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $activityLog->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Activity Logs'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="activityLogs form content">
            <?= $this->Form->create($activityLog) ?>
            <fieldset>
                <legend><?= __('Edit Activity Log') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users, 'empty' => true]);
                    echo $this->Form->control('action');
                    echo $this->Form->control('ip_address');
                    echo $this->Form->control('user_agent');
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
