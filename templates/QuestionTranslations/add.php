<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\QuestionTranslation $questionTranslation
 * @var \Cake\Collection\CollectionInterface|string[] $questions
 * @var \Cake\Collection\CollectionInterface|string[] $languages
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Question Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="questionTranslations form content">
            <?= $this->Form->create($questionTranslation) ?>
            <fieldset>
                <legend><?= __('Add Question Translation') ?></legend>
                <?php
                    echo $this->Form->control('question_id', ['options' => $questions]);
                    echo $this->Form->control('language_id', ['options' => $languages]);
                    echo $this->Form->control('content');
                    echo $this->Form->control('explanation');
                    echo $this->Form->control('source_type');
                    echo $this->Form->control('created_by');
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
