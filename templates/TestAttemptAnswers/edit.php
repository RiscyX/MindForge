<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttemptAnswer $testAttemptAnswer
 * @var string[]|\Cake\Collection\CollectionInterface $testAttempts
 * @var string[]|\Cake\Collection\CollectionInterface $questions
 * @var string[]|\Cake\Collection\CollectionInterface $answers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $testAttemptAnswer->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $testAttemptAnswer->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Test Attempt Answers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testAttemptAnswers form content">
            <?= $this->Form->create($testAttemptAnswer) ?>
            <fieldset>
                <legend><?= __('Edit Test Attempt Answer') ?></legend>
                <?php
                    echo $this->Form->control('test_attempt_id', ['options' => $testAttempts]);
                    echo $this->Form->control('question_id', ['options' => $questions]);
                    echo $this->Form->control('answer_id', ['options' => $answers, 'empty' => true]);
                    echo $this->Form->control('user_answer_text');
                    echo $this->Form->control('is_correct');
                    echo $this->Form->control('answered_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
