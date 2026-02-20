<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Test> $tests
 * @var int|null $roleId
 * @var bool|null $isCreatorCatalog
 * @var array<string, string>|null $filters
 * @var array<int, string>|null $categoryOptions
 * @var array<int, string>|null $difficultyOptions
 * @var array<string, mixed>|null $catalogPagination
 * @var array<int, \App\Model\Entity\TestAttempt>|null $recentAttempts
 * @var array<int, array<string, mixed>>|null $topQuizzes
 * @var array<int, array<string, mixed>>|null $topCategories
 */

$lang = $this->request->getParam('lang', 'en');

$isCreatorCatalog = (bool)($isCreatorCatalog ?? false);
$prefix = (string)$this->request->getParam('prefix', '');
$isCatalog = $prefix === '' || $prefix === 'QuizCreator';

$catalogPagination = is_array($catalogPagination ?? null) ? $catalogPagination : [];
$page = (int)($catalogPagination['page'] ?? 1);
$perPage = (int)($catalogPagination['perPage'] ?? 12);
$perPageOptions = (array)($catalogPagination['perPageOptions'] ?? [12, 24, 48]);
$totalItems = (int)($catalogPagination['totalItems'] ?? 0);
$totalPages = (int)($catalogPagination['totalPages'] ?? 1);
$queryParams = (array)$this->request->getQueryParams();
$recentAttempts = is_array($recentAttempts ?? null) ? $recentAttempts : [];
$topQuizzes = is_array($topQuizzes ?? null) ? $topQuizzes : [];
$topCategories = is_array($topCategories ?? null) ? $topCategories : [];

$q = (string)($filters['q'] ?? '');
$selectedCategory = (string)($filters['category'] ?? '');
$selectedDifficulty = (string)($filters['difficulty'] ?? '');
$selectedVisibility = (string)($filters['visibility'] ?? '');
$selectedSort = (string)($filters['sort'] ?? 'latest');

$catalogTests = $isCatalog
    ? (is_array($tests) ? $tests : iterator_to_array($tests))
    : [];

$this->assign('title', $isCatalog ? __('Quizzes') : __('Tests'));
$this->Html->css('tests_catalog', ['block' => 'css']);

