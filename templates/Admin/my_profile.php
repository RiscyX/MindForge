<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $lang
 * @var array<string, mixed>|null $aiStats
 */

$this->assign('title', __('My Profile'));

// Prepend app base path so the URL is correct on any subdirectory install (e.g. /MindForge/)
$_base     = (string)($this->request->getAttribute('base') ?? '');
$avatarSrc = $_base . ($user->avatar_url ?: '/img/avatars/stockpfp.jpg');
?>

<div class="mf-admin-form-center">
    <div class="mf-admin-card p-4 mt-4 w-100" style="max-width: 720px;">
        <?= $this->Form->create($user, ['type' => 'file', 'novalidate' => false]) ?>

        <div class="row g-4">
            <div class="col-12 col-md-6 d-flex flex-column">
                <div class="row g-3">
                    <div class="col-12">
                        <?= $this->Form->control('email', [
                            'label' => __('Email'),
                            'class' => 'form-control mf-admin-input',
                            'disabled' => true,
                        ]) ?>
                    </div>

                    <div class="col-12">
                        <?= $this->Form->control('username', [
                            'label' => __('Username'),
                            'placeholder' => __('Optional'),
                            'class' => 'form-control mf-admin-input',
                            'required' => false,
                        ]) ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="roleDisplay"><?= __('Role') ?></label>
                        <input
                            id="roleDisplay"
                            type="text"
                            class="form-control mf-admin-input"
                            value="<?= h($user->role->name ?? (string)$user->role_id) ?>"
                            disabled
                        />
                    </div>

                    <div class="col-12">
                        <div class="mf-muted" style="font-size: 0.95rem;">
                            <div>
                                <span class="fw-semibold text-white"><?= __('Last login') ?></span>
                                <span> - </span>
                                <span><?= h($user->last_login_at ? $user->last_login_at->i18nFormat('yyyy-MM-dd HH:mm') : '-') ?></span>
                            </div>
                            <div class="mt-1">
                                <span class="fw-semibold text-white"><?= __('Created') ?></span>
                                <span> - </span>
                                <span><?= h($user->created_at ? $user->created_at->i18nFormat('yyyy-MM-dd HH:mm') : '-') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <div class="d-grid gap-2">
                        <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary mf-admin-btn', 'data-loading-text' => __('Saving…')]) ?>

                        <?= $this->Form->postLink(
                            __('Send password reset email'),
                            [
                                'prefix' => 'Admin',
                                'controller' => 'Users',
                                'action' => 'requestPasswordReset',
                                'lang' => $lang,
                            ],
                            [
                                'class' => 'btn btn-outline-light mf-admin-btn',
                                'confirm' => __('Send a password reset link to your email address?'),
                            ],
                        ) ?>

                        <?= $this->Html->link(
                            __('Back to dashboard'),
                            ['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index', 'lang' => $lang],
                            ['class' => 'btn btn-outline-light mf-admin-btn'],
                        ) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 d-flex flex-column align-items-center">
                <div class="flex-grow-1 d-flex align-items-center justify-content-center w-100">
                    <div class="position-relative d-inline-block" id="mfAvatarPreviewWrap">
                        <img
                            id="mfAvatarPreview"
                            src="<?= h($avatarSrc) ?>"
                            alt="<?= h(__('Avatar')) ?>"
                            class="rounded-circle border"
                            style="width:160px;height:160px;object-fit:cover;"
                        />
                        <button
                            type="button"
                            id="mfAvatarChangeBtn"
                            class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle d-flex align-items-center justify-content-center"
                            style="width:36px;height:36px;padding:0;"
                            title="<?= h(__('Change avatar')) ?>"
                        >
                            <i class="bi bi-camera-fill" style="font-size:1rem;"></i>
                        </button>
                    </div>
                </div>

                <!-- Hidden real file input -->
                <input
                    type="file"
                    id="mfAvatarFileInput"
                    name="avatar_file_raw"
                    accept="image/*"
                    class="d-none"
                />
                <!-- Cropped base64 result sent to server -->
                <input type="hidden" name="avatar_cropped_data" id="mfAvatarCroppedData" />

                <div class="w-100 mt-3 text-center">
                    <button
                        type="button"
                        id="mfAvatarChangeBtnText"
                        class="btn btn-outline-light btn-sm"
                    ><?= __('Change avatar') ?></button>
                    <div id="mfAvatarFilename" class="mf-muted mt-1" style="font-size:0.82rem;"></div>
                </div>
            </div>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<!-- Avatar Crop Modal -->
<div class="modal fade" id="mfAvatarCropModal" tabindex="-1" aria-labelledby="mfAvatarCropModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="mfAvatarCropModalLabel">
                    <i class="bi bi-crop me-2"></i><?= __('Adjust your avatar') ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= h(__('Close')) ?>"></button>
            </div>
            <div class="modal-body p-3" style="background:#1a1a2e;">
                <div style="max-height:400px;overflow:hidden;">
                    <img id="mfCropperImage" src="" alt="" style="max-width:100%;display:block;" />
                </div>
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <button type="button" id="mfCropZoomIn" class="btn btn-sm btn-outline-light" title="<?= h(__('Zoom in')) ?>">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button type="button" id="mfCropZoomOut" class="btn btn-sm btn-outline-light" title="<?= h(__('Zoom out')) ?>">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <button type="button" id="mfCropRotateL" class="btn btn-sm btn-outline-light" title="<?= h(__('Rotate left')) ?>">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <button type="button" id="mfCropRotateR" class="btn btn-sm btn-outline-light" title="<?= h(__('Rotate right')) ?>">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button type="button" id="mfCropReset" class="btn btn-sm btn-outline-secondary" title="<?= h(__('Reset')) ?>">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
                <p class="text-center mf-muted mt-2 mb-0" style="font-size:0.82rem;">
                    <?= __('Drag to reposition · Scroll or pinch to zoom') ?>
                </p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                <button type="button" id="mfCropConfirmBtn" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= __('Apply') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($aiStats !== null) : ?>
