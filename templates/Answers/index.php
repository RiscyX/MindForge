<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Answer> $answers
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Answers'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Answers') ?></h1>
    </div>
</div>

<br>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfAnswersSearch',
        'label' => __('Search'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfAnswersLimit',
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
        'label' => __('New Answer') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <div class="mf-admin-table-scroll">
        <table id="mfAnswersTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Question') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Source') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Correct') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Updated') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($answers as $answer) : ?>
                    <tr>
                        <td class="mf-muted" data-order="<?= h((string)$answer->id) ?>"><?= $this->Number->format($answer->id) ?></td>
                        <td><?= $answer->hasValue('question') ? h((string)$answer->question->question_type) : '—' ?></td>
                        <td class="mf-muted"><?= h((string)$answer->source_type) ?></td>
                        <td>
                            <?php if ($answer->is_correct) : ?>
                                <span class="badge bg-success"><?= __('Yes') ?></span>
                            <?php else : ?>
                                <span class="badge bg-secondary"><?= __('No') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $answer->created_at ? h($answer->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $answer->created_at ? h($answer->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $answer->updated_at ? h($answer->updated_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $answer->updated_at ? h($answer->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['action' => 'view', $answer->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $answer->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $answer->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $answer->id),
                                        'class' => 'btn btn-sm btn-outline-danger',
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfAnswersTable',
        'searchInputId' => 'mfAnswersSearch',
        'limitSelectId' => 'mfAnswersLimit',
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[0, 'asc']],
            'nonOrderableTargets' => [-1],
            'nonSearchableTargets' => [3, 4, 5],
            'dom' => 'rt<"d-flex align-items-center justify-content-between mt-2"ip>',
        ],
        'vanilla' => [
            'defaultSortCol' => 0,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [6],
            'searchCols' => [0, 1, 2],
        ],
    ],
]) ?>
<?php $this->end(); ?>