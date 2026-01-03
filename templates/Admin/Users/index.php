<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> $users
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Users'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => true]);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => true]);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => true]);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => true]);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Users') ?></h1>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 mt-4 flex-wrap">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <label class="visually-hidden" for="mfUsersEmailSearch"><?= __('Search by email') ?></label>
        <input id="mfUsersEmailSearch" type="search" class="form-control form-control-sm mf-admin-input"
               style="width:min(320px, 100%);" placeholder="<?= __('Type an email…') ?>">
    </div>

    <div class="d-flex align-items-center gap-2">
        <?= $this->Html->link(
            __('Create User') . ' +',
            [
                'prefix' => 'Admin',
                'controller' => 'Users',
                'action' => 'add',
                'lang' => $lang,
            ],
            ['class' => 'btn btn-sm btn-primary'],
        ) ?>
        <label class="mf-muted" for="mfUsersLimit" style="font-size:0.9rem;"><?= __('Show') ?></label>
        <select id="mfUsersLimit" class="form-select form-select-sm mf-admin-select" style="width:auto;">
            <option value="10" selected>10</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="-1"><?= __('All') ?></option>
        </select>
    </div>
</div>

<div class="mf-admin-table-card mt-3">
    <table id="mfUsersTable" class="table table-dark table-hover mb-0 align-middle">
        <thead>
            <tr>
                <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Email') ?></th>
                <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Role') ?></th>
                <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Active') ?></th>
                <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Blocked') ?></th>
                <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Last login') ?></th>
                <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Created') ?></th>
                <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?= h($user->email) ?></td>
                    <td class="mf-muted"><?= h($user->role?->name ?? '') ?></td>
                    <td><?= $user->is_active ? __('Yes') : __('No') ?></td>
                    <td><?= $user->is_blocked ? __('Yes') : __('No') ?></td>
                    <td class="mf-muted">
                        <?= $user->last_login_at ? h($user->last_login_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                    </td>
                    <td class="mf-muted">
                        <?= $user->created_at ? h($user->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
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
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'confirm' => __('Are you sure you want to delete this user?'),
                                ],
                            ) ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $this->start('script'); ?>
<script>
(() => {
    if (typeof window.jQuery === 'undefined') return;
    const $ = window.jQuery;

    const $table = $('#mfUsersTable');
    if ($table.length === 0 || typeof $table.DataTable !== 'function') return;

    const table = $table.DataTable({
        searching: false,
        lengthChange: false,
        pageLength: 10,
        scrollY: '420px',
        scrollCollapse: true,
        order: [[0, 'asc']],
    });

    const limitSelect = document.getElementById('mfUsersLimit');
    if (limitSelect) {
        limitSelect.addEventListener('change', () => {
            const len = parseInt(limitSelect.value, 10);
            table.page.len(Number.isFinite(len) ? len : 10).draw();
        });
    }

    const emailSearch = document.getElementById('mfUsersEmailSearch');
    if (emailSearch) {
        emailSearch.addEventListener('input', () => {
            table.column(0).search(emailSearch.value || '').draw();
        });
    }
})();
</script>
<?php $this->end(); ?>
