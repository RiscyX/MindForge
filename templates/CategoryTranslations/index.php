<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CategoryTranslation> $categoryTranslations
 */
?>
<div class="categoryTranslations index content">
    <?= $this->Html->link(__('New Category Translation'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Category Translations') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('category_id') ?></th>
                    <th><?= $this->Paginator->sort('language_id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categoryTranslations as $categoryTranslation): ?>
                <tr>
                    <td><?= $this->Number->format($categoryTranslation->id) ?></td>
                    <td><?= $categoryTranslation->hasValue('category') ? $this->Html->link($categoryTranslation->category->id, ['controller' => 'Categories', 'action' => 'view', $categoryTranslation->category->id]) : '' ?></td>
                    <td><?= $categoryTranslation->hasValue('language') ? $this->Html->link($categoryTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $categoryTranslation->language->id]) : '' ?></td>
                    <td><?= h($categoryTranslation->name) ?></td>
                    <td><?= h($categoryTranslation->created_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $categoryTranslation->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $categoryTranslation->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $categoryTranslation->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $categoryTranslation->id),
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