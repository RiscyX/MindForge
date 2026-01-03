<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AiRequest $aiRequest
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Ai Request'), ['action' => 'edit', $aiRequest->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Ai Request'), ['action' => 'delete', $aiRequest->id], ['confirm' => __('Are you sure you want to delete # {0}?', $aiRequest->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Ai Requests'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Ai Request'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="aiRequests view content">
            <h3><?= h($aiRequest->source_medium) ?></h3>
            <table>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $aiRequest->hasValue('user') ? $this->Html->link($aiRequest->user->email, ['controller' => 'Users', 'action' => 'view', $aiRequest->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Test') ?></th>
                    <td><?= $aiRequest->hasValue('test') ? $this->Html->link($aiRequest->test->id, ['controller' => 'Tests', 'action' => 'view', $aiRequest->test->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Language') ?></th>
                    <td><?= $aiRequest->hasValue('language') ? $this->Html->link($aiRequest->language->name, ['controller' => 'Languages', 'action' => 'view', $aiRequest->language->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Source Medium') ?></th>
                    <td><?= h($aiRequest->source_medium) ?></td>
                </tr>
                <tr>
                    <th><?= __('Source Reference') ?></th>
                    <td><?= h($aiRequest->source_reference) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type') ?></th>
                    <td><?= h($aiRequest->type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Status') ?></th>
                    <td><?= h($aiRequest->status) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($aiRequest->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($aiRequest->created_at) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Input Payload') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($aiRequest->input_payload)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Output Payload') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($aiRequest->output_payload)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>