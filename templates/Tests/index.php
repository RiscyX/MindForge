<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Test> $tests
 */
?>
<div class="tests index content">
    <?= $this->Html->link(__('New Test'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Tests') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('category_id') ?></th>
                    <th><?= $this->Paginator->sort('difficulty_id') ?></th>
                    <th><?= $this->Paginator->sort('number_of_questions') ?></th>
                    <th><?= $this->Paginator->sort('is_public') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th><?= $this->Paginator->sort('updated_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tests as $test): ?>
                <tr>
                    <td><?= $this->Number->format($test->id) ?></td>
                    <td><?= $test->hasValue('category') ? $this->Html->link($test->category->id, ['controller' => 'Categories', 'action' => 'view', $test->category->id]) : '' ?></td>
                    <td><?= $test->hasValue('difficulty') ? $this->Html->link($test->difficulty->name, ['controller' => 'Difficulties', 'action' => 'view', $test->difficulty->id]) : '' ?></td>
                    <td><?= $test->number_of_questions === null ? '' : $this->Number->format($test->number_of_questions) ?></td>
                    <td><?= h($test->is_public) ?></td>
                    <td><?= $test->created_by === null ? '' : $this->Number->format($test->created_by) ?></td>
                    <td><?= h($test->created_at) ?></td>
                    <td><?= h($test->updated_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $test->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $test->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $test->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $test->id),
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