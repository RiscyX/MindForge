<?php
/**
 * @var \App\View\AppView $this
 * @var string $resetUrl
 */
?>
<p><?= __('You requested a password reset for your MindForge account.') ?></p>
<p><?= __('Click the link below to set a new password:') ?></p>
<p><a href="<?= h($resetUrl) ?>"><?= h($resetUrl) ?></a></p>
<p><?= __('If you did not request this, you can safely ignore this email.') ?></p>
