<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestTranslation $testTranslation
 * @var string[]|\Cake\Collection\CollectionInterface $tests
 * @var string[]|\Cake\Collection\CollectionInterface $languages
 * @var string[]|\Cake\Collection\CollectionInterface $translators
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $testTranslation->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $testTranslation->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Test Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testTranslations form content">
            <?= $this->Form->create($testTranslation) ?>
            <fieldset>
                <legend><?= __('Edit Test Translation') ?></legend>
                <?php
                    echo $this->Form->control('test_id', ['options' => $tests]);
                    echo $this->Form->control('language_id', ['options' => $languages]);
                    echo $this->Form->control('title');
                    echo $this->Form->control('slug');
                    echo $this->Form->control('description');
                    echo $this->Form->control('translator_id', ['options' => $translators, 'empty' => true]);
                    echo $this->Form->control('is_complete');
                    echo $this->Form->control('translated_at', ['empty' => true]);
                    echo $this->Form->control('created_at');
                    echo $this->Form->control('updated_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
