<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\QuestionTranslation> $questionTranslations
 */
?>
<div class="questionTranslations index content">
    <?= $this->Html->link(__('New Question Translation'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Question Translations') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('question_id') ?></th>
                    <th><?= $this->Paginator->sort('language_id') ?></th>
                    <th><?= $this->Paginator->sort('source_type') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questionTranslations as $questionTranslation): ?>
                <tr>
                    <td><?= $this->Number->format($questionTranslation->id) ?></td>
                    <td><?= $questionTranslation->hasValue('question') ? $this->Html->link($questionTranslation->question->question_type, ['controller' => 'Questions', 'action' => 'view', $questionTranslation->question->id]) : '' ?></td>
                    <td><?= $questionTranslation->hasValue('language') ? $this->Html->link($questionTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $questionTranslation->language->id]) : '' ?></td>
                    <td><?= h($questionTranslation->source_type) ?></td>
                    <td><?= $questionTranslation->created_by === null ? '' : $this->Number->format($questionTranslation->created_by) ?></td>
                    <td><?= h($questionTranslation->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $questionTranslation->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $questionTranslation->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $questionTranslation->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $questionTranslation->id),
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>