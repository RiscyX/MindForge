<?php
/**
 * Reusable empty-state row for admin tables.
 * Renders a full-width <tr><td> with icon, message, and optional CTA.
 *
 * @var \App\View\AppView $this
 * @var string|null      $message  Override the default "No records found." text.
 * @var string|null      $ctaLabel Label for the optional CTA button (requires $ctaUrl).
 * @var array|string|null $ctaUrl  CakePHP URL array or string for the CTA link.
 */

$message  = $message  ?? (string)__('No records found.');
$ctaLabel = $ctaLabel ?? null;
$ctaUrl   = $ctaUrl   ?? null;
?>
<tr class="mf-admin-empty-state-row">
    <td colspan="100" class="mf-admin-empty-state">
        <div class="mf-admin-empty-state-inner">
            <i class="bi bi-inbox mf-admin-empty-state-icon" aria-hidden="true"></i>
            <p class="mf-admin-empty-state-msg"><?= h($message) ?></p>
            <?php if ($ctaUrl !== null && $ctaLabel !== null) : ?>
                <?= $this->Html->link(
                    h($ctaLabel) . ' <i class="bi bi-plus-lg" aria-hidden="true"></i>',
                    $ctaUrl,
                    ['class' => 'btn btn-sm btn-primary mt-1', 'escape' => false],
                ) ?>
            <?php endif; ?>
        </div>
    </td>
</tr>
