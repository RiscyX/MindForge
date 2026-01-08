<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Question> $questions
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Questions'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Questions') ?></h1>
    </div>
</div>

<br>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfQuestionsSearch',
        'label' => __('Search'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfQuestionsLimit',
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
        'label' => __('New Question') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <div class="mf-admin-table-scroll">
        <table id="mfQuestionsTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Source') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Active') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Updated') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $question) : ?>
                    <tr>
                        <td class="mf-muted" data-order="<?= h((string)$question->id) ?>"><?= $this->Number->format($question->id) ?></td>
                        <td class="mf-muted"><?= h((string)$question->source_type) ?></td>
                        <td>
                            <?php if ($question->is_active) : ?>
                                <span class="badge bg-success"><?= __('Active') ?></span>
                            <?php else : ?>
                                <span class="badge bg-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $question->created_at ? h($question->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $question->created_at ? h($question->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $question->updated_at ? h($question->updated_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $question->updated_at ? h($question->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['action' => 'view', $question->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $question->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $question->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $question->id),
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
        'tableId' => 'mfQuestionsTable',
        'searchInputId' => 'mfQuestionsSearch',
        'limitSelectId' => 'mfQuestionsLimit',
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