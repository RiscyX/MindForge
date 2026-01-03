<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestTranslation $testTranslation
 * @var \Cake\Collection\CollectionInterface|string[] $tests
 * @var \Cake\Collection\CollectionInterface|string[] $languages
 * @var \Cake\Collection\CollectionInterface|string[] $translators
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Test Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testTranslations form content">
            <?= $this->Form->create($testTranslation) ?>
            <fieldset>
                <legend><?= __('Add Test Translation') ?></legend>
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
