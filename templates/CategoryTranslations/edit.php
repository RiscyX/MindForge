<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CategoryTranslation $categoryTranslation
 * @var string[]|\Cake\Collection\CollectionInterface $categories
 * @var string[]|\Cake\Collection\CollectionInterface $languages
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $categoryTranslation->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $categoryTranslation->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Category Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="categoryTranslations form content">
            <?= $this->Form->create($categoryTranslation) ?>
            <fieldset>
                <legend><?= __('Edit Category Translation') ?></legend>
                <?php
                    echo $this->Form->control('category_id', ['options' => $categories]);
                    echo $this->Form->control('language_id', ['options' => $languages]);
                    echo $this->Form->control('name');
                    echo $this->Form->control('description');
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
