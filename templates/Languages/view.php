<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Language $language
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Language'), ['action' => 'edit', $language->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Language'), ['action' => 'delete', $language->id], ['confirm' => __('Are you sure you want to delete # {0}?', $language->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Languages'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Language'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="languages view content">
            <h3><?= h($language->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Code') ?></th>
                    <td><?= h($language->code) ?></td>
                </tr>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($language->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($language->id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Ai Requests') ?></h4>
                <?php if (!empty($language->ai_requests)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Test Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Source Medium') ?></th>
                            <th><?= __('Source Reference') ?></th>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Input Payload') ?></th>
                            <th><?= __('Output Payload') ?></th>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($language->ai_requests as $aiRequest) : ?>
                        <tr>
                            <td><?= h($aiRequest->id) ?></td>
                            <td><?= h($aiRequest->user_id) ?></td>
                            <td><?= h($aiRequest->test_id) ?></td>
                            <td><?= h($aiRequest->language_id) ?></td>
                            <td><?= h($aiRequest->source_medium) ?></td>
                            <td><?= h($aiRequest->source_reference) ?></td>
                            <td><?= h($aiRequest->type) ?></td>
                            <td><?= h($aiRequest->input_payload) ?></td>
                            <td><?= h($aiRequest->output_payload) ?></td>
                            <td><?= h($aiRequest->status) ?></td>
                            <td><?= h($aiRequest->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['prefix' => 'Admin', 'controller' => 'AiRequests', 'action' => 'view', $aiRequest->id, 'lang' => $lang ?? 'en']) ?>
                                <?= $this->Html->link(__('Edit'), ['prefix' => 'Admin', 'controller' => 'AiRequests', 'action' => 'edit', $aiRequest->id, 'lang' => $lang ?? 'en']) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['prefix' => 'Admin', 'controller' => 'AiRequests', 'action' => 'delete', $aiRequest->id, 'lang' => $lang ?? 'en'],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $aiRequest->id),
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
                <h4><?= __('Related Answer Translations') ?></h4>
                <?php if (!empty($language->answer_translations)) : ?>
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
                        <?php foreach ($language->answer_translations as $answerTranslation) : ?>
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
                <h4><?= __('Related Category Translations') ?></h4>
                <?php if (!empty($language->category_translations)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Category Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Description') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($language->category_translations as $categoryTranslation) : ?>
                        <tr>
                            <td><?= h($categoryTranslation->id) ?></td>
                            <td><?= h($categoryTranslation->category_id) ?></td>
                            <td><?= h($categoryTranslation->language_id) ?></td>
                            <td><?= h($categoryTranslation->name) ?></td>
                            <td><?= h($categoryTranslation->description) ?></td>
                            <td><?= h($categoryTranslation->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'CategoryTranslations', 'action' => 'view', $categoryTranslation->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'CategoryTranslations', 'action' => 'edit', $categoryTranslation->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'CategoryTranslations', 'action' => 'delete', $categoryTranslation->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $categoryTranslation->id),
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
                <?php if (!empty($language->question_translations)) : ?>
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
                        <?php foreach ($language->question_translations as $questionTranslation) : ?>
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
                <h4><?= __('Related Test Attempts') ?></h4>
                <?php if (!empty($language->test_attempts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Test Id') ?></th>
                            <th><?= __('Category Id') ?></th>
                            <th><?= __('Difficulty Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Started At') ?></th>
                            <th><?= __('Finished At') ?></th>
                            <th><?= __('Score') ?></th>
                            <th><?= __('Total Questions') ?></th>
                            <th><?= __('Correct Answers') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($language->test_attempts as $testAttempt) : ?>
                        <tr>
                            <td><?= h($testAttempt->id) ?></td>
                            <td><?= h($testAttempt->user_id) ?></td>
                            <td><?= h($testAttempt->test_id) ?></td>
                            <td><?= h($testAttempt->category_id) ?></td>
                            <td><?= h($testAttempt->difficulty_id) ?></td>
                            <td><?= h($testAttempt->language_id) ?></td>
                            <td><?= h($testAttempt->started_at) ?></td>
                            <td><?= h($testAttempt->finished_at) ?></td>
                            <td><?= h($testAttempt->score) ?></td>
                            <td><?= h($testAttempt->total_questions) ?></td>
                            <td><?= h($testAttempt->correct_answers) ?></td>
                            <td><?= h($testAttempt->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'TestAttempts', 'action' => 'view', $testAttempt->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'TestAttempts', 'action' => 'edit', $testAttempt->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'TestAttempts', 'action' => 'delete', $testAttempt->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $testAttempt->id),
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
                <h4><?= __('Related Test Translations') ?></h4>
                <?php if (!empty($language->test_translations)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Test Id') ?></th>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('Slug') ?></th>
                            <th><?= __('Description') ?></th>
                            <th><?= __('Translator Id') ?></th>
                            <th><?= __('Is Complete') ?></th>
                            <th><?= __('Translated At') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th><?= __('Updated At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($language->test_translations as $testTranslation) : ?>
                        <tr>
                            <td><?= h($testTranslation->id) ?></td>
                            <td><?= h($testTranslation->test_id) ?></td>
                            <td><?= h($testTranslation->language_id) ?></td>
                            <td><?= h($testTranslation->title) ?></td>
                            <td><?= h($testTranslation->slug) ?></td>
                            <td><?= h($testTranslation->description) ?></td>
                            <td><?= h($testTranslation->translator_id) ?></td>
                            <td><?= h($testTranslation->is_complete) ?></td>
                            <td><?= h($testTranslation->translated_at) ?></td>
                            <td><?= h($testTranslation->created_at) ?></td>
                            <td><?= h($testTranslation->updated_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'TestTranslations', 'action' => 'view', $testTranslation->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'TestTranslations', 'action' => 'edit', $testTranslation->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'TestTranslations', 'action' => 'delete', $testTranslation->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $testTranslation->id),
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
