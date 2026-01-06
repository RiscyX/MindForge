<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Language> $languages
 */
$this->Paginator->options(['url' => ['lang' => $lang]]);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white"><?= __('Languages') ?></h1>
        <?= $this->Html->link(__('New Language'), ['action' => 'add', 'lang' => $lang], ['class' => 'btn btn-primary']) ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover text-white" width="100%" cellspacing="0" style="--bs-table-bg: transparent">
            <thead>
                <tr>
                    <th class="text-white"><?= $this->Paginator->sort('id') ?></th>
                    <th class="text-white"><?= $this->Paginator->sort('code') ?></th>
                    <th class="text-white"><?= $this->Paginator->sort('name') ?></th>
                    <th class="actions text-end text-white"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($languages as $language): ?>
                <tr>
                    <td class="text-white"><?= $this->Number->format($language->id) ?></td>
                    <td class="text-white"><?= h($language->code) ?></td>
                    <td class="text-white"><?= h($language->name) ?></td>
                    <td class="actions text-end">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $language->id, 'lang' => $lang], ['class' => 'btn btn-sm btn-info text-white']) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $language->id, 'lang' => $lang], ['class' => 'btn btn-sm btn-warning text-white']) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $language->id, 'lang' => $lang],
                            [
                                'confirm' => __('Are you sure you want to delete # {0}?', $language->id),
                                'class' => 'btn btn-sm btn-danger'
                            ]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-3 text-white">
        <div class="dataTables_info">
            <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
        </div>
        <ul class="pagination mb-0">
            <?= $this->Paginator->first('<< ' . __('first'), ['class' => 'page-item', 'linkClass' => 'page-link']) ?>
            <?= $this->Paginator->prev('< ' . __('previous'), ['class' => 'page-item', 'linkClass' => 'page-link']) ?>
            <?= $this->Paginator->numbers(['class' => 'page-item', 'linkClass' => 'page-link']) ?>
            <?= $this->Paginator->next(__('next') . ' >', ['class' => 'page-item', 'linkClass' => 'page-link']) ?>
            <?= $this->Paginator->last(__('last') . ' >>', ['class' => 'page-item', 'linkClass' => 'page-link']) ?>
        </ul>
    </div>
</div>