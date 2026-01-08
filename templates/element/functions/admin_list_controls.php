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
?>

<div class="d-flex align-items-center justify-content-between gap-3 mt-4 flex-wrap">
    <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
        <label class="visually-hidden" for="<?= h($searchId) ?>"><?= h($searchLabel) ?></label>
        <input
            id="<?= h($searchId) ?>"
            type="search"
            class="form-control form-control-sm mf-admin-input flex-grow-1"
            style="max-width:<?= h($searchMaxWidth) ?>;"
            placeholder="<?= h($searchPlaceholder) ?>"
        >
    </div>

    <div class="d-flex align-items-center gap-2">
        <label class="mf-muted" for="<?= h($limitId) ?>" style="font-size:0.9rem;">
            <?= h($limitLabel) ?>
        </label>
        <select id="<?= h($limitId) ?>" class="form-select form-select-sm mf-admin-select" style="width:auto;">
            <?php foreach ($limitOptions as $value => $label) : ?>
                <option value="<?= h((string)$value) ?>" <?= ((string)$value === $limitDefault) ? 'selected' : '' ?>>
                    <?= h((string)$label) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($create) : ?>
            <?= $this->Html->link(
                $create['label'] ?? __('Create') . ' +',
                $create['url'] ?? '#',
                ['class' => $create['class'] ?? 'btn btn-sm btn-primary'],
            ) ?>
        <?php endif; ?>
    </div>
</div>
