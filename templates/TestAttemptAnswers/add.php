<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttemptAnswer $testAttemptAnswer
 * @var \Cake\Collection\CollectionInterface|string[] $testAttempts
 * @var \Cake\Collection\CollectionInterface|string[] $questions
 * @var \Cake\Collection\CollectionInterface|string[] $answers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Test Attempt Answers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testAttemptAnswers form content">
            <?= $this->Form->create($testAttemptAnswer) ?>
            <fieldset>
                <legend><?= __('Add Test Attempt Answer') ?></legend>
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
