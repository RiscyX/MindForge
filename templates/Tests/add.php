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
$lang = $this->request->getParam('lang');
$categoryOptions = is_array($categories) ? $categories : $categories->toArray();
$currentCategoryId = (int)($test->category_id ?? 0);
$currentCategoryLabel = $currentCategoryId > 0 && isset($categoryOptions[$currentCategoryId])
    ? (string)$categoryOptions[$currentCategoryId]
    : '';

$this->Html->css('tests_builder', ['block' => 'css']);
?>
<div class="mf-test-builder">
<div class="mf-test-builder__header">
    <div>
        <h1 class="h2 mf-test-builder__title"><?= __('Create Quiz') ?></h1>
        <div class="mf-test-builder__subtitle"><?= __('Build questions, translations, and publishing settings in one flow.') ?></div>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?= $this->Html->link(__('Back to Tests'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
    </div>
</div>

<?= $this->Form->create($test, ['class' => 'needs-validation mf-test-builder__form']) ?>
<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4 mf-test-builder__panel">
            <div class="mf-test-builder__panel-header">
                <h5 class="mb-0"><?= __('Basic Information') ?></h5>
            </div>
            <div class="mf-test-builder__panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="category-combobox-input"><?= __('Category') ?></label>
                        <?= $this->Form->hidden('category_id', ['id' => 'category-id-hidden', 'value' => $currentCategoryId > 0 ? $currentCategoryId : null]) ?>
                        <div class="mf-test-combobox" id="category-combobox" data-mf-combobox="category">
                            <input
                                id="category-combobox-input"
                                type="text"
                                class="form-control"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="<?= h(__('Start typing category...')) ?>"
                                value="<?= h($currentCategoryLabel) ?>"
                                aria-expanded="false"
                                aria-controls="category-combobox-list"
                            >
                            <div class="mf-test-combobox__panel" id="category-combobox-list" role="listbox"></div>
                        </div>
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

        <div class="card mb-4 mf-test-builder__panel">
            <div class="mf-test-builder__panel-header">
                 <h5 class="mb-0"><?= __('Questions') ?></h5>
                 <button type="button" class="btn btn-sm btn-primary" data-mf-add-question><i class="bi bi-plus-circle me-1"></i><?= __('Add Question') ?></button>
            </div>
            <div class="mf-test-builder__panel-body">
                 <div id="questions-container">
                    <!-- Questions will be added here dynamically -->
                 </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
         <div class="card mb-4 mf-test-builder__panel">
            <div class="mf-test-builder__panel-header">
                <h5 class="mb-0"><?= __('Translations') ?></h5>
            </div>
            <div class="mf-test-builder__panel-body">
                <div class="accordion" id="accordionTranslations">
                <?php foreach ($languages as $langId => $langName): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $langId ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $langId ?>" aria-expanded="false" aria-controls="collapse<?= $langId ?>">

                                <?= h($langName) ?>
                            </button>
                        </h2>
                         <div id="collapse<?= $langId ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $langId ?>" data-bs-parent="#accordionTranslations">
                            <div class="accordion-body">
                                <?= $this->Form->hidden("test_translations.$langId.language_id", ['value' => $langId]) ?>
                                <?= $this->Form->control("test_translations.$langId.title", ['class' => 'form-control mb-2', 'label' => __('Title ({0})', $langName)]) ?>
                                <?= $this->Form->control("test_translations.$langId.description", ['class' => 'form-control', 'label' => __('Description ({0})', $langName), 'type' => 'textarea', 'rows' => 3]) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <hr class="my-3">
                <div>
                    <label for="ai-supporting-documents" class="form-label mb-1">
                        <?= __('Optional source files for AI generation') ?>
                    </label>
                    <input
                        id="ai-supporting-documents"
                        class="d-none"
                        type="file"
                        multiple
                        accept=".pdf,.docx,.odt,.txt,.md,.csv,.json,.xml,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,text/plain,text/markdown,text/csv,application/json,application/xml,text/xml"
                    >
                    <div class="mf-doc-upload mt-2" data-mf-doc-upload>
                        <button type="button" class="btn btn-outline-light btn-sm" data-mf-doc-trigger>
                            <?= __('Choose files') ?>
                        </button>
                        <div class="mf-doc-upload__meta" data-mf-doc-meta data-empty-label="<?= h(__('No files selected (optional).')) ?>" data-selected-label="<?= h(__('selected files')) ?>">
                            <?= __('No files selected (optional).') ?>
                        </div>
                    </div>
                    <div class="form-text">
                        <?= __('Optional. Upload PDFs, DOCX, or text-like files to help AI generate more accurate quiz content.') ?>
                    </div>
                </div>
            </div>
         </div>
         <div class="d-grid gap-2 mf-test-builder__actions">
             <span class="d-block" title="<?= h($aiGenerateLimited ? $aiLimitMessage : '') ?>">
                 <button
                     type="button"
                     class="btn btn-outline-light w-100"
                     id="ai-generate-test"
                     <?= $aiGenerateLimited ? 'disabled aria-disabled="true"' : '' ?>
                     title="<?= h($aiGenerateLimited ? $aiLimitMessage : '') ?>"
                 >
                    <i class="bi bi-robot"></i> <?= __('Generate Test with AI') ?>
                 </button>
             </span>
             <small class="text-muted">
                 <?= __('AI generations today: {0}/{1}', (int)($aiGenerationLimit['used'] ?? 0), (int)($aiGenerationLimit['limit'] ?? 0)) ?>
             </small>
             <button type="button" class="btn btn-outline-light" id="ai-translate-test">
                <i class="bi bi-translate"></i> <?= __('Translate Test with AI') ?>
             </button>
             <hr>
             <?= $this->Form->button(__('Save Test'), ['class' => 'btn btn-primary btn-lg']) ?>
         </div>
    </div>
</div>
<?= $this->Form->end() ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
$builderConfig = [
    'languages' => $languages,
    'languagesMeta' => $languagesMeta ?? [],
    'categoryComboboxMap' => $categoryOptions,
    'categoryComboboxSelectedId' => (int)$currentCategoryId,
    'categoryComboboxNoResults' => __('No category found'),
    'categoryComboboxInvalid' => __('Please choose a category from the list.'),
    'questionTypes' => [
        'TRUE_FALSE' => Question::TYPE_TRUE_FALSE,
        'MULTIPLE_CHOICE' => Question::TYPE_MULTIPLE_CHOICE,
        'TEXT' => Question::TYPE_TEXT,
        'MATCHING' => Question::TYPE_MATCHING,
    ],
    'aiStrings' => [
        'generateTitle' => __('Generate Test with AI'),
        'inputLabel' => __('Describe the test you want to create (topic, difficulty, number of questions, etc.)'),
        'inputPlaceholder' => __('E.g., Create a 10-question test about Ancient Rome history, focused on military battles, medium difficulty.'),
        'confirmButtonText' => __('Generate'),
        'validationMessage' => __('Please enter a prompt'),
        'successTitle' => __('Success!'),
        'successMessage' => __('Test generated successfully.'),
        'errorTitle' => __('Error'),
        'requestFailedPrefix' => __('Request failed:'),
        'unknownError' => __('Unknown error occurred'),
        'limitReachedMessage' => __('AI generation limit reached. Limit resets tomorrow.'),
        'translateTitle' => __('Translate Test with AI'),
        'translateConfirmText' => __('Translate'),
        'translateInfo' => __('This will translate the current test content into all configured languages.'),
        'translateSuccess' => __('Translations updated.'),
        'translationInProgress' => __('Translation in progress...'),
        'trueLabel' => __('True'),
        'falseLabel' => __('False'),
        'aiRateLimited' => __('The AI service is temporarily overloaded. Please try again in a few minutes.'),
        'aiServerError' => __('The AI service encountered an error. Please try again later.'),
        'aiNetworkError' => __('Could not reach the AI service. Please check your connection and try again.'),
        'aiTimeoutError' => __('The AI service took too long to respond. Please try again.'),
    ],
    'config' => [
        'generateAiUrl' => $this->Url->build(['action' => 'generateWithAi', 'lang' => $this->request->getParam('lang')]),
        'translateAiUrl' => $this->Url->build(['action' => 'translateWithAi', 'lang' => $this->request->getParam('lang')]),
        'currentLanguageId' => (int)($currentLanguageId ?? 0),
        'aiGenerateLimited' => $aiGenerateLimited,
    ],
];

$builderConfigJson = json_encode(
    $builderConfig,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
);
if ($builderConfigJson === false) {
    $builderConfigJson = '{}';
}
?>
<script type="application/json" id="mf-tests-builder-config"><?= $builderConfigJson ?></script>
<?= $this->Html->script('tests_builder_bootstrap') ?>
<?= $this->Html->script('tests_category_autocomplete') ?>
<?= $this->Html->script('tests_add') ?>
