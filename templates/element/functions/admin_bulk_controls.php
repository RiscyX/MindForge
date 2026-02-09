<?php
/**
 * Reusable bulk controls under a table.
 *
 * @var \App\View\AppView $this
 * @var array $selectAll
 * @var array $bulk
 */

$selectAll = $selectAll ?? [];
$bulk = $bulk ?? [];

$selectAllCheckboxId = $selectAll['checkboxId'] ?? 'mfSelectAll';
$selectAllLinkId = $selectAll['linkId'] ?? 'mfSelectAllLink';
$selectAllText = $selectAll['text'] ?? __('Összes bejelölése');

$bulkLabel = $bulk['label'] ?? __('A kijelöltekkel végzendő művelet:');
$bulkFormId = $bulk['formId'] ?? null;
$buttons = $bulk['buttons'] ?? [];

$containerClass = $containerClass ?? 'd-flex align-items-center gap-3 flex-wrap mt-2';
?>

<div class="mf-admin-bulkbar <?= h($containerClass) ?>">
    <input id="<?= h($selectAllCheckboxId) ?>" class="visually-hidden" type="checkbox" />

    <button
        id="<?= h($selectAllLinkId) ?>"
        type="button"
        class="btn btn-sm btn-outline-light"
    >
        <?= h('↑ ') ?><?= h($selectAllText) ?>
    </button>

    <span class="mf-muted" style="font-size:0.9rem;">
        <?= h($bulkLabel) ?>
    </span>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php foreach ($buttons as $button) : ?>
            <?php
                $attrs = $button['attrs'] ?? [];
                $attrs += [
                    'type' => 'submit',
                    'name' => $button['name'] ?? 'bulk_action',
                    'value' => $button['value'] ?? '',
                    'class' => $button['class'] ?? 'btn btn-sm btn-outline-light',
                ];
                if ($bulkFormId) {
                    $attrs['form'] = $bulkFormId;
                }
            ?>
            <?= $this->Form->button(
                (string)($button['label'] ?? ''),
                $attrs + ['escapeTitle' => true],
            ) ?>
        <?php endforeach; ?>
    </div>
</div>