<div class="mf-admin-form-center mt-4">
    <div class="mf-admin-card p-4 w-100" style="max-width: 720px;">
        <h2 class="h5 mb-3">
            <i class="bi bi-cpu me-2 text-primary" aria-hidden="true"></i>
            <?= __('AI Usage') ?>
        </h2>
        <div class="row g-3">
            <div class="col-6 col-sm-4">
                <div class="mf-admin-card p-3 h-100 text-center">
                    <div class="mf-muted" style="font-size:0.8rem;"><?= __('Total requests') ?></div>
                    <div class="fw-bold fs-5 text-white"><?= $this->Number->format((int)($aiStats['total'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="mf-admin-card p-3 h-100 text-center">
                    <div class="mf-muted" style="font-size:0.8rem;"><?= __('Successful') ?></div>
                    <div class="fw-bold fs-5 text-success"><?= $this->Number->format((int)($aiStats['success'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-6 col-sm-4">
                <div class="mf-admin-card p-3 h-100 text-center">
                    <div class="mf-muted" style="font-size:0.8rem;"><?= __('Failed') ?></div>
                    <div class="fw-bold fs-5 text-warning"><?= $this->Number->format((int)($aiStats['failed'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-6 col-sm-6">
                <div class="mf-admin-card p-3 h-100 text-center">
                    <div class="mf-muted" style="font-size:0.8rem;"><?= __('Total tokens used') ?></div>
                    <div class="fw-bold fs-5 text-white"><?= $this->Number->format((int)($aiStats['totalTokens'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-6 col-sm-6">
                <div class="mf-admin-card p-3 h-100 text-center">
                    <div class="mf-muted" style="font-size:0.8rem;"><?= __('Estimated cost (USD)') ?></div>
                    <div class="fw-bold fs-5 text-white">$<?= number_format((float)($aiStats['totalCostUsd'] ?? 0), 4) ?></div>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <?= $this->Html->link(
                '<i class="bi bi-arrow-right me-1" aria-hidden="true"></i>' . __('View my AI requests'),
                [
                    'prefix' => false,
                    'controller' => 'AiRequests',
                    'action' => 'index',
                    'lang' => $lang,
                    '?' => ['user_id' => $user->id],
                ],
                ['class' => 'btn btn-outline-primary btn-sm', 'escape' => false],
            ) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->start('script'); ?>
<!-- Cropper.js -->
<link rel="stylesheet" href="<?= $this->Url->build('/css/vendor/cropperjs/cropper.min.css') ?>">
<script src="<?= $this->Url->build('/js/vendor/cropperjs/cropper.min.js') ?>"></script>
<script>
(function () {
    'use strict';

    const fileInput     = document.getElementById('mfAvatarFileInput');
    const changeBtn     = document.getElementById('mfAvatarChangeBtn');
    const changeBtnText = document.getElementById('mfAvatarChangeBtnText');
    const cropperImg    = document.getElementById('mfCropperImage');
    const preview       = document.getElementById('mfAvatarPreview');
    const croppedInput  = document.getElementById('mfAvatarCroppedData');
    const filenameEl    = document.getElementById('mfAvatarFilename');
    const confirmBtn    = document.getElementById('mfCropConfirmBtn');
    const modalEl       = document.getElementById('mfAvatarCropModal');

    if (!fileInput || !modalEl) return;

    const bsModal = new bootstrap.Modal(modalEl);
    let cropper = null;

    function openFilePicker() { fileInput.value = ''; fileInput.click(); }
    changeBtn?.addEventListener('click', openFilePicker);
    changeBtnText?.addEventListener('click', openFilePicker);

    fileInput.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            cropperImg.src = e.target.result;

            // Destroy previous instance
            if (cropper) { cropper.destroy(); cropper = null; }

            bsModal.show();
        };
        reader.readAsDataURL(file);
    });

    // Init Cropper after modal is fully shown (avoids zero-size canvas)
    modalEl.addEventListener('shown.bs.modal', function () {
        cropper = new Cropper(cropperImg, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.85,
            restore: false,
            guides: false,
            center: false,
            highlight: false,
            cropBoxMovable: false,
            cropBoxResizable: false,
            toggleDragModeOnDblclick: false,
        });
    });

    // Destroy cleanly when modal closes without saving
    modalEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
    });

    // Toolbar buttons
    document.getElementById('mfCropZoomIn')?.addEventListener('click',  () => cropper?.zoom(0.1));
    document.getElementById('mfCropZoomOut')?.addEventListener('click', () => cropper?.zoom(-0.1));
    document.getElementById('mfCropRotateL')?.addEventListener('click', () => cropper?.rotate(-90));
    document.getElementById('mfCropRotateR')?.addEventListener('click', () => cropper?.rotate(90));
    document.getElementById('mfCropReset')?.addEventListener('click',   () => cropper?.reset());

    // Confirm crop
    confirmBtn?.addEventListener('click', function () {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
        if (!canvas) return;

        canvas.toBlob(function (blob) {
            const reader = new FileReader();
            reader.onload = function (e) {
                croppedInput.value = e.target.result;
                preview.src = e.target.result;
                if (fileInput.files && fileInput.files[0]) {
                    filenameEl.textContent = fileInput.files[0].name;
                }
                bsModal.hide();
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.92);
    });
}());
</script>
<?php $this->end(); ?>
