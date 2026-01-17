// Initialize Sortable on load
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('questions-container');
    if (container) {
        Sortable.create(container, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'bg-secondary',
            onEnd: function() {
                updateQuestionOrder();
            }
        });
    }

    // AI Generate Button
    const aiBtn = document.getElementById('ai-generate-test');
    if (aiBtn) {
        aiBtn.addEventListener('click', function() {
            Swal.fire({
                title: aiStrings.generateTitle,
                input: 'textarea',
                inputLabel: aiStrings.inputLabel,
                inputPlaceholder: aiStrings.inputPlaceholder,
                showCancelButton: true,
                confirmButtonText: aiStrings.confirmButtonText,
                showLoaderOnConfirm: true,
                background: '#19191a',
                color: '#fff',
                customClass: {
                    popup: 'border border-secondary p-5'
                },
                preConfirm: (prompt) => {
                    if (!prompt) {
                        Swal.showValidationMessage(aiStrings.validationMessage);
                        return false;
                    }
                    const csrfToken = document.querySelector('input[name="_csrfToken"]').value;
                    return fetch(config.generateAiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({ prompt: prompt })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value && result.value.success) {
                        fillFormWithAiData(result.value.data);
                        Swal.fire({
                            title: aiStrings.successTitle,
                            text: aiStrings.successMessage,
                            icon: 'success'
                        });
                    } else {
                         Swal.fire({
                            title: aiStrings.errorTitle,
                            text: result.value ? result.value.message : aiStrings.unknownError,
                            icon: 'error'
                        });
                    }
                }
            });
        });
    }
});

function fillFormWithAiData(data) {
    // Fill Title and Description for each language
    if (data.translations) {
        for (const [langId, trans] of Object.entries(data.translations)) {
            // CakePHP naming convention: test_translations[langId][title]
            // ID convention: test-translations-{langId}-title
            
            const titleInput = document.getElementById(`test-translations-${langId}-title`);
            const descInput = document.getElementById(`test-translations-${langId}-description`);
            
            if (titleInput && trans.title) {
                titleInput.value = trans.title;
            }
            if (descInput && trans.description) {
                descInput.value = trans.description;
            }
        }
    }

    if (data.questions && Array.isArray(data.questions)) {
        // Clear existing questions? Maybe ask user? For now just append or clear? 
        // Let's clear to be safe if it's a "Generate Test" action
        document.getElementById('questions-container').innerHTML = '';
        questionIndex = 0;
        answerCounters = {};

        data.questions.forEach(qData => {
            addQuestion(qData);
        });
    }
}

function addQuestion(data = null) {
    const container = document.getElementById('questions-container');
    const index = questionIndex++;
    
    // Determine type from data or default
    let defaultType = questionTypes.MULTIPLE_CHOICE;
    if (data && data.type) {
        if (data.type === 'true_false') defaultType = questionTypes.TRUE_FALSE;
        else if (data.type === 'text') defaultType = questionTypes.TEXT;
        // else multiple_choice
    }

    let html = `
    <div class="card mb-3 bg-dark text-white border-secondary question-card" id="question-${index}" data-index="${index}">
        <div class="card-header d-flex justify-content-between align-items-center border-secondary">
            <div class="d-flex align-items-center">
                <span class="drag-handle me-2" style="cursor: grab; color: #6c757d;"><i class="bi bi-grip-vertical fs-5"></i></span>
                <h6 class="mb-0 question-number">Question ${index + 1}</h6>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(${index})">&times;</button>
        </div>
        <div class="card-body">
            ${data && data.id ? `<input type="hidden" name="questions[${index}][id]" value="${data.id}">` : ''}
            <input type="hidden" name="questions[${index}][source_type]" value="${data && data.source_type ? data.source_type : (data ? 'db' : 'human')}">
            <input type="hidden" class="question-position" name="questions[${index}][position]" value="${index}">
            <input type="hidden" name="questions[${index}][is_active]" value="1">

            <div class="mb-3">
                <label class="form-label">Type</label>
                <select class="form-select" name="questions[${index}][question_type]" onchange="changeQuestionType(${index}, this.value)">
                    <option value="${questionTypes.MULTIPLE_CHOICE}" ${defaultType === questionTypes.MULTIPLE_CHOICE ? 'selected' : ''}>Multiple Choice</option>
                    <option value="${questionTypes.TRUE_FALSE}" ${defaultType === questionTypes.TRUE_FALSE ? 'selected' : ''}>True/False</option>
                    <option value="${questionTypes.TEXT}" ${defaultType === questionTypes.TEXT ? 'selected' : ''}>Text</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Question Text</label>
                ${generateTranslationInputs('questions', index, 'question_translations', 'content', data ? data.translations : null)}
            </div>

            <div id="answers-container-${index}" class="answers-container mb-3">
                <!-- Answers will be loaded here based on type -->
            </div>
            
            <button type="button" id="add-answer-btn-${index}" class="btn btn-sm btn-outline-secondary" onclick="addAnswer(${index})">
                <i class="bi bi-plus-circle"></i> Add Answer Option
            </button>
        </div>
    </div>
    `;
    
    const div = document.createElement('div');
    div.innerHTML = html;
    container.appendChild(div.firstElementChild);
    
    // Initialize answers
    changeQuestionType(index, defaultType, data ? data.answers : null);
    
    updateQuestionOrder();
}

