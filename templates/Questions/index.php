<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Question> $questions
 */
?>
<div class="questions index content">
    <?= $this->Html->link(__('New Question'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Questions') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('test_id') ?></th>
                    <th><?= $this->Paginator->sort('category_id') ?></th>
                    <th><?= $this->Paginator->sort('difficulty_id') ?></th>
                    <th><?= $this->Paginator->sort('question_type') ?></th>
                    <th><?= $this->Paginator->sort('original_language_id') ?></th>
                    <th><?= $this->Paginator->sort('source_type') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('is_active') ?></th>
                    <th><?= $this->Paginator->sort('position') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th><?= $this->Paginator->sort('updated_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $question): ?>
                <tr>
                    <td><?= $this->Number->format($question->id) ?></td>
                    <td><?= $question->hasValue('test') ? $this->Html->link($question->test->id, ['controller' => 'Tests', 'action' => 'view', $question->test->id]) : '' ?></td>
                    <td><?= $question->hasValue('category') ? $this->Html->link($question->category->id, ['controller' => 'Categories', 'action' => 'view', $question->category->id]) : '' ?></td>
                    <td><?= $question->hasValue('difficulty') ? $this->Html->link($question->difficulty->name, ['controller' => 'Difficulties', 'action' => 'view', $question->difficulty->id]) : '' ?></td>
                    <td><?= h($question->question_type) ?></td>
                    <td><?= $question->hasValue('original_language') ? $this->Html->link($question->original_language->name, ['controller' => 'Languages', 'action' => 'view', $question->original_language->id]) : '' ?></td>
                    <td><?= h($question->source_type) ?></td>
                    <td><?= $question->created_by === null ? '' : $this->Number->format($question->created_by) ?></td>
                    <td><?= h($question->is_active) ?></td>
                    <td><?= $question->position === null ? '' : $this->Number->format($question->position) ?></td>
                    <td><?= h($question->created_at) ?></td>
                    <td><?= h($question->updated_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $question->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $question->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $question->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $question->id),
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