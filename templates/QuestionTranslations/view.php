<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\QuestionTranslation $questionTranslation
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Question Translation'), ['action' => 'edit', $questionTranslation->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Question Translation'), ['action' => 'delete', $questionTranslation->id], ['confirm' => __('Are you sure you want to delete # {0}?', $questionTranslation->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Question Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Question Translation'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="questionTranslations view content">
            <h3><?= h($questionTranslation->source_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Question') ?></th>
                    <td><?= $questionTranslation->hasValue('question') ? $this->Html->link($questionTranslation->question->question_type, ['controller' => 'Questions', 'action' => 'view', $questionTranslation->question->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Language') ?></th>
                    <td><?= $questionTranslation->hasValue('language') ? $this->Html->link($questionTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $questionTranslation->language->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Source Type') ?></th>
                    <td><?= h($questionTranslation->source_type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($questionTranslation->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $questionTranslation->created_by === null ? '' : $this->Number->format($questionTranslation->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($questionTranslation->created_at) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Content') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($questionTranslation->content)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Explanation') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($questionTranslation->explanation)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>