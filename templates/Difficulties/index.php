<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Difficulty> $difficulties
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Difficulties'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Difficulties') ?></h1>
    </div>
</div>

<br>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfDifficultiesSearch',
        'label' => __('Search by name or id'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfDifficultiesLimit',
        'label' => __('Show'),
        'default' => '10',
        'options' => [
            '10' => '10',
            '50' => '50',
            '100' => '100',
            '-1' => __('All'),
        ],
    ],
    'create' => [
        'label' => __('New Difficulty') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <div class="mf-admin-table-scroll">
        <table id="mfDifficultiesTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Name') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Level') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($difficulties as $difficulty) : ?>
                    <?php 
                        $translatedName = '';
                        if (!empty($difficulty->difficulty_translations)) {
                            $translatedName = $difficulty->difficulty_translations[0]->name;
                        } else {
                            // Fallback if no translation for current lang found
                            // Attempt to load default English translation or the first available?
                            // Since we filtered in controller, we don't have others here.
                            $translatedName = __('Not translated');
                        }
                    ?>
                    <tr>
                        <td class="mf-muted" data-order="<?= h((string)$difficulty->id) ?>"><?= $this->Number->format($difficulty->id) ?></td>
                        <td class="text-start"><?= h($translatedName) ?></td>
                        <td class="mf-muted" data-order="<?= h((string)$difficulty->level) ?>"><?= $this->Number->format($difficulty->level) ?></td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['action' => 'view', $difficulty->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $difficulty->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $difficulty->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id),
                                        'class' => 'btn btn-sm btn-outline-danger',
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfDifficultiesTable',
        'searchInputId' => 'mfDifficultiesSearch',
        'limitSelectId' => 'mfDifficultiesLimit',
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[0, 'asc']],
            'nonOrderableTargets' => [-1],
            'dom' => 'rt<"d-flex align-items-center justify-content-between mt-2"ip>',
        ],
        'vanilla' => [
            'defaultSortCol' => 0,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [3],
            'searchCols' => [0, 1, 2],
        ],
    ],
]) ?>
<?php $this->end(); ?>