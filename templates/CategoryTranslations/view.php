<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CategoryTranslation $categoryTranslation
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Category Translation'), ['action' => 'edit', $categoryTranslation->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Category Translation'), ['action' => 'delete', $categoryTranslation->id], ['confirm' => __('Are you sure you want to delete # {0}?', $categoryTranslation->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Category Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Category Translation'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="categoryTranslations view content">
            <h3><?= h($categoryTranslation->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Category') ?></th>
                    <td><?= $categoryTranslation->hasValue('category') ? $this->Html->link($categoryTranslation->category->id, ['controller' => 'Categories', 'action' => 'view', $categoryTranslation->category->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Language') ?></th>
                    <td><?= $categoryTranslation->hasValue('language') ? $this->Html->link($categoryTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $categoryTranslation->language->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($categoryTranslation->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($categoryTranslation->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($categoryTranslation->created_at) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($categoryTranslation->description)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>