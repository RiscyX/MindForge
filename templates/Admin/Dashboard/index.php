<?php
$this->assign('title', __('Admin Dashboard'));
?>

<div class="container-fluid px-0 mf-admin-shell">
    <div class="d-flex mf-admin-layout">
        <aside class="mf-admin-sidebar d-none d-lg-flex flex-column">
            <div class="mf-admin-sidebar__section">
                <div class="mf-admin-sidebar__label"><?= __('Management') ?></div>
                <nav class="mf-admin-nav">
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Users') ?></a>
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Categories') ?></a>
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Tests') ?></a>
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Questions') ?></a>
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Answers') ?></a>
                </nav>
            </div>

            <div class="mf-admin-sidebar__section mt-2">
                <div class="mf-admin-sidebar__label"><?= __('System') ?></div>
                <nav class="mf-admin-nav">
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Logs') ?></a>
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Device Logs') ?></a>
                    <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('AI Requests') ?></a>
                </nav>
            </div>

            <div class="mt-auto mf-admin-sidebar__footer">
                <?= $this->Form->postLink(
                    __('Logout'),
                    ['controller' => 'Users', 'action' => 'logout', 'lang' => $this->request->getParam('lang', 'en')],
                    ['class' => 'mf-admin-nav__link']
                ) ?>
            </div>
        </aside>

        <section class="flex-grow-1 mf-admin-main">
            <div class="container-fluid px-3 px-lg-4 py-4">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <h1 class="h3 mb-1"><?= __('Dashboard Overview') ?></h1>
                        <div class="mf-muted"><?= __('Welcome back, Administrator. Here\'s what\'s happening today.') ?></div>
                    </div>
                    <div class="mf-muted" style="font-size:0.9rem;">
                        <span class="mf-admin-pill"><?= __('Last updated: Just now') ?></span>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <?php
                    /** @var array{totalUsers:int,activeUsers:int,totalTests:int,totalQuestions:int,todaysLogins:int,aiRequests:int} $metrics */
                    $metrics = $stats ?? [
                        'totalUsers' => 0,
                        'activeUsers' => 0,
                        'totalTests' => 0,
                        'totalQuestions' => 0,
                        'todaysLogins' => 0,
                        'aiRequests' => 0,
                    ];

                    $statCards = [
                        ['label' => __('Total Users'), 'value' => number_format((int)$metrics['totalUsers']), 'delta' => ''],
                        ['label' => __('Active Users'), 'value' => number_format((int)$metrics['activeUsers']), 'delta' => ''],
                        ['label' => __('Total Tests'), 'value' => number_format((int)$metrics['totalTests']), 'delta' => ''],
                        ['label' => __('Questions'), 'value' => number_format((int)$metrics['totalQuestions']), 'delta' => ''],
                        ['label' => __('Today\'s Logins'), 'value' => number_format((int)$metrics['todaysLogins']), 'delta' => ''],
                        ['label' => __('AI Requests'), 'value' => number_format((int)$metrics['aiRequests']), 'delta' => ''],
                    ];
                    ?>
                    <?php foreach ($statCards as $stat) : ?>
                        <div class="col-6 col-xl-2">
                            <div class="mf-admin-card p-3 h-100">
                                <div class="mf-muted" style="font-size:0.85rem;"><?= h($stat['label']) ?></div>
                                <div class="fw-semibold" style="font-size:1.35rem; line-height:1.15;"><?= h($stat['value']) ?></div>
                                <?php if ($stat['delta'] !== '') : ?>
                                    <div class="mf-admin-delta"><?= h($stat['delta']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-3 mt-4 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h5 mb-0"><?= __('Recent System Events') ?></h2>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <label class="mf-muted" for="mfEventsLimit" style="font-size:0.9rem;"><?= __('Show') ?></label>
                        <select id="mfEventsLimit" class="form-select form-select-sm mf-admin-select" style="width:auto;">
                            <option selected>10</option>
                            <option>50</option>
                            <option>100</option>
                            <option><?= __('All') ?></option>
                        </select>
                    </div>
                </div>

                <div class="mf-admin-table-card mt-3">
                    <div class="mf-admin-table-scroll">
                        <table class="table table-dark table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Timestamp') ?></th>
                                    <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Event Type') ?></th>
                                    <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('User') ?></th>
                                    <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Details') ?></th>
                                    <th scope="col" class="mf-muted" style="font-size:0.8rem;"><?= __('Status') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $badge = static function (string $status): string {
                                    return match ($status) {
                                        'Success' => 'bg-success',
                                        'Failed' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                };

                                /** @var list<array{ts:string,type:string,user:string,details:string,status:string}> $rows */
                                $rows = $recentEvents ?? [];
                                ?>

                                <?php if (!$rows) : ?>
                                    <tr>
                                        <td colspan="5" class="mf-muted py-4">
                                            <?= __('No events yet.') ?>
                                        </td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($rows as $row) : ?>
                                        <tr>
                                            <td class="mf-muted" style="font-size:0.9rem;"><span class="text-nowrap"><?= h($row['ts']) ?></span></td>
                                            <td><?= h($row['type']) ?></td>
                                            <td class="mf-muted"><?= h($row['user']) ?></td>
                                            <td class="mf-muted" style="max-width: 420px;">
                                                <span style="display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                    <?= h($row['details']) ?>
                                                </span>
                                            </td>
                                            <td><span class="badge <?= h($badge($row['status'])) ?>"><?= h($row['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
