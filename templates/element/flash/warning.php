<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<?php
$flashClass = 'mf-flash';
if (!empty($params['class'])) {
    $flashClass .= ' ' . $params['class'];
}
?>

<div class="<?= h($flashClass) ?>" data-mf-flash role="alert" onclick="this.remove();">
    <div class="mf-flash__message"><?= $message ?></div>
</div>
