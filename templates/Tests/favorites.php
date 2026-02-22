<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $favoriteItems
 * @var array<string, int> $pagination
 */

$lang = (string)$this->request->getParam('lang', 'en');
$this->assign('title', __('My Favorite Quizzes'));
$this->Html->css('tests_catalog.css?v=4', ['block' => 'css']);

$favoriteItems = is_array($favoriteItems ?? null) ? $favoriteItems : [];
$pagination = is_array($pagination ?? null) ? $pagination : ['total' => 0, 'page' => 1, 'limit' => 12, 'total_pages' => 1];
$page = max(1, (int)($pagination['page'] ?? 1));
$totalPages = max(1, (int)($pagination['total_pages'] ?? 1));
$total = max(0, (int)($pagination['total'] ?? 0));

?>

<div class="py-3 py-lg-4">
    <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
        <div class="mf-catalog-hero-icon" aria-hidden="true"><i class="bi bi-heart-fill"></i></div>
        <div>
            <h1 class="h2 fw-bold mb-0 text-white"><?= __('My Favorite Quizzes') ?></h1>
            <div class="mf-muted mt-1"><?= __('Saved public quizzes you can quickly open or start.') ?></div>
        </div>
    </div>

    <?php if (count($favoriteItems) === 0) : ?>
        <div class="mf-admin-card p-4">
            <div class="h5 text-white mb-2"><?= __('No favorites yet') ?></div>
            <div class="mf-muted mb-3"><?= __('Open a quiz and click "Save to favorites" to collect it here.') ?></div>
            <?= $this->Html->link(
                __('Browse quizzes'),
                ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-primary'],
            ) ?>
        </div>
    <?php else : ?>
        <div class="mf-quiz-grid mt-3">
            <?php foreach ($favoriteItems as $item) : ?>
                <?php
                $test = (array)($item['test'] ?? []);
                $testId = (int)($test['id'] ?? 0);
                $title = (string)($test['title'] ?? __('Untitled quiz'));
                $description = (string)($test['description'] ?? '');
                $category = trim((string)($test['category'] ?? ''));
                $difficulty = trim((string)($test['difficulty'] ?? ''));
                $questionsCount = isset($test['number_of_questions']) ? (int)$test['number_of_questions'] : null;
                ?>
                <article class="mf-quiz-card" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: 0.16;">
                    <div class="mf-quiz-card__cover">
                        <div class="mf-quiz-card__cover-inner">
                            <div class="mf-quiz-card__icon" aria-hidden="true">
                                <i class="bi bi-heart-fill"></i>
                            </div>
                            <div class="mf-quiz-card__cover-meta">
                                <?php if ($category !== '') : ?>
                                    <div class="mf-quiz-card__category" title="<?= h($category) ?>"><?= h($category) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mf-quiz-card__rightmeta">
                                <?php if ($difficulty !== '') : ?>
                                    <span class="mf-quiz-diff mf-quiz-diff--default" title="<?= h($difficulty) ?>">
                                        <span class="mf-quiz-diff__dot" aria-hidden="true"></span>
                                        <span class="mf-quiz-diff__text"><?= h($difficulty) ?></span>
                                    </span>
                                <?php endif; ?>
                                <?php if ($questionsCount !== null) : ?>
                                    <div class="mf-quiz-stat" title="<?= h(__('Questions')) ?>">
                                        <i class="bi bi-list-ol" aria-hidden="true"></i>
                                        <span class="mf-quiz-stat__value"><?= (int)$questionsCount ?></span>
                                        <span class="mf-quiz-stat__label"><?= __('questions') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mf-quiz-card__content">
                        <div class="mf-quiz-card__title"><?= h($title) ?></div>
                        <p class="mf-quiz-card__desc"><?= $description !== '' ? h($description) : __('No description yet.') ?></p>
                    </div>

                    <div class="mf-quiz-card__actions">
                        <?= $this->Form->postLink(
                            __('Remove from favorites'),
                            ['controller' => 'Tests', 'action' => 'unfavorite', (string)$testId, 'lang' => $lang],
                            ['class' => 'btn btn-outline-light'],
                        ) ?>
                        <?= $this->Form->postLink(
                            __('Start quiz'),
                            ['controller' => 'Tests', 'action' => 'start', (string)$testId, 'lang' => $lang],
                            ['class' => 'btn btn-primary mf-quiz-card__cta'],
                        ) ?>
                        <?= $this->Html->link(
                            __('Open details'),
                            ['controller' => 'Tests', 'action' => 'details', (string)$testId, 'lang' => $lang],
                            ['class' => 'btn btn-outline-light mf-quiz-card__secondary'],
                        ) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1) : ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="mf-muted small"><?= __('Showing {0} favorites', $total) ?></div>
                <div class="d-flex gap-2">
                    <?php if ($page > 1) : ?>
                        <?= $this->Html->link(
                            __('Previous'),
                            ['controller' => 'Tests', 'action' => 'favorites', 'lang' => $lang, '?' => ['page' => $page - 1]],
                            ['class' => 'btn btn-sm btn-outline-light'],
                        ) ?>
                    <?php endif; ?>
                    <?php if ($page < $totalPages) : ?>
                        <?= $this->Html->link(
                            __('Next'),
                            ['controller' => 'Tests', 'action' => 'favorites', 'lang' => $lang, '?' => ['page' => $page + 1]],
                            ['class' => 'btn btn-sm btn-outline-light'],
                        ) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
