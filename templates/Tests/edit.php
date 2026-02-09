<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Test $test
 * @var \Cake\Collection\CollectionInterface|string[] $categories
 * @var \Cake\Collection\CollectionInterface|string[] $difficulties
 * @var \Cake\Collection\CollectionInterface|string[] $languages
 * @var array<string, mixed> $aiGenerationLimit
 */
use App\Model\Entity\Question;

$aiGenerateLimited = !((bool)($aiGenerationLimit['allowed'] ?? true));
$aiLimitMessage = __('AI generation limit reached. Limit resets tomorrow.');
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= __('Edit Test') ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?= $this->Html->link(__('List Tests'), ['action' => 'index', 'lang' => $this->request->getParam('lang')], ['class' => 'btn btn-sm btn-secondary me-2']) ?>
        <?= $this->Form->postLink(
            __('Delete'),
            ['action' => 'delete', $test->id, 'lang' => $this->request->getParam('lang')],
            ['confirm' => __('Are you sure you want to delete # {0}?', $test->id), 'class' => 'btn btn-sm btn-danger']
        ) ?>
    </div>
</div>

<?= $this->Form->create($test, ['class' => 'needs-validation']) ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4 bg-dark text-white">
            <div class="card-header">
                <h5 class="mb-0"><?= __('Basic Information') ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                         <?= $this->Form->control('category_id', ['options' => $categories, 'class' => 'form-select', 'label' => ['class' => 'form-label']]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $this->Form->control('difficulty_id', ['options' => $difficulties, 'empty' => false, 'class' => 'form-select', 'label' => ['class' => 'form-label']]) ?>
                    </div>
                     <div class="col-12">
                         <div class="form-check">
                            <?= $this->Form->checkbox('is_public', ['class' => 'form-check-input', 'id' => 'is_public']) ?>
                            <label class="form-check-label" for="is_public"><?= __('Is Public') ?></label>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 bg-dark text-white">
            <div class="card-header d-flex justify-content-between align-items-center">
                 <h5 class="mb-0"><?= __('Questions') ?></h5>
                 <button type="button" class="btn btn-sm btn-primary" onclick="addQuestion()"><?= __('Add Question') ?></button>
            </div>
            <div class="card-body">
                 <div id="questions-container">
                    <!-- Questions will be added here dynamically -->
                 </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
         <div class="card mb-4 bg-dark text-white">
            <div class="card-header">
                <h5 class="mb-0"><?= __('Translations') ?></h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="accordionTranslations">
                <?php foreach ($languages as $langId => $langName): ?>
                    <div class="accordion-item bg-dark border-secondary">
                        <h2 class="accordion-header" id="heading<?= $langId ?>">
                            <button class="accordion-button collapsed bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $langId ?>" aria-expanded="false" aria-controls="collapse<?= $langId ?>">
                                <?= h($langName) ?>
                            </button>
                        </h2>
                         <div id="collapse<?= $langId ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $langId ?>" data-bs-parent="#accordionTranslations">
                            <div class="accordion-body bg-dark text-white">
                                <?= $this->Form->hidden("test_translations.$langId.id") ?>
                                <?= $this->Form->hidden("test_translations.$langId.language_id", ['value' => $langId]) ?>
                                <?= $this->Form->control("test_translations.$langId.title", ['class' => 'form-control mb-2', 'label' => __('Title ({0})', $langName)]) ?>
                                <?= $this->Form->control("test_translations.$langId.description", ['class' => 'form-control', 'label' => __('Description ({0})', $langName), 'type' => 'textarea', 'rows' => 3]) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
         </div>
         <div class="d-grid gap-2">
             <span class="d-block" title="<?= h($aiGenerateLimited ? $aiLimitMessage : '') ?>">
                 <button
                     type="button"
                     class="btn btn-outline-info w-100"
                     id="ai-generate-test"
                     <?= $aiGenerateLimited ? 'disabled aria-disabled="true"' : '' ?>
                     title="<?= h($aiGenerateLimited ? $aiLimitMessage : '') ?>"
                 >
                     <i class="bi bi-robot"></i> <?= __('Generate Content with AI') ?>
                 </button>
             </span>
             <small class="text-muted">
                 <?= __('AI generations today: {0}/{1}', (int)($aiGenerationLimit['used'] ?? 0), (int)($aiGenerationLimit['limit'] ?? 0)) ?>
             </small>
             <button type="button" class="btn btn-outline-warning" id="ai-translate-test">
                 <i class="bi bi-translate"></i> <?= __('Translate Test with AI') ?>
             </button>
             <hr>
             <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary btn-lg']) ?>
         </div>
    </div>
</div>
<?= $this->Form->end() ?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Pass PHP data to JS
    const languages = <?= json_encode($languages) ?>;
    const languagesMeta = <?= json_encode($languagesMeta ?? []) ?>;
    const trueFalseTranslations = (() => {
        const out = {};
        (languagesMeta || []).forEach(lang => {
            const id = Number(lang.id);
            const code = String(lang.code || '').toLowerCase();
            if (!id) return;
            if (code.startsWith('hu')) {
                out[id] = { true: 'Igaz', false: 'Hamis' };
            } else {
                out[id] = { true: 'True', false: 'False' };
            }
        });
        return out;
    })();
    const questionTypes = {
        TRUE_FALSE: '<?= Question::TYPE_TRUE_FALSE ?>',
        MULTIPLE_CHOICE: '<?= Question::TYPE_MULTIPLE_CHOICE ?>',
        TEXT: '<?= Question::TYPE_TEXT ?>'
    };
    const aiStrings = {
        generateTitle: '<?= __('Generate Test with AI') ?>',
        inputLabel: '<?= __('Describe the test you want to create or update (topic, difficulty, number of questions, etc.)') ?>',
        inputPlaceholder: '<?= __('E.g., Create a 10-question test about Ancient Rome history, focused on military battles, medium difficulty.') ?>',
        confirmButtonText: '<?= __('Generate') ?>',
        validationMessage: '<?= __('Please enter a prompt') ?>',
        successTitle: '<?= __('Success!') ?>',
        successMessage: '<?= __('Test generated successfully.') ?>',
        errorTitle: '<?= __('Error') ?>',
        unknownError: '<?= __('Unknown error occurred') ?>',
        limitReachedMessage: '<?= __('AI generation limit reached. Limit resets tomorrow.') ?>',
        translateTitle: '<?= __('Translate Test with AI') ?>',
        translateConfirmText: '<?= __('Translate') ?>',
        translateInfo: '<?= __('This will translate the current test content into all configured languages.') ?>',
        translateSuccess: '<?= __('Translations updated.') ?>',
        translationInProgress: '<?= __('Translation in progress...') ?>'
        ,trueLabel: '<?= __('True') ?>'
        ,falseLabel: '<?= __('False') ?>'
    };
    const config = {
        generateAiUrl: '<?= $this->Url->build(['action' => 'generateWithAi', 'lang' => $this->request->getParam('lang')]) ?>',
        translateAiUrl: '<?= $this->Url->build(['action' => 'translateWithAi', $test->id, 'lang' => $this->request->getParam('lang')]) ?>',
        currentLanguageId: <?= (int)($currentLanguageId ?? 0) ?>,
        aiGenerateLimited: <?= $aiGenerateLimited ? 'true' : 'false' ?>
    };
    
    // Global state variables used by external JS
    let questionIndex = 0;
    let answerCounters = {};

    // Load existing questions
    document.addEventListener('DOMContentLoaded', function() {
        const existingQuestions = <?= json_encode($questionsData ?? []) ?>;
        if (existingQuestions && existingQuestions.length > 0) {
            existingQuestions.forEach(qData => {
                addQuestion(qData);
            });
        }
    });

</script>
<?= $this->Html->script('tests_add') ?>
