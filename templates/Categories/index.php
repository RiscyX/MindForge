<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Category> $categories
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Categories'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Categories') ?></h1>
    </div>
</div>

<br>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfCategoriesSearch',
        'label' => __('Search by name or id'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfCategoriesLimit',
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
        'label' => __('New Category') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <div class="mf-admin-table-scroll">
        <table id="mfCategoriesTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Names') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Active') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Updated') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category) : ?>
                    <tr>
                        <td class="mf-muted" data-order="<?= h((string)$category->id) ?>"><?= $this->Number->format($category->id) ?></td>
                        <td class="text-start">
                            <?php foreach ($category->category_translations as $translation) : ?>
                                <?php if ($translation->hasValue('language')) : ?>
                                    <div>
                                        <span class="badge bg-secondary me-1"><?= h($translation->language->code) ?></span>
                                        <?= h($translation->name) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($category->is_active) : ?>
                                <span class="badge bg-success"><?= __('Active') ?></span>
                            <?php else : ?>
                                <span class="badge bg-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $category->created_at ? h($category->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $category->created_at ? h($category->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $category->updated_at ? h($category->updated_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $category->updated_at ? h($category->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['action' => 'view', $category->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $category->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $category->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $category->id),
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
        'tableId' => 'mfCategoriesTable',
        'searchInputId' => 'mfCategoriesSearch',
        'limitSelectId' => 'mfCategoriesLimit',
        'strings' => [
            'selectAtLeastOne' => (string)__('Select at least one item.'),
            'confirmDelete' => (string)__('Are you sure you want to delete the selected items?'),
        ],
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[0, 'asc']],
            'nonOrderableTargets' => [-1],
            'nonSearchableTargets' => [2, 3, 4],
            'dom' => 'rt<"d-flex align-items-center justify-content-between mt-2"ip>',
        ],
        'vanilla' => [
            'defaultSortCol' => 0,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [5],
            'searchCols' => [0, 1],
        ],
    ],
]) ?>
<?php $this->end(); ?>