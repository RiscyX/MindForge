<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Answer $answer
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Answer'), ['action' => 'edit', $answer->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Answer'), ['action' => 'delete', $answer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $answer->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Answers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Answer'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="answers view content">
            <h3><?= h($answer->source_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Question') ?></th>
                    <td><?= $answer->hasValue('question') ? $this->Html->link($answer->question->question_type, ['controller' => 'Questions', 'action' => 'view', $answer->question->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Source Type') ?></th>
                    <td><?= h($answer->source_type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($answer->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Position') ?></th>
                    <td><?= $answer->position === null ? '' : $this->Number->format($answer->position) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($answer->created_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Updated At') ?></th>
                    <td><?= h($answer->updated_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Correct') ?></th>
                    <td><?= $answer->is_correct ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Source Text') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($answer->source_text)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Answer Translations') ?></h4>
                <?php if (!empty($answer->answer_translations)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Answer Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Content') ?></th>
                            <th><?= __('Source Type') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($answer->answer_translations as $answerTranslation) : ?>
                        <tr>
                            <td><?= h($answerTranslation->id) ?></td>
                            <td><?= h($answerTranslation->answer_id) ?></td>
                            <td><?= h($answerTranslation->language_id) ?></td>
                            <td><?= h($answerTranslation->content) ?></td>
                            <td><?= h($answerTranslation->source_type) ?></td>
                            <td><?= h($answerTranslation->created_by) ?></td>
                            <td><?= h($answerTranslation->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'AnswerTranslations', 'action' => 'view', $answerTranslation->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'AnswerTranslations', 'action' => 'edit', $answerTranslation->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'AnswerTranslations', 'action' => 'delete', $answerTranslation->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $answerTranslation->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Test Attempt Answers') ?></h4>
                <?php if (!empty($answer->test_attempt_answers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Test Attempt Id') ?></th>
                            <th><?= __('Question Id') ?></th>
                            <th><?= __('Answer Id') ?></th>
                            <th><?= __('User Answer Text') ?></th>
                            <th><?= __('Is Correct') ?></th>
                            <th><?= __('Answered At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($answer->test_attempt_answers as $testAttemptAnswer) : ?>
                        <tr>
                            <td><?= h($testAttemptAnswer->id) ?></td>
                            <td><?= h($testAttemptAnswer->test_attempt_id) ?></td>
                            <td><?= h($testAttemptAnswer->question_id) ?></td>
                            <td><?= h($testAttemptAnswer->answer_id) ?></td>
                            <td><?= h($testAttemptAnswer->user_answer_text) ?></td>
                            <td><?= h($testAttemptAnswer->is_correct) ?></td>
                            <td><?= h($testAttemptAnswer->answered_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'TestAttemptAnswers', 'action' => 'view', $testAttemptAnswer->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'TestAttemptAnswers', 'action' => 'edit', $testAttemptAnswer->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'TestAttemptAnswers', 'action' => 'delete', $testAttemptAnswer->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $testAttemptAnswer->id),
                                    ]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>