function removeQuestion(index) {
    const el = document.getElementById(`question-${index}`);
    if (el) {
        el.remove();
        updateQuestionOrder();
    }
}

function updateQuestionOrder() {
    const cards = document.querySelectorAll('#questions-container .question-card');
    cards.forEach((card, newIndex) => {
        // Update visual number
        const numberLabel = card.querySelector('.question-number');
        if (numberLabel) {
             numberLabel.textContent = `Question ${newIndex + 1}`;
        }
        
        // Update position input
        const posInput = card.querySelector('.question-position');
        if (posInput) {
            posInput.value = newIndex;
        }
    });
}

function generateTranslationInputs(baseName, index, subName, fieldName, values = null) {
    let html = '';
    for (const [langId, langName] of Object.entries(languages)) {
        // Find value if exists. values structure: { 'en': 'Text', 'hu': 'Szoveg' } or similar
        // Or maybe keyed by langId directly? Let's assume passed data keys by lang code (e.g. en_US) or keys used in languages object?
        // The PHP languages array is $id => $name (e.g. 1 => 'English').
        // The AI data likely won't know our IDs. We might need to map language codes to IDs in PHP or here.
        // For now, let's assume the keys in `values` match the keys in `languages` (the IDs). 
        // Realistically, AI returns 'en', 'hu'. We need to map 'en' -> langId 1.
        // We can pass that map from PHP.
        
        let val = '';
        if (values && values[langId]) {
            val = values[langId];
        }
        
        // Quick hack: if values is keyed by iso code (en, hu) and we only have IDs here.
        // We will deal with that in the PHP variable injection.
        
        html += `
        <div class="input-group mb-2">
            <span class="input-group-text bg-secondary text-white border-secondary" style="width: 100px;">${langName}</span>
            <input type="hidden" name="${baseName}[${index}][${subName}][${langId}][language_id]" value="${langId}">
            <input type="text" class="form-control bg-dark text-white border-secondary" name="${baseName}[${index}][${subName}][${langId}][${fieldName}]" placeholder="Translation for ${langName}" value="${val}">
        </div>
        `;
    }
    return html;
}

function generateAnswerTranslationInputs(qIndex, aIndex, values = null) {
    let html = '';
    
    // Check if we have hardcoded translations for this answer (e.g. True/False) logic handled in caller?
    // Actually, update: we will use 'values' passed here.
    // If specific values are passed (like hardcoded True/False for each lang), use them.
    
    for (const [langId, langName] of Object.entries(languages)) {
        let val = '';
        if (values && values[langId]) {
            // values[langId] might be an object { content: "..." } or string "..." depending on earlier structure
            // In addFixedAnswer for T/F, we will pass { langId: "TranslatedTrue" }
             val = values[langId];
        }

        html += `
        <div class="input-group input-group-sm mb-1">
            <span class="input-group-text bg-secondary text-white border-secondary" style="width: 80px;">${langName}</span>
            <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][answer_translations][${langId}][language_id]" value="${langId}">
            <input type="text" class="form-control bg-dark text-white border-secondary" name="questions[${qIndex}][answers][${aIndex}][answer_translations][${langId}][content]" placeholder="Answer text..." value="${val}">
        </div>
        `;
    }
    return html;
}

