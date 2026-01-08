<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Test> $tests
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Tests'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Tests') ?></h1>
    </div>
</div>

<br>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfTestsSearch',
        'label' => __('Search'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfTestsLimit',
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
        'label' => __('New Test') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <div class="mf-admin-table-scroll">
        <table id="mfTestsTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Category') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Difficulty') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Questions') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Public') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Updated') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tests as $test) : ?>
                    <tr>
                        <td class="mf-muted" data-order="<?= h((string)$test->id) ?>"><?= $this->Number->format($test->id) ?></td>
                        <td class="mf-muted">
                            <?= $test->hasValue('category') ? h((string)$test->category->id) : '—' ?>
                        </td>
                        <td class="mf-muted">
                            <?= $test->hasValue('difficulty') ? h((string)$test->difficulty->name) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $test->number_of_questions === null ? '0' : h((string)$test->number_of_questions) ?>">
                            <?= $test->number_of_questions === null ? '—' : $this->Number->format($test->number_of_questions) ?>
                        </td>
                        <td class="mf-muted">
                            <?= $test->is_public ? __('Yes') : __('No') ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $test->created_at ? h($test->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $test->created_at ? h($test->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $test->updated_at ? h($test->updated_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $test->updated_at ? h($test->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['action' => 'view', $test->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $test->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $test->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $test->id),
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
        'tableId' => 'mfTestsTable',
        'searchInputId' => 'mfTestsSearch',
        'limitSelectId' => 'mfTestsLimit',
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[0, 'asc']],
            'nonOrderableTargets' => [-1],
            'nonSearchableTargets' => [3, 4, 5, 6],
            'dom' => 'rt<"d-flex align-items-center justify-content-between mt-2"ip>',
        ],
        'vanilla' => [
            'defaultSortCol' => 0,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [7],
            'searchCols' => [0, 1, 2],
        ],
    ],
]) ?>
<?php $this->end(); ?>