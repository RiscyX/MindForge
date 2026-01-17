<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Test $test
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Test'), ['action' => 'edit', $test->id, 'lang' => $this->request->getParam('lang')], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Test'), ['action' => 'delete', $test->id, 'lang' => $this->request->getParam('lang')], ['confirm' => __('Are you sure you want to delete # {0}?', $test->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Tests'), ['action' => 'index', 'lang' => $this->request->getParam('lang')], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Test'), ['action' => 'add', 'lang' => $this->request->getParam('lang')], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="tests view content">
            <h3><?= h($test->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Category') ?></th>
                    <td><?= $test->hasValue('category') ? $this->Html->link($test->category->id, ['controller' => 'Categories', 'action' => 'view', $test->category->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Difficulty') ?></th>
                    <td><?= $test->hasValue('difficulty') ? $this->Html->link($test->difficulty->name, ['controller' => 'Difficulties', 'action' => 'view', $test->difficulty->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($test->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Number Of Questions') ?></th>
                    <td><?= $test->number_of_questions === null ? '' : $this->Number->format($test->number_of_questions) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $test->created_by === null ? '' : $this->Number->format($test->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($test->created_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Updated At') ?></th>
                    <td><?= h($test->updated_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Public') ?></th>
                    <td><?= $test->is_public ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Ai Requests') ?></h4>
                <?php if (!empty($test->ai_requests)) : ?>
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
                        <?php foreach ($test->ai_requests as $aiRequest) : ?>
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
                                <?= $this->Html->link(__('View'), ['controller' => 'AiRequests', 'action' => 'view', $aiRequest->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'AiRequests', 'action' => 'edit', $aiRequest->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'AiRequests', 'action' => 'delete', $aiRequest->id],
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
                <h4><?= __('Related Questions') ?></h4>
                <?php if (!empty($test->questions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Test Id') ?></th>
                            <th><?= __('Category Id') ?></th>
                            <th><?= __('Difficulty Id') ?></th>
                            <th><?= __('Question Type') ?></th>
                            <th><?= __('Original Language Id') ?></th>
                            <th><?= __('Source Type') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Is Active') ?></th>
                            <th><?= __('Position') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th><?= __('Updated At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($test->questions as $question) : ?>
                        <tr>
                            <td><?= h($question->id) ?></td>
                            <td><?= h($question->test_id) ?></td>
                            <td><?= h($question->category_id) ?></td>
                            <td><?= h($question->difficulty_id) ?></td>
                            <td><?= h($question->question_type) ?></td>
                            <td><?= h($question->original_language_id) ?></td>
                            <td><?= h($question->source_type) ?></td>
                            <td><?= h($question->created_by) ?></td>
                            <td><?= h($question->is_active) ?></td>
                            <td><?= h($question->position) ?></td>
                            <td><?= h($question->created_at) ?></td>
                            <td><?= h($question->updated_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Questions', 'action' => 'view', $question->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Questions', 'action' => 'edit', $question->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Questions', 'action' => 'delete', $question->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $question->id),
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
                <?php if (!empty($test->test_attempts)) : ?>
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
                        <?php foreach ($test->test_attempts as $testAttempt) : ?>
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
                <?php if (!empty($test->test_translations)) : ?>
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
                        <?php foreach ($test->test_translations as $testTranslation) : ?>
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
            <div class="related">
                <h4><?= __('Related User Favorite Tests') ?></h4>
                <?php if (!empty($test->user_favorite_tests)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Test Id') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($test->user_favorite_tests as $userFavoriteTest) : ?>
                        <tr>
                            <td><?= h($userFavoriteTest->id) ?></td>
                            <td><?= h($userFavoriteTest->user_id) ?></td>
                            <td><?= h($userFavoriteTest->test_id) ?></td>
                            <td><?= h($userFavoriteTest->created_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'UserFavoriteTests', 'action' => 'view', $userFavoriteTest->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'UserFavoriteTests', 'action' => 'edit', $userFavoriteTest->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'UserFavoriteTests', 'action' => 'delete', $userFavoriteTest->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $userFavoriteTest->id),
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