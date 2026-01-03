<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestTranslation $testTranslation
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Test Translation'), ['action' => 'edit', $testTranslation->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Test Translation'), ['action' => 'delete', $testTranslation->id], ['confirm' => __('Are you sure you want to delete # {0}?', $testTranslation->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Test Translations'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Test Translation'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="testTranslations view content">
            <h3><?= h($testTranslation->title) ?></h3>
            <table>
                <tr>
                    <th><?= __('Test') ?></th>
                    <td><?= $testTranslation->hasValue('test') ? $this->Html->link($testTranslation->test->id, ['controller' => 'Tests', 'action' => 'view', $testTranslation->test->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Language') ?></th>
                    <td><?= $testTranslation->hasValue('language') ? $this->Html->link($testTranslation->language->name, ['controller' => 'Languages', 'action' => 'view', $testTranslation->language->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Title') ?></th>
                    <td><?= h($testTranslation->title) ?></td>
                </tr>
                <tr>
                    <th><?= __('Slug') ?></th>
                    <td><?= h($testTranslation->slug) ?></td>
                </tr>
                <tr>
                    <th><?= __('Translator') ?></th>
                    <td><?= $testTranslation->hasValue('translator') ? $this->Html->link($testTranslation->translator->email, ['controller' => 'Users', 'action' => 'view', $testTranslation->translator->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($testTranslation->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Translated At') ?></th>
                    <td><?= h($testTranslation->translated_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created At') ?></th>
                    <td><?= h($testTranslation->created_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Updated At') ?></th>
                    <td><?= h($testTranslation->updated_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Complete') ?></th>
                    <td><?= $testTranslation->is_complete ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($testTranslation->description)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>