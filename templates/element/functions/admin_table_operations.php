<?php
/**
 * Reusable table JS operations: search, limit, sorting (vanilla), select-all (page/visible),
 * and optional DataTables integration when available.
 *
 * @var \App\View\AppView $this
 * @var array $config
 */

$config = $config ?? [];

$encodedConfig = json_encode(
    $config,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
);
if ($encodedConfig === false) {
    $encodedConfig = '{}';
}
?>
<script type="application/json" data-mf-admin-table-config><?= $encodedConfig ?></script>
<?= $this->Html->script('admin_table_operations') ?>
