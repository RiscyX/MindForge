<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Question> $questions
 * @var array<string, string> $filters
 * @var array<int, string> $categoryOptions
 * @var array<string, string> $questionTypeOptions
 * @var array<string, string> $sourceTypeOptions
 * @var array<string, string> $activeOptions
 */

$lang = $this->request->getParam('lang', 'en');
$filters = is_array($filters ?? null) ? $filters : [];
$queryParams = $this->request->getQueryParams();

$selectedCategory = (string)($filters['category'] ?? '');
$selectedQuestionType = (string)($filters['question_type'] ?? '');
$selectedIsActive = (string)($filters['is_active'] ?? '');
$selectedSourceType = (string)($filters['source_type'] ?? '');

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

<div class="mf-admin-card p-3 mt-3">
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
        <div class="col-12 col-lg-3">
            <?= $this->Form->control('category', [
                'label' => __('Category'),
                'type' => 'select',
                'empty' => __('All'),
                'options' => $categoryOptions ?? [],
                'value' => $selectedCategory,
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-12 col-lg-3">
            <?= $this->Form->control('question_type', [
                'label' => __('Question Type'),
                'type' => 'select',
                'empty' => __('All'),
                'options' => $questionTypeOptions ?? [],
                'value' => $selectedQuestionType,
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-12 col-lg-2">
            <?= $this->Form->control('is_active', [
                'label' => __('Status'),
                'type' => 'select',
                'empty' => __('All'),
                'options' => $activeOptions ?? [],
                'value' => $selectedIsActive,
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-12 col-lg-2">
            <?= $this->Form->control('source_type', [
                'label' => __('Source Type'),
                'type' => 'select',
                'empty' => __('All'),
                'options' => $sourceTypeOptions ?? [],
                'value' => $selectedSourceType,
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-12 col-lg-1 d-grid">
            <?= $this->Form->button(__('Apply'), ['class' => 'btn btn-primary']) ?>
        </div>
        <div class="col-12 col-lg-1 d-grid">
            <?= $this->Html->link(__('Reset'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
        </div>
    <?= $this->Form->end() ?>
</div>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang],
        'id' => 'mfQuestionsBulkForm',
    ]) ?>
    <input type="hidden" name="return_filters[category]" value="<?= h($selectedCategory) ?>">
    <input type="hidden" name="return_filters[question_type]" value="<?= h($selectedQuestionType) ?>">
    <input type="hidden" name="return_filters[is_active]" value="<?= h($selectedIsActive) ?>">
    <input type="hidden" name="return_filters[source_type]" value="<?= h($selectedSourceType) ?>">

    <div class="mf-admin-table-scroll">
        <table id="mfQuestionsTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Content') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Category') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Difficulty') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Type') ?></th>
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
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$question->id) ?>"
                                aria-label="<?= h(__('Select question')) ?>"
                            />
                        </td>
                        <td class="mf-muted" data-order="<?= h((string)$question->id) ?>"><?= $this->Number->format($question->id) ?></td>
                        <td class="mf-muted" style="text-align:left;">
                            <?php
                            $content = null;
                            if (!empty($question->question_translations)) {
                                $content = $question->question_translations[0]->content ?? null;
                            }
                            ?>
                            <?= $content ? h((string)$content) : __('N/A') ?>
                        </td>
                        <td class="mf-muted">
                            <?php
                            $catName = null;
                            if ($question->hasValue('category') && !empty($question->category->category_translations)) {
                                $catName = $question->category->category_translations[0]->name ?? null;
                            }
                            ?>
                            <?= $catName ? h((string)$catName) : __('N/A') ?>
                        </td>
                        <td class="mf-muted">
                            <?php
                            $diffName = null;
                            if ($question->hasValue('difficulty') && !empty($question->difficulty->difficulty_translations)) {
                                $diffName = $question->difficulty->difficulty_translations[0]->name ?? null;
                            }
                            ?>
                            <?= $diffName ? h((string)$diffName) : __('N/A') ?>
                        </td>
                        <td class="mf-muted">
                            <?php
                            $questionType = (string)($question->question_type ?? '');
                            $questionTypeLabel = $questionTypeOptions[$questionType] ?? $questionType;
                            ?>
                            <?= $questionTypeLabel !== '' ? h((string)$questionTypeLabel) : __('N/A') ?>
                        </td>
                        <td>
                            <?php if ((string)$question->source_type === 'ai') : ?>
                                <span class="badge bg-info text-dark"><?= __('AI') ?></span>
                            <?php elseif ((string)$question->source_type === 'human') : ?>
                                <span class="badge bg-primary"><?= __('Human') ?></span>
                            <?php else : ?>
                                <span class="badge bg-secondary"><?= h((string)$question->source_type) ?></span>
                            <?php endif; ?>
                        </td>
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
                                    ['action' => 'view', $question->id, 'lang' => $lang, '?' => $queryParams],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $question->id, 'lang' => $lang, '?' => $queryParams],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Form->postLink(
                                    $question->is_active ? __('Deactivate') : __('Activate'),
                                    ['action' => 'toggleActive', $question->id, 'lang' => $lang, '?' => $queryParams],
                                    [
                                        'class' => $question->is_active
                                            ? 'btn btn-sm btn-outline-warning'
                                            : 'btn btn-sm btn-outline-success',
                                    ],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $question->id, 'lang' => $lang, '?' => $queryParams],
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

    <?= $this->Form->end() ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-2">
    <?= $this->element('functions/admin_bulk_controls', [
        'containerClass' => 'd-flex align-items-center gap-3 flex-wrap',
        'selectAll' => [
            'checkboxId' => 'mfQuestionsSelectAll',
            'linkId' => 'mfQuestionsSelectAllLink',
            'text' => __('Összes bejelölése'),
        ],
        'bulk' => [
            'label' => __('A kijelöltekkel végzendő művelet:'),
            'formId' => 'mfQuestionsBulkForm',
                'buttons' => [
                    [
                        'label' => __('Activate'),
                        'value' => 'activate',
                        'class' => 'btn btn-sm btn-outline-success',
                    ],
                    [
                        'label' => __('Deactivate'),
                        'value' => 'deactivate',
                        'class' => 'btn btn-sm btn-outline-warning',
                    ],
                    [
                        'label' => __('Delete'),
                        'value' => 'delete',
                        'class' => 'btn btn-sm btn-outline-danger',
                        'attrs' => [
                            'data-mf-bulk-delete' => true,
                        ],
                    ],
            ],
        ],
    ]) ?>

    <nav aria-label="<?= h(__('Pagination')) ?>">
        <div id="mfQuestionsPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfQuestionsTable',
        'searchInputId' => 'mfQuestionsSearch',
        'limitSelectId' => 'mfQuestionsLimit',
        'bulkFormId' => 'mfQuestionsBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfQuestionsSelectAll',
        'selectAllLinkId' => 'mfQuestionsSelectAllLink',
        'paginationContainerId' => 'mfQuestionsPagination',
        'pagination' => [
            'windowSize' => 3,
            'jumpSize' => 3,
        ],
        'strings' => [
            'selectAtLeastOne' => (string)__('Select at least one item.'),
            'confirmDelete' => (string)__('Are you sure you want to delete the selected items?'),
        ],
        'bulkDeleteValues' => ['delete'],
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[1, 'asc']],
            'nonOrderableTargets' => [0, -1],
            'nonSearchableTargets' => [3, 4, 5, 7, 8],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [0, 10],
            'searchCols' => [1, 2, 6, 7],
        ],
    ],
]) ?>
<?php $this->end(); ?>
