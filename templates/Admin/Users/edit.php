<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var \Cake\Collection\CollectionInterface<string> $roles
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt> $testAttempts
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\ActivityLog> $activityLogs
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\DeviceLog> $deviceLogs
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Edit User'));
?>

<?php
$deviceTypeLabels = [
    0 => __('Mobile'),
    1 => __('Tablet'),
    2 => __('Desktop'),
];
?>

<div class="row g-4 align-items-start mt-2">

<div class="col-12 col-lg-6">
    <div class="mf-admin-card p-4">
        <?= $this->Form->create($user, ['type' => 'file', 'novalidate' => false]) ?>

        <?php
            $avatarSrc = $user->avatar_url ?: '/img/avatars/stockpfp.jpg';
        ?>

        <div class="row g-4">
            <div class="col-12 col-md-6 d-flex flex-column">
                <div class="row g-3">
                    <div class="col-12">
                        <?= $this->Form->control('email', [
                            'label' => __('Email'),
                            'class' => 'form-control mf-admin-input',
                            'required' => true,
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
                        <?= $this->Form->control('password', [
                            'label' => __('New password'),
                            'placeholder' => __('Leave blank to keep current'),
                            'type' => 'password',
                            'class' => 'form-control mf-admin-input',
                            'required' => false,
                            'minlength' => 8,
                            'value' => '',
                        ]) ?>
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <?= $this->Form->control('role_id', [
                        'label' => __('Role'),
                        'options' => $roles,
                        'class' => 'form-select mf-admin-select',
                        'required' => true,
                    ]) ?>
                </div>
            </div>

            <div class="col-12 col-md-6 d-flex flex-column align-items-center">
                <div class="flex-grow-1 d-flex align-items-center justify-content-center w-100">
                    <?= $this->Html->image(
                        $avatarSrc,
                        [
                            'alt' => __('Avatar'),
                            'class' => 'rounded-circle border',
                            'style' => 'width:160px;height:160px;object-fit:cover;',
                        ],
                    ) ?>
                </div>

                <div class="w-100 mt-auto pt-3">
                    <?= $this->Form->control('avatar_file', [
                        'label' => __('Avatar'),
                        'type' => 'file',
                        'accept' => 'image/*',
                        'class' => 'form-control mf-admin-input',
                        'required' => false,
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
            <div class="d-flex gap-2">
                <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary mf-admin-btn', 'data-loading-text' => __('Saving…')]) ?>
                <?= $this->Html->link(
                    __('Cancel'),
                    [
                        'prefix' => 'Admin',
                        'controller' => 'Users',
                        'action' => 'index',
                        'lang' => $lang,
                    ],
                    ['class' => 'btn btn-outline-light mf-admin-btn'],
                ) ?>
            </div>

            <div class="d-flex gap-2">
                <?php if ($user->is_blocked) : ?>
                    <button class="btn btn-danger mf-admin-btn" type="button" disabled aria-disabled="true">
                        <?= __('Ban') ?>
                    </button>
                    <?= $this->Form->postLink(
                        __('Unban'),
                        [
                            'prefix' => 'Admin',
                            'controller' => 'Users',
                            'action' => 'unban',
                            $user->id,
                            'lang' => $lang,
                        ],
                        ['class' => 'btn btn-success mf-admin-btn'],
                    ) ?>
                <?php else : ?>
                    <?= $this->Form->postLink(
                        __('Ban'),
                        [
                            'prefix' => 'Admin',
                            'controller' => 'Users',
                            'action' => 'ban',
                            $user->id,
                            'lang' => $lang,
                        ],
                        [
                            'class' => 'btn btn-danger mf-admin-btn',
                            'confirm' => __('Are you sure you want to ban this user?'),
                        ],
                    ) ?>
                    <button class="btn btn-success mf-admin-btn" type="button" disabled aria-disabled="true">
                        <?= __('Unban') ?>
                    </button>
                <?php endif; ?>

                <?= $this->Form->postLink(
                    __('Delete'),
                    [
                        'prefix' => 'Admin',
                        'controller' => 'Users',
                        'action' => 'delete',
                        $user->id,
                        'lang' => $lang,
                    ],
                    [
                        'class' => 'btn btn-outline-danger mf-admin-btn',
                        'confirm' => __('Are you sure you want to delete this user?'),
                    ],
                ) ?>
            </div>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<div class="col-12 col-lg-6">
<div class="d-flex flex-column gap-4" style="position:sticky;top:1rem;max-height:calc(100vh - 2rem);overflow-y:auto;padding-right:2px;">

        <div>
            <div class="mf-admin-card p-0 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between px-3 pt-3 pb-2 border-bottom border-dark-subtle">
                    <span class="fw-semibold"><?= __('Last 5 Test Attempts') ?></span>
                </div>
                <div>
                    <table class="table table-dark table-hover table-sm mb-0 align-middle text-center">
                        <thead>
                            <tr>
                                <th class="mf-muted fs-6"><?= __('Test') ?></th>
                                <th class="mf-muted fs-6"><?= __('Score') ?></th>
                                <th class="mf-muted fs-6"><?= __('Date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testAttempts as $attempt) : ?>
                                <tr>
                                    <td class="text-start">
                                        <?php if ($attempt->test !== null) : ?>
                                            <?php
                                                $testTitle = !empty($attempt->test->test_translations)
                                                    ? (string)$attempt->test->test_translations[0]->title
                                                    : 'Test #' . (string)$attempt->test_id;
                                            ?>
                                            <?= h($testTitle) ?>
                                        <?php else : ?>
                                            <span class="mf-muted"><?= h('Test #' . (string)$attempt->test_id) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $score = $attempt->score ?? null;
                                            $badge = 'bg-secondary';
                                            if ($score !== null) {
                                                $badge = $score >= 80 ? 'bg-success' : ($score >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                                            }
                                        ?>
                                        <?php if ($score !== null) : ?>
                                            <span class="badge <?= $badge ?>"><?= $this->Number->format($score) ?>%</span>
                                        <?php else : ?>
                                            <span class="mf-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="mf-muted" style="white-space:nowrap;">
                                        <?= $attempt->created_at ? h($attempt->created_at->i18nFormat('yyyy-MM-dd')) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($testAttempts) === 0) : ?>
                                <?= $this->element('functions/admin_empty_state', ['message' => __('No test attempts yet.')]) ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="mf-admin-card p-0 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between px-3 pt-3 pb-2 border-bottom border-dark-subtle">
                    <span class="fw-semibold"><?= __('Last 5 Activity Logs') ?></span>
                </div>
                <div>
                    <table class="table table-dark table-hover table-sm mb-0 align-middle text-center">
                        <thead>
                            <tr>
                                <th class="mf-muted fs-6"><?= __('Action') ?></th>
                                <th class="mf-muted fs-6"><?= __('IP') ?></th>
                                <th class="mf-muted fs-6"><?= __('Date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activityLogs as $log) : ?>
                                <tr>
                                    <td class="text-start">
                                        <span class="mf-admin-pill"><?= h((string)($log->action ?? '—')) ?></span>
                                    </td>
                                    <td class="mf-muted" style="white-space:nowrap;"><?= h((string)($log->ip_address ?? '—')) ?></td>
                                    <td class="mf-muted" style="white-space:nowrap;">
                                        <?= $log->created_at ? h($log->created_at->i18nFormat('yyyy-MM-dd')) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($activityLogs) === 0) : ?>
                                <?= $this->element('functions/admin_empty_state', ['message' => __('No activity logs yet.')]) ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="mf-admin-card p-0 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between px-3 pt-3 pb-2 border-bottom border-dark-subtle">
                    <span class="fw-semibold"><?= __('Last 5 Device Logs') ?></span>
                    <?= $this->Html->link(
                        __('View all →'),
                        ['prefix' => 'Admin', 'controller' => 'DeviceLogs', 'action' => 'index', 'lang' => $lang, '?' => ['user_id' => $user->id]],
                        ['class' => 'btn btn-sm btn-outline-light ms-2'],
                    ) ?>
                </div>
                <div>
                    <table class="table table-dark table-hover table-sm mb-0 align-middle text-center">
                        <thead>
                            <tr>
                                <th class="mf-muted fs-6"><?= __('IP') ?></th>
                                <th class="mf-muted fs-6"><?= __('Device') ?></th>
                                <th class="mf-muted fs-6"><?= __('Date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deviceLogs as $dl) : ?>
                                <tr>
                                    <td class="mf-muted text-start" style="white-space:nowrap;"><?= h((string)($dl->ip_address ?? '—')) ?></td>
                                    <td>
                                        <span class="mf-admin-pill"><?= h($deviceTypeLabels[(int)($dl->device_type ?? 0)] ?? __('Unknown')) ?></span>
                                    </td>
                                    <td class="mf-muted" style="white-space:nowrap;">
                                        <?= $dl->created_at ? h($dl->created_at->i18nFormat('yyyy-MM-dd')) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($deviceLogs) === 0) : ?>
                                <?= $this->element('functions/admin_empty_state', ['message' => __('No device logs yet.')]) ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

</div>

</div>

</div>
