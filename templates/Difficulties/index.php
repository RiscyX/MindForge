<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Difficulty> $difficulties
 */
$this->Paginator->options(['url' => ['lang' => $lang]]);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white"><?= __('Difficulties') ?></h1>
        <?= $this->Html->link(__('New Difficulty'), ['action' => 'add', 'lang' => $lang], ['class' => 'btn btn-primary']) ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover text-white" width="100%" cellspacing="0" style="--bs-table-bg: transparent">
            <thead>
                <tr>
                    <th class="text-white"><?= $this->Paginator->sort('id') ?></th>
                    <th class="text-white"><?= __('Names') ?></th>
                    <th class="text-white"><?= $this->Paginator->sort('level') ?></th>
                    <th class="actions text-end text-white"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($difficulties as $difficulty): ?>
                <tr>
                    <td class="text-white"><?= $this->Number->format($difficulty->id) ?></td>
                    <td class="text-white">
                        <?php foreach ($difficulty->difficulty_translations as $translation): ?>
                            <?php if ($translation->hasValue('language')): ?>
                                <div>
                                    <span class="badge bg-secondary me-1"><?= h($translation->language->code) ?></span>
                                    <?= h($translation->name) ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                    <td class="text-white"><?= $this->Number->format($difficulty->level) ?></td>
                    <td class="actions text-end">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $difficulty->id, 'lang' => $lang], ['class' => 'btn btn-sm btn-info text-white']) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $difficulty->id, 'lang' => $lang], ['class' => 'btn btn-sm btn-warning text-white']) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $difficulty->id, 'lang' => $lang],
                            [
                                'confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id),
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