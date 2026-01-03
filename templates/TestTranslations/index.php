<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\TestTranslation> $testTranslations
 */
?>
<div class="testTranslations index content">
    <?= $this->Html->link(__('New Test Translation'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Test Translations') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('test_id') ?></th>
                    <th><?= $this->Paginator->sort('language_id') ?></th>
                    <th><?= $this->Paginator->sort('title') ?></th>
                    <th><?= $this->Paginator->sort('slug') ?></th>
                    <th><?= $this->Paginator->sort('translator_id') ?></th>
                    <th><?= $this->Paginator->sort('is_complete') ?></th>
                    <th><?= $this->Paginator->sort('translated_at') ?></th>
                    <th><?= $this->Paginator->sort('created_at') ?></th>
                    <th><?= $this->Paginator->sort('updated_at') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testTranslations as $testTranslation): ?>
                <tr>
                    <td><?= $this->Number->format($testTranslation->id) ?></td>
                    <td><?= $testTranslation->hasValue('test') ? $this->Html->link($testTranslation->test->id, ['controller' => 'Tests', 'action' => 'view', $testTranslation->test->id]) : '' ?></td>
                    <td><?= $testTranslation->hasValue('language') ? $this->Html->link($testTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $testTranslation->language->id]) : '' ?></td>
                    <td><?= h($testTranslation->title) ?></td>
                    <td><?= h($testTranslation->slug) ?></td>
                    <td><?= $testTranslation->hasValue('translator') ? $this->Html->link($testTranslation->translator->email, ['controller' => 'Users', 'action' => 'view', $testTranslation->translator->id]) : '' ?></td>
                    <td><?= h($testTranslation->is_complete) ?></td>
                    <td><?= h($testTranslation->translated_at) ?></td>
                    <td><?= h($testTranslation->created_at) ?></td>
                    <td><?= h($testTranslation->updated_at) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $testTranslation->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $testTranslation->id]) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $testTranslation->id],
                            [
                                'method' => 'delete',
                                'confirm' => __('Are you sure you want to delete # {0}?', $testTranslation->id),
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