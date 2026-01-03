<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AiRequest $aiRequest
 * @var \Cake\Collection\CollectionInterface|string[] $users
 * @var \Cake\Collection\CollectionInterface|string[] $tests
 * @var \Cake\Collection\CollectionInterface|string[] $languages
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Ai Requests'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="aiRequests form content">
            <?= $this->Form->create($aiRequest) ?>
            <fieldset>
                <legend><?= __('Add Ai Request') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('test_id', ['options' => $tests, 'empty' => true]);
                    echo $this->Form->control('language_id', ['options' => $languages, 'empty' => true]);
                    echo $this->Form->control('source_medium');
                    echo $this->Form->control('source_reference');
                    echo $this->Form->control('type');
                    echo $this->Form->control('input_payload');
                    echo $this->Form->control('output_payload');
                    echo $this->Form->control('status');
                    echo $this->Form->control('created_at');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
