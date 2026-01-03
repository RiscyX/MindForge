<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Difficulty'), ['action' => 'edit', $difficulty->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Difficulty'), ['action' => 'delete', $difficulty->id], ['confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Difficulties'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Difficulty'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="difficulties view content">
            <h3><?= h($difficulty->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($difficulty->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($difficulty->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Level') ?></th>
                    <td><?= $this->Number->format($difficulty->level) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Questions') ?></h4>
                <?php if (!empty($difficulty->questions)) : ?>
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
                        <?php foreach ($difficulty->questions as $question) : ?>
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
                <?php if (!empty($difficulty->test_attempts)) : ?>
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
                        <?php foreach ($difficulty->test_attempts as $testAttempt) : ?>
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
                <h4><?= __('Related Tests') ?></h4>
                <?php if (!empty($difficulty->tests)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Category Id') ?></th>
                            <th><?= __('Difficulty Id') ?></th>
                            <th><?= __('Number Of Questions') ?></th>
                            <th><?= __('Is Public') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created At') ?></th>
                            <th><?= __('Updated At') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($difficulty->tests as $test) : ?>
                        <tr>
                            <td><?= h($test->id) ?></td>
                            <td><?= h($test->category_id) ?></td>
                            <td><?= h($test->difficulty_id) ?></td>
                            <td><?= h($test->number_of_questions) ?></td>
                            <td><?= h($test->is_public) ?></td>
                            <td><?= h($test->created_by) ?></td>
                            <td><?= h($test->created_at) ?></td>
                            <td><?= h($test->updated_at) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Tests', 'action' => 'view', $test->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Tests', 'action' => 'edit', $test->id]) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Tests', 'action' => 'delete', $test->id],
                                    [
                                        'method' => 'delete',
                                        'confirm' => __('Are you sure you want to delete # {0}?', $test->id),
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