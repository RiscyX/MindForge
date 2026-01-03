<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AnswerTranslation $answerTranslation
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Answer Translation'), ['action' => 'edit', $answerTranslation->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Answer Translation'), ['action' => 'delete', $answerTranslation->id], ['confirm' => __('Are you sure you want to delete # {0}?', $answerTranslation->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Answer Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Answer Translation'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="answerTranslations view content">
            <h3><?= h($answerTranslation->source_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Answer') ?></th>
                    <td><?= $answerTranslation->hasValue('answer') ? $this->Html->link($answerTranslation->answer->source_type, ['controller' => 'Answers', 'action' => 'view', $answerTranslation->answer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Language') ?></th>
                    <td><?= $answerTranslation->hasValue('language') ? $this->Html->link($answerTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $answerTranslation->language->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Source Type') ?></th>
                    <td><?= h($answerTranslation->source_type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($answerTranslation->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $answerTranslation->created_by === null ? '' : $this->Number->format($answerTranslation->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($answerTranslation->created_at) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Content') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($answerTranslation->content)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>