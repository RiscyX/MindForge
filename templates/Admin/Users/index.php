<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> $users
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Users'));
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Users') ?></h1>
    </div>
</div>

<br>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfUsersSearch',
        'label' => __('Search by username or email'),
        'placeholder' => __('Search…'),
        'maxWidth' => '420px',
    ],
    'limit' => [
        'id' => 'mfUsersLimit',
        'label' => __('Show'),
        'default' => '10',
        'options' => [
            '10' => '10',
            '50' => '50',
            '100' => '100',
            '-1' => __('All'),
        ],
    ],
    'create' => [
        'label' => __('Create User') . ' +',
        'url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<?php
// Row-level actions must not create nested <form> tags inside the bulk form.
// We render one hidden form to carry CSRF token and submit to different endpoints via `formaction`.
?>
<?= $this->Form->create(null, [
    'id' => 'mfUserRowActionForm',
    'url' => [
        'prefix' => 'Admin',
        'controller' => 'Users',
        'action' => 'index',
        'lang' => $lang,
    ],
    'style' => 'display:none;',
]) ?>
<?= $this->Form->end() ?>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => [
            'prefix' => 'Admin',
            'controller' => 'Users',
            'action' => 'bulk',
            'lang' => $lang,
        ],
        'id' => 'mfUsersBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfUsersTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Username') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Email') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Role') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Last login') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$user->id) ?>"
                                aria-label="<?= h(__('Select user')) ?>"
                            />
                        </td>
                        <td><?= $user->username ? h($user->username) : '–' ?></td>
                        <td><?= h($user->email) ?></td>
                        <td class="mf-muted"><?= h($user->role?->name ?? '') ?></td>
                        <td class="mf-muted" data-order="<?= $user->last_login_at ? h($user->last_login_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $user->last_login_at ? h($user->last_login_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $user->created_at ? h($user->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $user->created_at ? h($user->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td>
                            <div class="mf-admin-actions">
                                <?= $this->Html->link(
                                    '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>' . h(__('Edit')) . '</span>',
                                    [
                                        'prefix' => 'Admin',
                                        'controller' => 'Users',
                                        'action' => 'edit',
                                        $user->id,
                                        'lang' => $lang,
                                    ],
                                    ['class' => 'btn btn-sm mf-admin-action mf-admin-action--neutral', 'escape' => false],
                                ) ?>

                                <?php if ($user->is_blocked) : ?>
                                    <button class="btn btn-sm mf-admin-action mf-admin-action--danger" type="button" disabled aria-disabled="true">
                                        <i class="bi bi-person-x" aria-hidden="true"></i><span><?= __('Ban') ?></span>
                                    </button>
                                    <?= $this->Form->button(
                                        '<i class="bi bi-person-check" aria-hidden="true"></i><span>' . h(__('Unban')) . '</span>',
                                        [
                                            'type' => 'submit',
                                            'class' => 'btn btn-sm mf-admin-action mf-admin-action--success',
                                            'form' => 'mfUserRowActionForm',
                                            'formaction' => $this->Url->build([
                                                'prefix' => 'Admin',
                                                'controller' => 'Users',
                                                'action' => 'unban',
                                                $user->id,
                                                'lang' => $lang,
                                            ]),
                                            'onclick' => 'return confirm(' . json_encode((string)__('Are you sure you want to unban this user?')) . ');',
                                            'escapeTitle' => false,
                                        ],
                                    ) ?>
                                <?php else : ?>
                                    <?= $this->Form->button(
                                        '<i class="bi bi-person-x" aria-hidden="true"></i><span>' . h(__('Ban')) . '</span>',
                                        [
                                            'type' => 'submit',
                                            'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                                            'form' => 'mfUserRowActionForm',
                                            'formaction' => $this->Url->build([
                                                'prefix' => 'Admin',
                                                'controller' => 'Users',
                                                'action' => 'ban',
                                                $user->id,
                                                'lang' => $lang,
                                            ]),
                                            'onclick' => 'return confirm(' . json_encode((string)__('Are you sure you want to ban this user?')) . ');',
                                            'escapeTitle' => false,
                                        ],
                                    ) ?>
                                    <button class="btn btn-sm mf-admin-action mf-admin-action--success" type="button" disabled aria-disabled="true">
                                        <i class="bi bi-person-check" aria-hidden="true"></i><span><?= __('Unban') ?></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= $this->Form->end() ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-2">
    <?= $this->element('functions/admin_bulk_controls', [
        'containerClass' => 'd-flex align-items-center gap-3 flex-wrap',
        'selectAll' => [
            'checkboxId' => 'mfUsersSelectAll',
            'linkId' => 'mfUsersSelectAllLink',
            'text' => __('Select all'),
        ],
        'bulk' => [
            'label' => __('Action for selected items:'),
            'formId' => 'mfUsersBulkForm',
            'buttons' => [
                [
                    'label' => '<i class="bi bi-person-x" aria-hidden="true"></i><span>' . h(__('Ban')) . '</span>',
                    'value' => 'ban',
                    'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                    'escapeTitle' => false,
                ],
                [
                    'label' => '<i class="bi bi-person-check" aria-hidden="true"></i><span>' . h(__('Unban')) . '</span>',
                    'value' => 'unban',
                    'class' => 'btn btn-sm mf-admin-action mf-admin-action--success',
                    'escapeTitle' => false,
                ],
                [
                    'label' => '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
                    'value' => 'delete',
                    'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                    'escapeTitle' => false,
                    'attrs' => [
                        'data-mf-bulk-delete' => true,
                    ],
                ],
            ],
        ],
    ]) ?>

    <nav aria-label="<?= h(__('Pagination')) ?>">
        <div id="mfUsersPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfUsersTable',
        'searchInputId' => 'mfUsersSearch',
        'limitSelectId' => 'mfUsersLimit',
        'bulkFormId' => 'mfUsersBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfUsersSelectAll',
        'selectAllLinkId' => 'mfUsersSelectAllLink',
        'paginationContainerId' => 'mfUsersPagination',
        'pagination' => [
            'windowSize' => 3,
            'jumpSize' => 3,
        ],
        'strings' => [
            'selectAtLeastOne' => (string)__('Select at least one user.'),
            'confirmDelete' => (string)__('Are you sure you want to delete the selected users?'),
        ],
        'bulkDeleteValues' => ['delete'],
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[5, 'desc']],
            'nonOrderableTargets' => [0, -1],
            'nonSearchableTargets' => [4, 5],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 5,
            'defaultSortDir' => 'desc',
            'excludedSortCols' => [0, 6],
            'searchCols' => [1, 2, 3],
        ],
    ],
]) ?>
<?php $this->end(); ?>