if (!$this->request->getParam('prefix')) {
    $this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
    $this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
    $this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
    $this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
}
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

        <?php if (!$isCreatorCatalog && ($topQuizzes || $topCategories)) : ?>
            <div class="row g-3 mt-3">
                <?php if ($topQuizzes) : ?>
                    <div class="col-12 col-xl-7">
                        <section class="mf-quiz-card mf-top-quizzes" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: 0.14;" aria-label="<?= h(__('Top quizzes')) ?>">
                            <div class="mf-quiz-card__cover">
                                <div class="mf-quiz-card__cover-inner">
                                    <div class="mf-quiz-card__icon" aria-hidden="true">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>
                                    <div class="mf-quiz-card__cover-meta">
                                        <h2 class="h5 text-white mb-1"><?= __('Top 3 quizzes') ?></h2>
                                        <div class="mf-muted small"><?= __('Most played') ?></div>
                                    </div>
                                    <div class="mf-quiz-card__rightmeta">
                                        <span class="mf-top-quizzes__count">
                                            <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                            <?= h((string)count($topQuizzes)) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mf-quiz-card__content pt-0">
                                <div class="mf-top-quizzes__list">
                                <?php foreach ($topQuizzes as $idx => $topQuiz) : ?>
                                    <?php
                                    $topTitle = (string)($topQuiz['title'] ?? __('Untitled quiz'));
                                    $topCategory = (string)($topQuiz['category_name'] ?? __('Uncategorized'));
                                    $topDifficulty = trim((string)($topQuiz['difficulty_name'] ?? ''));
                                    $topAttempts = (int)($topQuiz['attempt_count'] ?? 0);
                                    $topQuizId = (int)($topQuiz['id'] ?? 0);
                                    ?>
                                    <article class="mf-top-quiz-item">
                                        <div class="mf-top-quiz-item__left">
                                            <span class="mf-top-quiz-item__rank">#<?= h((string)($idx + 1)) ?></span>
                                            <div class="mf-top-quiz-item__meta">
                                                <div class="mf-top-quiz-item__title" title="<?= h($topTitle) ?>"><?= h($topTitle) ?></div>
                                                <div class="mf-top-quiz-item__sub">
                                                    <span><i class="bi bi-tag" aria-hidden="true"></i> <?= h($topCategory) ?></span>
                                                    <?php if ($topDifficulty !== '') : ?>
                                                        <span><i class="bi bi-speedometer2" aria-hidden="true"></i> <?= h($topDifficulty) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mf-top-quiz-item__right">
                                            <span class="mf-top-quiz-item__attempts" title="<?= h(__('Attempts')) ?>">
                                                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                                <?= h((string)$topAttempts) ?>
                                            </span>
                                            <?php if ($topQuizId > 0) : ?>
                                                <?= $this->Html->link(
                                                    __('Open'),
                                                    ['controller' => 'Tests', 'action' => 'details', $topQuizId, 'lang' => $lang],
                                                    ['class' => 'btn btn-sm btn-outline-light'],
                                                ) ?>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    </div>
                <?php endif; ?>

                <?php if ($topCategories) : ?>
                    <div class="col-12 col-xl-5">
                        <section class="mf-quiz-card mf-top-categories" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: 0.12;" aria-label="<?= h(__('Top categories')) ?>">
                            <div class="mf-quiz-card__cover">
                                <div class="mf-quiz-card__cover-inner">
                                    <div class="mf-quiz-card__icon" aria-hidden="true">
                                        <i class="bi bi-tags-fill"></i>
                                    </div>
                                    <div class="mf-quiz-card__cover-meta">
                                        <h2 class="h5 text-white mb-1"><?= __('Top 5 categories') ?></h2>
                                        <div class="mf-muted small"><?= __('By attempts') ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="mf-quiz-card__content pt-0">
                                <div class="mf-top-categories__list">
                                    <?php foreach ($topCategories as $idx => $topCategory) : ?>
                                        <?php
                                        $categoryName = (string)($topCategory['name'] ?? __('Uncategorized'));
                                        $attemptCount = (int)($topCategory['attempt_count'] ?? 0);
                                        ?>
                                        <article class="mf-top-category-item">
                                            <div class="mf-top-category-item__left">
                                                <span class="mf-top-category-item__rank">#<?= h((string)($idx + 1)) ?></span>
                                                <div class="mf-top-category-item__name" title="<?= h($categoryName) ?>"><?= h($categoryName) ?></div>
                                            </div>
                                            <span class="mf-top-category-item__attempts">
                                                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                                                <?= h((string)$attemptCount) ?>
                                            </span>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$isCreatorCatalog) : ?>
            <div class="mf-quiz-card mf-attempt-widget mt-3" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: 0.12;">
                <div class="mf-attempt-widget__head">
                    <div>
                        <h2 class="h5 mb-1 text-white"><?= __('Recent attempts') ?></h2>
                        <div class="mf-muted small"><?= __('Quick snapshot of your latest quiz results.') ?></div>
                    </div>
                    <?= $this->Html->link(
                        __('View all attempts'),
                        ['controller' => 'Users', 'action' => 'stats', 'lang' => $lang],
                        ['class' => 'btn btn-sm btn-outline-light'],
                    ) ?>
                </div>

                <?php if ($recentAttempts) : ?>
                    <div class="mf-attempt-list mt-3">
                        <?php foreach ($recentAttempts as $attempt) : ?>
                            <?php
                            $categoryName = $attempt->hasValue('category') && !empty($attempt->category->category_translations)
                                ? (string)$attempt->category->category_translations[0]->name
                                : __('Uncategorized');
                            $quizTitle = $attempt->hasValue('test') && !empty($attempt->test->test_translations)
                                ? (string)$attempt->test->test_translations[0]->title
                                : __('Quiz');
                            $scoreValue = $attempt->score !== null ? (float)$attempt->score : 0.0;
                            $scoreLabel = $attempt->score !== null
                                ? rtrim(rtrim((string)$attempt->score, '0'), '.') . '%'
                                : '—';
                            $scoreTone = $scoreValue >= 80 ? 'good' : ($scoreValue >= 50 ? 'mid' : 'low');
                            ?>
                            <article class="mf-attempt-item mf-attempt-item--<?= h($scoreTone) ?>">
                                <div class="mf-attempt-item__left">
                                    <div class="mf-attempt-item__title"><?= h($quizTitle) ?></div>
                                    <div class="mf-attempt-item__meta">
                                        <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= $attempt->finished_at ? h($attempt->finished_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></span>
                                        <span><i class="bi bi-tag" aria-hidden="true"></i> <?= h($categoryName) ?></span>
                                    </div>
                                </div>

                                <div class="mf-attempt-item__right">
                                    <div class="mf-attempt-item__score" title="<?= h(__('Score')) ?>"><?= h($scoreLabel) ?></div>
                                    <?= $this->Html->link(
                                        __('Open result'),
                                        ['controller' => 'Tests', 'action' => 'result', $attempt->id, 'lang' => $lang],
                                        ['class' => 'btn btn-sm btn-primary'],
                                    ) ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="mf-attempt-empty mt-3">
                        <div class="mf-attempt-empty__title"><?= __('No attempts yet') ?></div>
                        <div class="mf-muted mb-3"><?= __('Start your first test and your latest results will appear here.') ?></div>
                        <div class="d-flex gap-2 flex-wrap">
                            <?= $this->Html->link(
                                __('Start test'),
                                '#mf-catalog-quizzes',
                                ['class' => 'btn btn-primary'],
                            ) ?>
                            <?= $this->Html->link(
                                __('View all attempts'),
                                ['controller' => 'Users', 'action' => 'stats', 'lang' => $lang],
                                ['class' => 'btn btn-outline-light'],
                            ) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mf-admin-card p-3 mt-3">
                <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
                    <input type="hidden" name="per_page" value="<?= h((string)$perPage) ?>">

                    <div class="col-12 col-xl-4">
                        <?= $this->Form->control('q', [
                            'label' => __('Search'),
                            'value' => $q,
                            'class' => 'form-control',
                            'placeholder' => __('Title or description'),
                        ]) ?>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label" for="mf-category-combobox-input"><?= __('Category') ?></label>
                        <?= $this->Form->hidden('category', ['id' => 'mf-category-id-hidden', 'value' => $selectedCategory !== '' ? (int)$selectedCategory : null]) ?>
                        <div class="mf-test-combobox" id="mf-category-combobox" data-mf-combobox="category-filter">
                            <input
                                id="mf-category-combobox-input"
                                type="text"
                                class="form-control"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="<?= h(__('Start typing category...')) ?>"
                                value="<?= h($selectedCategory !== '' && isset($categoryOptions[(int)$selectedCategory]) ? (string)$categoryOptions[(int)$selectedCategory] : '') ?>"
                                aria-expanded="false"
                                aria-controls="mf-category-combobox-list"
                            >
                            <div class="mf-test-combobox__panel" id="mf-category-combobox-list" role="listbox"></div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
                        <?= $this->Form->control('difficulty', [
                            'label' => __('Difficulty'),
                            'type' => 'select',
                            'empty' => __('All'),
                            'options' => $difficultyOptions ?? [],
                            'value' => $selectedDifficulty,
                            'class' => 'form-select',
                        ]) ?>
                    </div>

                    <div class="col-12 col-md-6 col-xl-2">
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
                        <?= $this->Form->button(__('Apply'), ['class' => 'btn mf-catalog-apply-btn']) ?>
                    </div>
                    <div class="col-12">
                        <?= $this->Html->link(
                            __('Reset filters'),
                            ['action' => 'index', 'lang' => $lang, '?' => ['per_page' => $perPage]],
                            ['class' => 'btn btn-sm btn-outline-light mf-catalog-reset-btn'],
                        ) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        <?php endif; ?>

        <?php if ($isCreatorCatalog) : ?>
            <div class="mf-admin-card p-3 mt-3">
                <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
                    <input type="hidden" name="per_page" value="<?= h((string)$perPage) ?>">
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
                        <?= $this->Form->button(__('Apply'), ['class' => 'btn mf-catalog-apply-btn']) ?>
                    </div>
                    <div class="col-12">
                        <?= $this->Html->link(__('Reset filters'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light mf-catalog-reset-btn']) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        <?php endif; ?>

        <div id="mf-catalog-quizzes" class="mf-quiz-grid mt-3">
            <?php foreach ($catalogTests as $test) : ?>
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
                                __('Stats'),
                                ['action' => 'stats', $test->id, 'lang' => $lang],
                                ['class' => 'btn btn-outline-light mf-quiz-card__secondary'],
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

            <?php if (count($catalogTests) === 0) : ?>
                <div class="mf-admin-card p-4">
                    <div class="h5 text-white mb-2"><?= __('No quizzes found') ?></div>
                    <div class="mf-muted mb-3">
                        <?= $isCreatorCatalog
                            ? __('You have not created any quizzes yet.')
                            : __('There are no available quizzes right now.') ?>
                    </div>
                    <?= $this->Html->link(
                        $isCreatorCatalog ? __('Create quiz') : __('Start a quiz'),
                        $isCreatorCatalog
                            ? ['controller' => 'Tests', 'action' => 'add', 'lang' => $lang]
                            : ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                        ['class' => 'btn btn-primary'],
                    ) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php
        $shownCount = count($catalogTests);
        $windowSize = 5;
        $startPage = max(1, $page - (int)floor($windowSize / 2));
        $endPage = min($totalPages, $startPage + $windowSize - 1);
        $startPage = max(1, $endPage - $windowSize + 1);
        ?>
        <div class="mf-catalog-footer d-flex align-items-center justify-content-between gap-3 flex-wrap mt-3">
            <div class="mf-catalog-footer__meta mf-muted small">
                <?= __('Showing {0} of {1}', $shownCount, $totalItems) ?>
            </div>

            <div class="mf-catalog-per-page">
                <?= $this->Form->create(null, ['type' => 'get', 'class' => 'd-flex align-items-center gap-2']) ?>
                    <?php foreach ($queryParams as $queryKey => $queryValue) : ?>
                        <?php if ($queryKey === 'per_page' || $queryKey === 'page') : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php if (is_scalar($queryValue)) : ?>
                            <input type="hidden" name="<?= h((string)$queryKey) ?>" value="<?= h((string)$queryValue) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <label class="small mf-muted" for="mfCatalogPerPage"><?= __('Show') ?></label>
                    <select id="mfCatalogPerPage" name="per_page" class="form-select form-select-sm">
                        <?php foreach ($perPageOptions as $option) : ?>
                            <?php $optionValue = (int)$option; ?>
                            <option value="<?= $optionValue ?>" <?= $perPage === $optionValue ? 'selected' : '' ?>>
                                <?= $optionValue ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm mf-catalog-apply-btn mf-catalog-apply-btn--compact"><?= __('Apply') ?></button>
                <?= $this->Form->end() ?>
            </div>

            <?php if ($totalPages > 1) : ?>
                <nav class="mf-catalog-pagination" aria-label="<?= h(__('Quizzes pagination')) ?>">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <?= $this->Html->link(
                                __('Previous'),
                                ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => max(1, $page - 1), 'per_page' => $perPage])],
                                ['class' => 'page-link'],
                            ) ?>
                        </li>

                        <?php for ($p = $startPage; $p <= $endPage; $p++) : ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <?= $this->Html->link(
                                    (string)$p,
                                    ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => $p, 'per_page' => $perPage])],
                                    ['class' => 'page-link'],
                                ) ?>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <?= $this->Html->link(
                                __('Next'),
                                ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => min($totalPages, $page + 1), 'per_page' => $perPage])],
                                ['class' => 'page-link'],
                            ) ?>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>

        <?php if (!$isCreatorCatalog) : ?>
            <?php
            $catalogFilterConfig = [
                'categoryComboboxMap' => $categoryOptions ?? [],
                'categoryComboboxSelectedId' => $selectedCategory !== '' ? (int)$selectedCategory : 0,
                'categoryComboboxAllLabel' => __('All categories'),
                'categoryComboboxNoResults' => __('No category found'),
                'categoryComboboxInvalid' => __('Please choose a category from the list.'),
            ];
            $catalogFilterConfigJson = json_encode(
                $catalogFilterConfig,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES,
            );
            if ($catalogFilterConfigJson === false) {
                $catalogFilterConfigJson = '{}';
            }
            ?>
            <script type="application/json" id="mf-tests-catalog-filter-config"><?= $catalogFilterConfigJson ?></script>
            <?= $this->Html->script('tests_catalog_filters') ?>
        <?php endif; ?>
    </div>

