<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Answer $answer
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Answer'));

$questionLabel = $answer->hasValue('question') ? (string)$answer->question->question_type : '';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-white"><?= __('Answer') ?> #<?= h((string)$answer->id) ?></h1>
            <div class="mf-muted">
                <?= $questionLabel !== '' ? h($questionLabel) : __('Question') ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $answer->id, 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $answer->id, 'lang' => $lang],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $answer->id),
                    'class' => 'btn btn-outline-danger',
                ],
            ) ?>
            <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="mf-admin-card p-3">
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="mf-muted mb-1"><?= __('Question') ?></div>
                <div class="text-white">
                    <?php if ($answer->hasValue('question')) : ?>
                        <?= $this->Html->link(
                            h((string)$answer->question->question_type),
                            ['controller' => 'Questions', 'action' => 'view', $answer->question->id, 'lang' => $lang],
                            ['class' => 'link-light'],
                        ) ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Source Type') ?></div>
                <div class="text-white"><?= h((string)$answer->source_type) ?></div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Is Correct') ?></div>
                <div>
                    <?php if ($answer->is_correct) : ?>
                        <span class="badge bg-success"><?= __('Yes') ?></span>
                    <?php else : ?>
                        <span class="badge bg-secondary"><?= __('No') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Position') ?></div>
                <div class="text-white"><?= $answer->position === null ? '—' : h((string)$answer->position) ?></div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Created') ?></div>
                <div class="text-white"><?= $answer->created_at ? h($answer->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Updated') ?></div>
                <div class="text-white"><?= $answer->updated_at ? h($answer->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>

            <div class="col-12">
                <div class="mf-muted mb-1"><?= __('Source Text') ?></div>
                <div class="text-white" style="white-space:pre-wrap;"><?= h((string)$answer->source_text) ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($answer->answer_translations)) : ?>
        <div class="mf-admin-card p-3 mt-3">
            <h2 class="h5 mb-3 text-white"><?= __('Answer Translations') ?></h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th><?= __('ID') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Content') ?></th>
                            <th><?= __('Source') ?></th>
                            <th><?= __('Created') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($answer->answer_translations as $t) : ?>
                            <tr>
                                <td class="mf-muted"><?= h((string)$t->id) ?></td>
                                <td class="mf-muted"><?= h((string)$t->language_id) ?></td>
                                <td class="text-white"><?= h((string)$t->content) ?></td>
                                <td class="mf-muted"><?= h((string)$t->source_type) ?></td>
                                <td class="mf-muted"><?= $t->created_at ? h($t->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
