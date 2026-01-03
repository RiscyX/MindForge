<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DeviceLog $deviceLog
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Device Log'), ['action' => 'edit', $deviceLog->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Device Log'), ['action' => 'delete', $deviceLog->id], ['confirm' => __('Are you sure you want to delete # {0}?', $deviceLog->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Device Logs'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Device Log'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="deviceLogs view content">
            <h3><?= h($deviceLog->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $deviceLog->hasValue('user') ? $this->Html->link($deviceLog->user->email, ['controller' => 'Users', 'action' => 'view', $deviceLog->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Ip Address') ?></th>
                    <td><?= h($deviceLog->ip_address) ?></td>
                </tr>
                <tr>
                    <th><?= __('Country') ?></th>
                    <td><?= h($deviceLog->country) ?></td>
                </tr>
                <tr>
                    <th><?= __('City') ?></th>
                    <td><?= h($deviceLog->city) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($deviceLog->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Device Type') ?></th>
                    <td><?= $this->Number->format($deviceLog->device_type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($deviceLog->created_at) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('User Agent') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($deviceLog->user_agent)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>