<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> $users
 * @var array<string, string> $filters
 * @var int $limit
 * @var array<int, int> $limitOptions
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Users'));

$q = (string)($filters['q'] ?? '');
$pagination = (array)$this->Paginator->params();
$currentPage = (int)($pagination['page'] ?? 1);
$pageCount = (int)($pagination['pageCount'] ?? 1);
$recordCount = (int)($pagination['count'] ?? 0);
$queryParams = (array)$this->request->getQueryParams();
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Users') ?></h1>
    </div>
</div>
<div class="mf-admin-toolbar mt-4">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'mfUsersFilterForm', 'class' => 'd-flex align-items-center gap-2 flex-wrap flex-grow-1']) ?>
        <div class="mf-admin-toolbar__search">
            <label class="visually-hidden" for="mfUsersSearch"><?= __('Search by username or email') ?></label>
            <?= $this->Form->text('q', [
                'id' => 'mfUsersSearch',
                'value' => $q,
                'class' => 'form-control form-control-sm mf-admin-input',
                'placeholder' => __('Search username/email…'),
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'style' => '--mf-admin-search-max: 420px;',
            ]) ?>
        </div>

        <div class="mf-admin-toolbar__right">
            <div class="mf-admin-toolbar__limit">
                <label class="mf-muted" for="mfUsersLimit" style="font-size:0.9rem;"><?= __('Show') ?></label>
                <?= $this->Form->select('limit', array_combine(array_map('strval', $limitOptions), array_map('strval', $limitOptions)), [
                    'id' => 'mfUsersLimit',
                    'value' => (string)$limit,
                    'class' => 'form-select form-select-sm mf-admin-select',
                ]) ?>
            </div>
        </div>
    <?= $this->Form->end() ?>

    <?= $this->Html->link(
        '<i class="bi bi-plus-lg" aria-hidden="true"></i><span>' . h(__('Create User')) . '</span>',
        [
            'prefix' => 'Admin',
            'controller' => 'Users',
            'action' => 'add',
            'lang' => $lang,
        ],
        ['class' => 'btn btn-sm btn-primary mf-admin-toolbar__create', 'escape' => false],
    ) ?>
</div>

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
        'data-select-required' => (string)__('Select at least one user.'),
        'data-delete-confirm' => (string)__('Are you sure you want to delete the selected users?'),
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

    <?php if ($pageCount > 1) : ?>
        <?php
        $prevPage = max(1, $currentPage - 1);
        $nextPage = min($pageCount, $currentPage + 1);
        $startPage = max(1, $currentPage - 2);
        $endPage = min($pageCount, $currentPage + 2);
        ?>
        <nav aria-label="<?= h(__('Pagination')) ?>">
            <ul class="pagination pagination-sm mb-0 mf-admin-pagination">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <?= $this->Html->link(
                        __('Previous'),
                        ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => $prevPage])],
                        ['class' => 'page-link'],
                    ) ?>
                </li>
                <?php for ($p = $startPage; $p <= $endPage; $p++) : ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <?= $this->Html->link(
                            (string)$p,
                            ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => $p])],
                            ['class' => 'page-link'],
                        ) ?>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $pageCount ? 'disabled' : '' ?>">
                    <?= $this->Html->link(
                        __('Next'),
                        ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => $nextPage])],
                        ['class' => 'page-link'],
                    ) ?>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<div class="mf-muted small mt-2">
    <?= __('Total records: {0}', $recordCount) ?>
</div>

<?php $this->start('script'); ?>
<?= $this->Html->script('users_index') ?>
<?php $this->end(); ?>