function changeQuestionType(index, type, answersData = null) {
    const container = document.getElementById(`answers-container-${index}`);
    const addBtn = document.getElementById(`add-answer-btn-${index}`);
    container.innerHTML = ''; // Clear existing answers
    if (answerCounters[index] === undefined) {
         answerCounters[index] = 0;
    } else {
         answerCounters[index] = 0; // reset
    }
    
    if (type === questionTypes.TEXT) {
        addBtn.style.display = 'none';
        container.innerHTML = '<p class="text-muted fst-italic">Text questions do not have predefined answers in this form currently.</p>';
    } else if (type === questionTypes.TRUE_FALSE) {
        addBtn.style.display = 'none';
        
        let trueCorrect = true; 
        let falseCorrect = false;
        
        // Prepare translations for True and False buttons
        let trueTransMap = {};
        let falseTransMap = {};
        
        // We use the global trueFalseTranslations which is keyed by langId
        if (typeof trueFalseTranslations !== 'undefined') {
            for (const [langId, trans] of Object.entries(trueFalseTranslations)) {
                trueTransMap[langId] = trans.true;
                falseTransMap[langId] = trans.false;
            }
        }

        // If AI data provided correct/incorrect flags, use them
        let trueId = null;
        let falseId = null;

        if (answersData && answersData.length >= 2) {
             // Assume index 0 is True, index 1 is False from AI standard response
             trueCorrect = answersData[0].is_correct;
             falseCorrect = answersData[1].is_correct;
             trueId = answersData[0].id;
             falseId = answersData[1].id;
        }

        addFixedAnswer(index, 0, 'True', trueCorrect, trueTransMap, trueId);
        addFixedAnswer(index, 1, 'False', falseCorrect, falseTransMap, falseId);
    } else {

        addBtn.style.display = 'inline-block';
        
        if (answersData && Array.isArray(answersData)) {
            answersData.forEach(ans => {
                addAnswer(index, ans);
            });
        } else {
            addAnswer(index); // Add at least one option
            addAnswer(index);
        }
    }
}

function addFixedAnswer(qIndex, aIndex, defaultText, isCorrect, translations = null, id = null) {
    const container = document.getElementById(`answers-container-${qIndex}`);
    
    // Determine checkbox state
    const checked = isCorrect ? 'checked' : '';

    let html = `
    <div class="card mb-2 bg-secondary text-white">
         <div class="card-body p-2">
             <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center">
                    <strong class="me-3">Option: ${defaultText}</strong>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="questions[${qIndex}][correct_answer_index]" value="${aIndex}" ${checked} 
                               onchange="updateFixedAnswerCorrectness(${qIndex}, ${aIndex})">
                        <label class="form-check-label">Correct</label>
                    </div>
                </div>
                <!-- Hidden input that actually submits the 1/0 value -->
                ${id ? `<input type="hidden" name="questions[${qIndex}][answers][${aIndex}][id]" value="${id}">` : ''}
                <input type="hidden" class="fixed-answer-correct-input" name="questions[${qIndex}][answers][${aIndex}][is_correct]" value="${isCorrect ? 1 : 0}">
                <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][source_type]" value="human">
             </div>
             ${generateAnswerTranslationInputs(qIndex, aIndex, translations)}
         </div>
    </div>
    `;
    const div = document.createElement('div');
    div.innerHTML = html;
    container.appendChild(div.firstElementChild);
}

function updateFixedAnswerCorrectness(qIndex, selectedAIndex) {
    // This helper ensures that when a radio button is clicked, the hidden inputs for all fixed answers 
    // in this question are updated to reflect the new state (1 for selected, 0 for others).
    const container = document.getElementById(`answers-container-${qIndex}`);
    const correctInputs = container.querySelectorAll('.fixed-answer-correct-input');
    
    // We iterate based on the order they appear, which matches aIndex 0 and 1 usually
    correctInputs.forEach((input, idx) => {
         if (idx === selectedAIndex) {
             input.value = "1";
         } else {
             input.value = "0";
         }
    });
}

function addAnswer(qIndex, data = null) {
    if (answerCounters[qIndex] === undefined) answerCounters[qIndex] = 0;
    const aIndex = answerCounters[qIndex]++;
    const container = document.getElementById(`answers-container-${qIndex}`);
    
    let isCorrect = data ? data.is_correct : false;
    let id = data ? data.id : null;
    
    let html = `
    <div class="card mb-2 bg-secondary text-white" id="q${qIndex}-a${aIndex}">
        <div class="card-body p-2">
             <div class="d-flex align-items-center justify-content-between mb-2">
                 <div class="form-check">
                    <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][is_correct]" value="0">
                    <input class="form-check-input" type="checkbox" name="questions[${qIndex}][answers][${aIndex}][is_correct]" value="1" ${isCorrect ? 'checked' : ''}>
                    <label class="form-check-label">Correct</label>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeAnswer(${qIndex}, ${aIndex})">Remove</button>
             </div>
             ${id ? `<input type="hidden" name="questions[${qIndex}][answers][${aIndex}][id]" value="${id}">` : ''}
             <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][source_type]" value="${data ? 'ai' : 'human'}">
             ${generateAnswerTranslationInputs(qIndex, aIndex, data ? data.translations : null)}
        </div>
    </div>
    `;
    
    const div = document.createElement('div');
    div.innerHTML = html;
    container.appendChild(div.firstElementChild);
}

function removeAnswer(qIndex, aIndex) {
    const el = document.getElementById(`q${qIndex}-a${aIndex}`);
    if (el) el.remove();
}
