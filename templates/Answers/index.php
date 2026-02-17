<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Answer> $answers
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Answers'));
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
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang],
        'id' => 'mfAnswersBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfAnswersTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Question') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Content') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Correct') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Updated') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($answers as $answer) : ?>
                    <tr>
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$answer->id) ?>"
                                aria-label="<?= h(__('Select answer')) ?>"
                            />
                        </td>
                        <td class="mf-muted" data-order="<?= h((string)$answer->id) ?>"><?= $this->Number->format($answer->id) ?></td>
                        <td class="mf-muted" style="text-align:left;">
                            <?php
                            $qContent = null;
                            if ($answer->hasValue('question') && !empty($answer->question->question_translations)) {
                                $qContent = $answer->question->question_translations[0]->content ?? null;
                            }
                            ?>
                            <?= $qContent ? h((string)$qContent) : ($answer->hasValue('question') ? ('#' . h((string)$answer->question->id)) : '—') ?>
                        </td>
                        <td class="mf-muted" style="text-align:left;">
                            <?php
                            $aContent = null;
                            if (!empty($answer->answer_translations)) {
                                $aContent = $answer->answer_translations[0]->content ?? null;
                            }
                            ?>
                            <?= $aContent ? h((string)$aContent) : __('N/A') ?>
                        </td>
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

    <?= $this->Form->end() ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-2">
    <?= $this->element('functions/admin_bulk_controls', [
        'containerClass' => 'd-flex align-items-center gap-3 flex-wrap',
        'selectAll' => [
            'checkboxId' => 'mfAnswersSelectAll',
            'linkId' => 'mfAnswersSelectAllLink',
            'text' => __('Select all'),
        ],
        'bulk' => [
            'label' => __('Action for selected items:'),
            'formId' => 'mfAnswersBulkForm',
            'buttons' => [
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
        <div id="mfAnswersPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfAnswersTable',
        'searchInputId' => 'mfAnswersSearch',
        'limitSelectId' => 'mfAnswersLimit',
        'bulkFormId' => 'mfAnswersBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfAnswersSelectAll',
        'selectAllLinkId' => 'mfAnswersSelectAllLink',
        'paginationContainerId' => 'mfAnswersPagination',
        'pagination' => [
            'windowSize' => 3,
            'jumpSize' => 3,
        ],
        'strings' => [
            'selectAtLeastOne' => (string)__('Select at least one item.'),
            'confirmDelete' => (string)__('Are you sure you want to delete the selected items?'),
        ],
        'bulkDeleteValues' => ['delete'],
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[1, 'asc']],
            'nonOrderableTargets' => [0, -1],
            'nonSearchableTargets' => [4, 5, 6],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [0, 7],
            'searchCols' => [1, 2, 3],
        ],
    ],
]) ?>
<?php $this->end(); ?>
