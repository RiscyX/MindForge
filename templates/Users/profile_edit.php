<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Edit Profile'));
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h2 class="fw-bold mb-4 text-center text-white"><?= __('Edit Profile') ?></h2>

            <?= $this->Form->create($user, ['type' => 'file', 'class' => 'needs-validation']) ?>
            
            <div class="mb-4 text-center">
                <div class="position-relative d-inline-block group-avatar-upload">
                    <label for="avatar-file" class="cursor-pointer" title="<?= __('Change Avatar') ?>" style="cursor: pointer;">
                        <?php if ($user->avatar_url) : ?>
                            <?= $this->Html->image($user->avatar_url, [
                                'alt' => $user->email,
                                'class' => 'rounded-circle img-thumbnail shadow-sm avatar-preview',
                                'style' => 'width: 150px; height: 150px; object-fit: cover;'
                            ]) ?>
                        <?php else : ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto shadow-sm avatar-preview-placeholder" style="width: 150px; height: 150px;">
                                <span class="text-white display-4 text-uppercase"><?= substr($user->email, 0, 1) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="position-absolute top-0 start-0 w-100 h-100 rounded-circle d-flex align-items-center justify-content-center bg-dark bg-opacity-50 opacity-0 hover-opacity-100 transition-opacity text-white fw-bold">
                            <?= __('Change') ?>
                        </div>
                    </label>
                    <?= $this->Form->control('avatar_file', [
                        'type' => 'file',
                        'label' => false,
                        'id' => 'avatar-file',
                        'class' => 'd-none',
                        'accept' => 'image/png, image/jpeg, image/gif, image/webp'
                    ]) ?>
                </div>
                <div class="form-text mt-2 text-white"><?= __('Click the image to upload a new avatar.') ?></div>
            </div>

            <div class="mb-3">
                <?= $this->Form->control('username', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label fw-semibold text-white'],
                    'placeholder' => __('Choose a username')
                ]) ?>
            </div>

            <div class="mb-3">
                <?= $this->Form->control('email', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label fw-semibold text-white'],
                    'disabled' => true
                ]) ?>
                <div class="form-text text-white"><?= __('Email cannot be changed.') ?></div>
            </div>

            <!-- Add other fields here if needed, e.g. name if it exists -->

            <div class="d-grid gap-2 mt-4">
                <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary btn-lg rounded-pill']) ?>
                <?= $this->Html->link(__('Cancel'), ['action' => 'profile', 'lang' => $lang], ['class' => 'btn btn-outline-light rounded-pill']) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<style>
    .hover-opacity-100:hover {
        opacity: 1 !important;
    }
    .transition-opacity {
        transition: opacity 0.2s ease-in-out;
    }
</style>

<script>
document.getElementById('avatar-file').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.avatar-preview');
            const placeholder = document.querySelector('.avatar-preview-placeholder');
            
            if (preview) {
                preview.src = e.target.result;
            } else if (placeholder) {
                // If there was no image before, we need to replace the placeholder with an img tag
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Avatar Preview';
                img.className = 'rounded-circle img-thumbnail shadow-sm avatar-preview';
                img.style = 'width: 150px; height: 150px; object-fit: cover;';
                placeholder.parentNode.replaceChild(img, placeholder);
            }
        }
        reader.readAsDataURL(file);
    }
});
</script>
