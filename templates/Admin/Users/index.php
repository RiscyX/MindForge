<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> $users
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Users'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
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
        'placeholder' => __('Search username/email…'),
        'maxWidth' => '400px',
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
        'url' => [
            'prefix' => 'Admin',
            'controller' => 'Users',
            'action' => 'add',
            'lang' => $lang,
        ],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

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
                                    class="form-check-input mf-user-select"
                                    type="checkbox"
                                    name="ids[]"
                                    value="<?= h((string)$user->id) ?>"
                                    aria-label="<?= h(__('Select user')) ?>"
                                />
                            </td>
                            <td><?= $user->username ? h($user->username) : '-' ?></td>
                            <td><?= h($user->email) ?></td>
                            <td class="mf-muted"><?= h($user->role?->name ?? '') ?></td>
                            <td class="mf-muted" data-order="<?= $user->last_login_at ? h($user->last_login_at->format('Y-m-d H:i:s')) : '0' ?>">
                                <?= $user->last_login_at ? h($user->last_login_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                            </td>
                            <td class="mf-muted" data-order="<?= $user->created_at ? h($user->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                                <?= $user->created_at ? h($user->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    <?= $this->Html->link(
                                        __('Edit'),
                                        [
                                            'prefix' => 'Admin',
                                            'controller' => 'Users',
                                            'action' => 'edit',
                                            $user->id,
                                            'lang' => $lang,
                                        ],
                                        ['class' => 'btn btn-sm btn-outline-light'],
                                    ) ?>

                                    <?php if ($user->is_blocked) : ?>
                                        <button class="btn btn-sm btn-danger" type="button" disabled aria-disabled="true">
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
                                            ['class' => 'btn btn-sm btn-success'],
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
                                                'class' => 'btn btn-sm btn-danger',
                                                'confirm' => __('Are you sure you want to ban this user?'),
                                            ],
                                        ) ?>
                                        <button class="btn btn-sm btn-success" type="button" disabled aria-disabled="true">
                                            <?= __('Unban') ?>
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
            'text' => __('Összes bejelölése'),
        ],
        'bulk' => [
            'label' => __('A kijelöltekkel végzendő művelet:'),
            'formId' => 'mfUsersBulkForm',
            'buttons' => [
                [
                    'label' => __('Ban'),
                    'value' => 'ban',
                    'class' => 'btn btn-sm btn-danger',
                ],
                [
                    'label' => __('Unban'),
                    'value' => 'unban',
                    'class' => 'btn btn-sm btn-success',
                ],
                [
                    'label' => __('Delete'),
                    'value' => 'delete',
                    'class' => 'btn btn-sm btn-outline-danger',
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
        'rowCheckboxSelector' => '.mf-user-select',
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
            'order' => [[1, 'asc']],
            'nonOrderableTargets' => [0, -1],
            'nonSearchableTargets' => [0, 3, 4, 5],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [0, 6],
            'searchCols' => [1, 2],
        ],
    ],
]) ?>
<?php $this->end(); ?>
