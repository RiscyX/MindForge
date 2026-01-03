<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Question $question
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Question'), ['action' => 'edit', $question->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Question'), ['action' => 'delete', $question->id], ['confirm' => __('Are you sure you want to delete # {0}?', $question->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Questions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Question'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="questions view content">
            <h3><?= h($question->question_type) ?></h3>
            <table>
                <tr>
                    <th><?= __('Test') ?></th>
                    <td><?= $question->hasValue('test') ? $this->Html->link($question->test->id, ['controller' => 'Tests', 'action' => 'view', $question->test->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Category') ?></th>
                    <td><?= $question->hasValue('category') ? $this->Html->link($question->category->id, ['controller' => 'Categories', 'action' => 'view', $question->category->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Difficulty') ?></th>
                    <td><?= $question->hasValue('difficulty') ? $this->Html->link($question->difficulty->name, ['controller' => 'Difficulties', 'action' => 'view', $question->difficulty->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Question Type') ?></th>
                    <td><?= h($question->question_type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Original Language') ?></th>
                    <td><?= $question->hasValue('original_language') ? $this->Html->link($question->original_language->name, ['controller' => 'Languages', 'action' => 'view', $question->original_language->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Source Type') ?></th>
                    <td><?= h($question->source_type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($question->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $question->created_by === null ? '' : $this->Number->format($question->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Position') ?></th>
                    <td><?= $question->position === null ? '' : $this->Number->format($question->position) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($question->created_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Updated At') ?></th>
                    <td><?= h($question->updated_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Active') ?></th>
                    <td><?= $question->is_active ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Answers') ?></h4>
                <?php if (!empty($question->answers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Question Id') ?></th>
                            <th><?= __('Source Type') ?></th>
                            <th><?= __('Is Correct') ?></th>
                            <th><?= __('Source Text') ?></th>
                            <th><?= __('Position') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th><?= __('Updated At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($question->answers as $answer) : ?>
                        <tr>
                            <td><?= h($answer->id) ?></td>
                            <td><?= h($answer->question_id) ?></td>
                            <td><?= h($answer->source_type) ?></td>
                            <td><?= h($answer->is_correct) ?></td>
                            <td><?= h($answer->source_text) ?></td>
                            <td><?= h($answer->position) ?></td>
                            <td><?= h($answer->created_at) ?></td>
                            <td><?= h($answer->updated_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Answers', 'action' => 'view', $answer->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Answers', 'action' => 'edit', $answer->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Answers', 'action' => 'delete', $answer->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $answer->id),
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
                <h4><?= __('Related Question Translations') ?></h4>
                <?php if (!empty($question->question_translations)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Question Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Content') ?></th>
                            <th><?= __('Explanation') ?></th>
                            <th><?= __('Source Type') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($question->question_translations as $questionTranslation) : ?>
                        <tr>
                            <td><?= h($questionTranslation->id) ?></td>
                            <td><?= h($questionTranslation->question_id) ?></td>
                            <td><?= h($questionTranslation->language_id) ?></td>
                            <td><?= h($questionTranslation->content) ?></td>
                            <td><?= h($questionTranslation->explanation) ?></td>
                            <td><?= h($questionTranslation->source_type) ?></td>
                            <td><?= h($questionTranslation->created_by) ?></td>
                            <td><?= h($questionTranslation->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'QuestionTranslations', 'action' => 'view', $questionTranslation->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'QuestionTranslations', 'action' => 'edit', $questionTranslation->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'QuestionTranslations', 'action' => 'delete', $questionTranslation->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $questionTranslation->id),
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
                <?php if (!empty($question->test_attempt_answers)) : ?>
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
                        <?php foreach ($question->test_attempt_answers as $testAttemptAnswer) : ?>
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