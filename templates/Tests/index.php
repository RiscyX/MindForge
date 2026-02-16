<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Test> $tests
 * @var int|null $roleId
 * @var bool|null $isCreatorCatalog
 * @var array<string, string>|null $filters
 * @var array<int, string>|null $categoryOptions
 * @var array<int, string>|null $difficultyOptions
 */

use App\Model\Entity\Role;

$lang = $this->request->getParam('lang', 'en');

$isCreatorCatalog = (bool)($isCreatorCatalog ?? false);
$isCatalog = !$this->request->getParam('prefix')
    && ((int)($roleId ?? 0) === Role::USER || $isCreatorCatalog);

$this->assign('title', $isCatalog ? __('Quizzes') : __('Tests'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<?php if ($isCatalog) : ?>
    <div class="py-3 py-lg-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h3 mb-1 text-white"><?= $isCreatorCatalog ? __('My Quizzes') : __('Quizzes') ?></h1>
                <div class="mf-muted">
                    <?= $isCreatorCatalog
                        ? __('Manage your quizzes and open each one for detailed stats.')
                        : __('Pick a quiz, skim the description, and start when you are ready.') ?>
                </div>
            </div>
        </div>

        <?php if ($isCreatorCatalog) : ?>
            <?php
            $q = (string)($filters['q'] ?? '');
            $selectedCategory = (string)($filters['category'] ?? '');
            $selectedDifficulty = (string)($filters['difficulty'] ?? '');
            $selectedVisibility = (string)($filters['visibility'] ?? '');
            $selectedSort = (string)($filters['sort'] ?? 'latest');
            ?>
            <div class="mf-admin-card p-3 mt-3">
                <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
                    <div class="col-12 col-xl-3">
                        <?= $this->Form->control('q', [
                            'label' => __('Search'),
                            'value' => $q,
                            'class' => 'form-control',
                            'placeholder' => __('Title or description'),
                        ]) ?>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <?= $this->Form->control('category', [
                            'label' => __('Category'),
                            'type' => 'select',
                            'empty' => __('All'),
                            'options' => $categoryOptions ?? [],
                            'value' => $selectedCategory,
                            'class' => 'form-select',
                        ]) ?>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <?= $this->Form->control('difficulty', [
                            'label' => __('Difficulty'),
                            'type' => 'select',
                            'empty' => __('All'),
                            'options' => $difficultyOptions ?? [],
                            'value' => $selectedDifficulty,
                            'class' => 'form-select',
                        ]) ?>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <?= $this->Form->control('visibility', [
                            'label' => __('Visibility'),
                            'type' => 'select',
                            'options' => [
                                '' => __('All'),
                                'public' => __('Public'),
                                'private' => __('Private'),
                            ],
                            'value' => $selectedVisibility,
                            'class' => 'form-select',
                        ]) ?>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <?= $this->Form->control('sort', [
                            'label' => __('Sort'),
                            'type' => 'select',
                            'options' => [
                                'latest' => __('Latest'),
                                'oldest' => __('Oldest'),
                                'updated' => __('Recently updated'),
                            ],
                            'value' => $selectedSort,
                            'class' => 'form-select',
                        ]) ?>
                    </div>
                    <div class="col-12 col-xl-1 d-grid">
                        <?= $this->Form->button(__('Apply'), ['class' => 'btn btn-primary']) ?>
                    </div>
                    <div class="col-12">
                        <?= $this->Html->link(__('Reset filters'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        <?php endif; ?>

        <div class="mf-quiz-grid mt-3">
            <?php foreach ($tests as $test) : ?>
                <?php
                $title = !empty($test->test_translations) ? (string)$test->test_translations[0]->title : '';
                $description = !empty($test->test_translations) ? (string)$test->test_translations[0]->description : '';
                $category = $test->hasValue('category') && !empty($test->category->category_translations)
                    ? (string)$test->category->category_translations[0]->name
                    : '';
                $difficulty = $test->hasValue('difficulty') && !empty($test->difficulty->difficulty_translations)
                    ? (string)$test->difficulty->difficulty_translations[0]->name
                    : '';

                $difficultyClass = 'mf-quiz-diff--default';
                $did = (int)($test->difficulty_id ?? 0);
                $rank = $did > 0 && isset($difficultyRanks) && is_array($difficultyRanks) && isset($difficultyRanks[$did])
                    ? (int)$difficultyRanks[$did]
                    : null;
                $count = isset($difficultyCount) ? (int)$difficultyCount : 0;
                if ($rank !== null && $count > 1) {
                    $p = $rank / max(1, $count - 1);
                    if ($p <= 0.33) {
                        $difficultyClass = 'mf-quiz-diff--easy';
                    } elseif ($p <= 0.66) {
                        $difficultyClass = 'mf-quiz-diff--medium';
                    } else {
                        $difficultyClass = 'mf-quiz-diff--hard';
                    }
                }

                // Keep accents strictly within the app's base palette.
                // We only vary intensity per card to avoid "samey" repetition.
                $variant = ((int)$test->id) % 3;
                $accentAlpha = match ($variant) {
                    0 => '0.22',
                    1 => '0.16',
                    default => '0.12',
                };
    ?>
                <article class="mf-quiz-card" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: <?= h($accentAlpha) ?>;">
                    <div class="mf-quiz-card__cover">
                        <div class="mf-quiz-card__cover-inner">
                            <div class="mf-quiz-card__icon" aria-hidden="true">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </div>

                            <div class="mf-quiz-card__cover-meta">
                                <?php if ($category !== '') : ?>
                                    <div class="mf-quiz-card__category" title="<?= h($category) ?>">
                                        <?= h($category) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mf-quiz-card__rightmeta">
                                <?php if ($difficulty !== '') : ?>
                                    <span class="mf-quiz-diff <?= h($difficultyClass) ?>" title="<?= h($difficulty) ?>">
                                        <span class="mf-quiz-diff__dot" aria-hidden="true"></span>
                                        <span class="mf-quiz-diff__text"><?= h($difficulty) ?></span>
                                    </span>
                                <?php endif; ?>
                                <?php if ($test->number_of_questions !== null) : ?>
                                    <div class="mf-quiz-stat" title="<?= h(__('Questions')) ?>">
                                        <i class="bi bi-list-ol" aria-hidden="true"></i>
                                        <span class="mf-quiz-stat__value"><?= (int)$test->number_of_questions ?></span>
                                        <span class="mf-quiz-stat__label"><?= __('questions') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mf-quiz-card__content">
                        <div class="mf-quiz-card__title">
                            <?= $title !== '' ? h($title) : __('Untitled quiz') ?>
                        </div>
                        <p class="mf-quiz-card__desc">
                            <?= $description !== '' ? h($description) : __('No description yet.') ?>
                        </p>
                    </div>

                    <div class="mf-quiz-card__actions">
                        <?php if ($isCreatorCatalog) : ?>
                            <?= $this->Html->link(
                                __('Open details'),
                                ['action' => 'details', $test->id, 'lang' => $lang],
                                ['class' => 'btn btn-primary mf-quiz-card__cta'],
                            ) ?>
                            <?= $this->Html->link(
                                __('Edit'),
                                ['action' => 'edit', $test->id, 'lang' => $lang],
                                ['class' => 'btn btn-outline-light mf-quiz-card__secondary'],
                            ) ?>
                        <?php else : ?>
                            <?= $this->Form->postLink(
                                __('Start quiz'),
                                ['action' => 'start', $test->id, 'lang' => $lang],
                                ['class' => 'btn btn-primary mf-quiz-card__cta'],
                            ) ?>
                            <?= $this->Html->link(
                                __('Open details'),
                                ['action' => 'details', $test->id, 'lang' => $lang],
                                ['class' => 'btn btn-outline-light mf-quiz-card__secondary'],
                            ) ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

<?php else : ?>
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
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang],
        'id' => 'mfTestsBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfTestsTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Title') ?></th>
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
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$test->id) ?>"
                                aria-label="<?= h(__('Select test')) ?>"
                            />
                        </td>
                        <td class="mf-muted" data-order="<?= h((string)$test->id) ?>"><?= $this->Number->format($test->id) ?></td>
                        <td class="mf-muted">
                            <?= !empty($test->test_translations) ? h($test->test_translations[0]->title) : __('N/A') ?>
                        </td>
                        <td class="mf-muted">
                            <?= $test->hasValue('category') && !empty($test->category->category_translations) ? h($test->category->category_translations[0]->name) : __('N/A') ?>
                        </td>
                        <td class="mf-muted">
                            <?= $test->hasValue('difficulty') && !empty($test->difficulty->difficulty_translations) ? h($test->difficulty->difficulty_translations[0]->name) : __('N/A') ?>
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

    <?= $this->Form->end() ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-2">
    <?= $this->element('functions/admin_bulk_controls', [
        'containerClass' => 'd-flex align-items-center gap-3 flex-wrap',
        'selectAll' => [
            'checkboxId' => 'mfTestsSelectAll',
            'linkId' => 'mfTestsSelectAllLink',
            'text' => __('Összes bejelölése'),
        ],
        'bulk' => [
            'label' => __('A kijelöltekkel végzendő művelet:'),
            'formId' => 'mfTestsBulkForm',
            'buttons' => [
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
        <div id="mfTestsPagination"></div>
    </nav>
</div>

    <?php $this->start('script'); ?>
    <?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfTestsTable',
        'searchInputId' => 'mfTestsSearch',
        'limitSelectId' => 'mfTestsLimit',
        'bulkFormId' => 'mfTestsBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfTestsSelectAll',
        'selectAllLinkId' => 'mfTestsSelectAllLink',
        'paginationContainerId' => 'mfTestsPagination',
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
            'nonSearchableTargets' => [0, 4, 5, 6, 7, -1],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [0, 8],
            'searchCols' => [1, 2, 3],
        ],
    ],
    ]) ?>
    <?php $this->end(); ?>

<?php endif; ?>
