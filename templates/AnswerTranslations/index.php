<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AnswerTranslation> $answerTranslations
 */
?>
<div class="answerTranslations index content">
    <?= $this->Html->link(__('New Answer Translation'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Answer Translations') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('answer_id') ?></th>
                    <th><?= $this->Paginator->sort('language_id') ?></th>
                    <th><?= $this->Paginator->sort('source_type') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($answerTranslations as $answerTranslation): ?>
                <tr>
                    <td><?= $this->Number->format($answerTranslation->id) ?></td>
                    <td><?= $answerTranslation->hasValue('answer') ? $this->Html->link($answerTranslation->answer->source_type, ['controller' => 'Answers', 'action' => 'view', $answerTranslation->answer->id]) : '' ?></td>
                    <td><?= $answerTranslation->hasValue('language') ? $this->Html->link($answerTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $answerTranslation->language->id]) : '' ?></td>
                    <td><?= h($answerTranslation->source_type) ?></td>
                    <td><?= $answerTranslation->created_by === null ? '' : $this->Number->format($answerTranslation->created_by) ?></td>
                    <td><?= h($answerTranslation->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $answerTranslation->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $answerTranslation->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $answerTranslation->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $answerTranslation->id),
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