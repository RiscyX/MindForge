<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Question $question
 * @var \Cake\Collection\CollectionInterface|string[] $tests
 * @var \Cake\Collection\CollectionInterface|string[] $categories
 * @var \Cake\Collection\CollectionInterface|string[] $difficulties
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Questions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="questions form content">
            <?= $this->Form->create($question) ?>
            <fieldset>
                <legend><?= __('Add Question') ?></legend>
                <?php
                    echo $this->Form->control('test_id', ['options' => $tests, 'empty' => true]);
                    echo $this->Form->control('category_id', ['options' => $categories]);
                    echo $this->Form->control('difficulty_id', ['options' => $difficulties, 'empty' => true]);
                    echo $this->Form->control('source_type');
                    echo $this->Form->control('created_by');
                    echo $this->Form->control('is_active');
                    echo $this->Form->control('position');
                    echo $this->Form->control('created_at');
                    echo $this->Form->control('updated_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
