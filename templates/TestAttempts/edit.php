<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttempt $testAttempt
 * @var string[]|\Cake\Collection\CollectionInterface $users
 * @var string[]|\Cake\Collection\CollectionInterface $tests
 * @var string[]|\Cake\Collection\CollectionInterface $categories
 * @var string[]|\Cake\Collection\CollectionInterface $difficulties
 * @var string[]|\Cake\Collection\CollectionInterface $languages
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $testAttempt->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $testAttempt->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Test Attempts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testAttempts form content">
            <?= $this->Form->create($testAttempt) ?>
            <fieldset>
                <legend><?= __('Edit Test Attempt') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('test_id', ['options' => $tests, 'empty' => true]);
                    echo $this->Form->control('category_id', ['options' => $categories, 'empty' => true]);
                    echo $this->Form->control('difficulty_id', ['options' => $difficulties, 'empty' => true]);
                    echo $this->Form->control('language_id', ['options' => $languages, 'empty' => true]);
                    echo $this->Form->control('started_at');
                    echo $this->Form->control('finished_at', ['empty' => true]);
                    echo $this->Form->control('score');
                    echo $this->Form->control('total_questions');
                    echo $this->Form->control('correct_answers');
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
