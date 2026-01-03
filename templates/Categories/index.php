<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Category> $categories
 */
$this->Paginator->options(['url' => ['lang' => $lang]]);
?>
<div class="categories index content">
    <?= $this->Html->link(__('New Category'), ['action' => 'add', 'lang' => $lang], ['class' => 'button float-right']) ?>
    <h3><?= __('Categories') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('is_active') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th><?= $this->Paginator->sort('updated_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= $this->Number->format($category->id) ?></td>
                    <td><?= h($category->is_active) ?></td>
                    <td><?= h($category->created_at) ?></td>
                    <td><?= h($category->updated_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $category->id, 'lang' => $lang]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $category->id, 'lang' => $lang]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $category->id, 'lang' => $lang],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $category->id),
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