<?php else : ?>
<?php
$allTests = is_array($tests) ? $tests : iterator_to_array($tests);
$totalTests = count($allTests);
$publicCount = 0;
$privateCount = 0;
foreach ($allTests as $_t) {
    if (!empty($_t->is_public)) {
        $publicCount++;
    } else {
        $privateCount++;
    }
}
?>

<header class="mf-page-header">
    <div class="mf-page-header__left">
        <div>
            <h1 class="mf-page-header__title">
                <i class="bi bi-journal-check me-2 text-primary" aria-hidden="true"></i>
                <?= __('Tests') ?>
                <span class="mf-page-header__count"><?= $this->Number->format($totalTests) ?></span>
            </h1>
            <p class="mf-page-header__sub"><?= __('Browse, edit and manage all quizzes in the system.') ?></p>
        </div>
    </div>
</header>

<div class="row g-3 mb-3 mf-admin-kpi-grid">
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-journal-text mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Total') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($totalTests) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-eye mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Public') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($publicCount) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-eye-slash mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Private') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($privateCount) ?></div>
            </div>
        </div>
    </div>
</div>

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
                            <?php if ($test->is_public) : ?>
                                <span class="badge bg-success"><?= __('Yes') ?></span>
                            <?php else : ?>
                                <span class="badge bg-secondary"><?= __('No') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $test->created_at ? h($test->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $test->created_at ? h($test->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $test->updated_at ? h($test->updated_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $test->updated_at ? h($test->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td>
                            <div class="mf-admin-actions">
                                <?= $this->Html->link(
                                    '<i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>' . h(__('Stats')) . '</span>',
                                    ['action' => 'stats', $test->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm mf-admin-action mf-admin-action--neutral', 'escape' => false],
                                ) ?>
                                <?= $this->Html->link(
                                    '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>' . h(__('Edit')) . '</span>',
                                    ['action' => 'edit', $test->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm mf-admin-action mf-admin-action--neutral', 'escape' => false],
                                ) ?>
                                <?= $this->Form->postLink(
                                    '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
                                    ['action' => 'delete', $test->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $test->id),
                                        'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                                        'escape' => false,
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($tests) === 0) : ?>
                    <?= $this->element('functions/admin_empty_state', [
                        'message' => __('No tests found.'),
                        'ctaUrl' => ['action' => 'add', 'lang' => $lang],
                        'ctaLabel' => __('New Test'),
                    ]) ?>
                <?php endif; ?>
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
            'text' => __('Select all'),
        ],
        'bulk' => [
            'label' => __('Action for selected items:'),
            'formId' => 'mfTestsBulkForm',
            'buttons' => [
                [
                    'label' => '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
                    'value' => 'delete',
                    'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                    'escapeTitle' => false,
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
