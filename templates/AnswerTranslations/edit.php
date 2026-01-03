<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AnswerTranslation $answerTranslation
 * @var string[]|\Cake\Collection\CollectionInterface $answers
 * @var string[]|\Cake\Collection\CollectionInterface $languages
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $answerTranslation->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $answerTranslation->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Answer Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="answerTranslations form content">
            <?= $this->Form->create($answerTranslation) ?>
            <fieldset>
                <legend><?= __('Edit Answer Translation') ?></legend>
                <?php
                    echo $this->Form->control('answer_id', ['options' => $answers]);
                    echo $this->Form->control('language_id', ['options' => $languages]);
                    echo $this->Form->control('content');
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
