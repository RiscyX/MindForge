<?php
/**
 * Reusable list toolbar (search + limit + create action).
 *
 * @var \App\View\AppView $this
 * @var array $search
 * @var array $limit
 * @var array|null $create
 */

$search = $search ?? [];
$limit = $limit ?? [];
$create = $create ?? null;

$searchId = $search['id'] ?? 'mfListSearch';
$searchLabel = $search['label'] ?? __('Search');
$searchPlaceholder = $search['placeholder'] ?? __('Search…');
$searchMaxWidth = $search['maxWidth'] ?? '400px';

$limitId = $limit['id'] ?? 'mfListLimit';
$limitLabel = $limit['label'] ?? __('Show');
$limitOptions = $limit['options'] ?? [
    '10' => '10',
    '50' => '50',
    '100' => '100',
    '-1' => __('All'),
];
$limitDefault = (string)($limit['default'] ?? '10');

$createLabelRaw = (string)($create['label'] ?? __('Create'));
$createLabelClean = trim(preg_replace('/\s*\+\s*$/', '', $createLabelRaw) ?? $createLabelRaw);
$createLabelClean = $createLabelClean !== '' ? $createLabelClean : (string)__('Create');
?>

<div class="mf-admin-toolbar mt-4">
    <div class="mf-admin-toolbar__search">
        <label class="visually-hidden" for="<?= h($searchId) ?>"><?= h($searchLabel) ?></label>
        <input
            id="<?= h($searchId) ?>"
            name="mf_search"
            type="search"
            class="form-control form-control-sm mf-admin-input"
            placeholder="<?= h($searchPlaceholder) ?>"
            autocomplete="off"
            spellcheck="false"
            inputmode="search"
            aria-label="<?= h($searchLabel) ?>"
            style="--mf-admin-search-max: <?= h($searchMaxWidth) ?>;"
        >
    </div>

    <div class="mf-admin-toolbar__right">
        <div class="mf-admin-toolbar__limit">
            <label class="mf-muted" for="<?= h($limitId) ?>" style="font-size:0.9rem;">
                <?= h($limitLabel) ?>
            </label>
            <select
                id="<?= h($limitId) ?>"
                name="mf_limit"
                class="form-select form-select-sm mf-admin-select"
                aria-label="<?= h($limitLabel) ?>"
            >
            <?php foreach ($limitOptions as $value => $label) : ?>
                <option value="<?= h((string)$value) ?>" <?= ((string)$value === $limitDefault) ? 'selected' : '' ?>>
                    <?= h((string)$label) ?>
                </option>
            <?php endforeach; ?>
            </select>
        </div>

        <?php if ($create) : ?>
            <?= $this->Html->link(
                '<i class="bi bi-plus-lg" aria-hidden="true"></i><span>' . h($createLabelClean) . '</span>',
                $create['url'] ?? '#',
                [
                    'class' => ($create['class'] ?? 'btn btn-sm btn-primary') . ' mf-admin-toolbar__create',
                    'escape' => false,
                    'aria-label' => $createLabelClean,
                ],
            ) ?>
        <?php endif; ?>
    </div>
</div>
