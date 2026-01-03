<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DeviceLog $deviceLog
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $deviceLog->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $deviceLog->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Device Logs'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="deviceLogs form content">
            <?= $this->Form->create($deviceLog) ?>
            <fieldset>
                <legend><?= __('Edit Device Log') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users, 'empty' => true]);
                    echo $this->Form->control('ip_address');
                    echo $this->Form->control('user_agent');
                    echo $this->Form->control('device_type');
                    echo $this->Form->control('country');
                    echo $this->Form->control('city');
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
