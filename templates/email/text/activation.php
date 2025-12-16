<?php
/**
 * Activation email text template
 *
 * @var \Cake\View\View $this
 * @var string $activationUrl
 */
?>
<?= __('Welcome to MindForge!') ?>

<?= __('Thank you for registering. Please click the link below to activate your account:') ?>

<?= $activationUrl ?>

<?= __('This link will expire in 4 hours.') ?>

<?= __('If you did not create an account, please ignore this email.') ?>
