function initTestBuilderAiTools() {
    const swalTheme = {
        buttonsStyling: false,
        reverseButtons: true,
        customClass: {
            container: 'mf-swal2-container',
            popup: 'mf-swal2-popup',
            title: 'mf-swal2-title',
            htmlContainer: 'mf-swal2-html',
            actions: 'mf-swal2-actions',
            confirmButton: 'btn btn-primary mf-swal2-confirm',
            cancelButton: 'btn btn-outline-light mf-swal2-cancel',
            icon: 'mf-swal2-icon'
        },
        showClass: {
            popup: 'mf-swal2-animate-in'
        },
        hideClass: {
            popup: 'mf-swal2-animate-out'
        }
    };
    const themedSwal = (options) => Swal.fire(Object.assign({}, swalTheme, options || {}));

    initSupportingDocumentsPicker();

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
        if (config.aiGenerateLimited) {
            aiBtn.addEventListener('click', function(event) {
                event.preventDefault();
                themedSwal({
                    title: aiStrings.errorTitle,
                    text: aiStrings.limitReachedMessage,
                    icon: 'warning'
                });
            });

        } else {
            aiBtn.addEventListener('click', function() {
            themedSwal({
                title: aiStrings.generateTitle,
                input: 'textarea',
                inputLabel: aiStrings.inputLabel,
                inputPlaceholder: aiStrings.inputPlaceholder,
                showCancelButton: true,
                confirmButtonText: aiStrings.confirmButtonText,
                showLoaderOnConfirm: true,
                preConfirm: (prompt) => {
                    const docsEl = document.getElementById('ai-supporting-documents');
                    const promptText = String(prompt || '').trim();

                    if (!promptText) {
                        Swal.showValidationMessage(aiStrings.validationMessage);
                        return false;
                    }

                    const formData = new FormData();
                    formData.append('prompt', promptText);
                    if (docsEl && docsEl.files) {
                        Array.from(docsEl.files).forEach((f) => {
                            formData.append('documents[]', f);
                        });
                    }

                    const csrfToken = document.querySelector('input[name="_csrfToken"]').value;
                    return fetch(config.generateAiUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-Token': csrfToken
                        },
                        body: formData
                    })
                    .then(response => {
                        return response.text().then(raw => {
                            let payload = null;
                            try {
                                payload = JSON.parse(raw);
                            } catch {
                                payload = null;
                            }

                            if (!response.ok) {
                                if (response.status === 429 && payload && payload.limit_reached) {
                                    throw new Error(aiStrings.limitReachedMessage);
                                }

                                const msg = payload && payload.message ? payload.message : response.statusText;
                                throw new Error(msg);
                            }

                            return payload;
                        });
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `${aiStrings.requestFailedPrefix || 'Request failed:'} ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value && result.value.success) {
                        fillFormWithAiData(result.value.data);
                        themedSwal({
                            title: aiStrings.successTitle,
                            text: aiStrings.successMessage,
                            icon: 'success'
                        });
                    } else {
                         themedSwal({
                            title: aiStrings.errorTitle,
                            text: result.value ? result.value.message : aiStrings.unknownError,
                            icon: 'error'
                        });
                    }
                }
            });
            });
        }
    }

    // AI Translate Button
    const translateBtn = document.getElementById('ai-translate-test');
    if (translateBtn && config.translateAiUrl) {
        translateBtn.addEventListener('click', function() {
            themedSwal({
                title: aiStrings.translateTitle || 'Translate',
                text: aiStrings.translateInfo || '',
                showCancelButton: true,
                confirmButtonText: aiStrings.translateConfirmText || 'Translate'
            }).then((confirmResult) => {
                if (!confirmResult.isConfirmed) return;

                const csrfTokenEl = document.querySelector('input[name="_csrfToken"]');
                const csrfToken = csrfTokenEl ? csrfTokenEl.value : '';
                const payload = buildTranslatePayload();

                let pct = 8;
                let timer = null;

                themedSwal({
                    title: aiStrings.translationInProgress || 'Translation in progress...',
                    html: `
                        <div style="text-align:left;">
                            <div class="progress" style="height: 10px; background: rgba(255,255,255,0.08);">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: ${pct}%; background: #0dcaf0;"></div>
                            </div>
                            <div class="mf-muted" style="margin-top: 10px; font-size: 0.95rem;">
                                ${aiStrings.translateInfo || ''}
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        timer = window.setInterval(() => {
                            // Indeterminate-ish: creep up to 92%
                            pct = Math.min(92, pct + Math.random() * 7);
                            const bar = Swal.getHtmlContainer()?.querySelector('.progress-bar');
                            if (bar) {
                                bar.style.width = `${pct}%`;
                            }
                        }, 450);
                    },
                    willClose: () => {
                        if (timer) window.clearInterval(timer);
                    }
                });

                fetch(config.translateAiUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.text().then(raw => ({ ok: response.ok, status: response.status, raw })))
                .then(({ ok, raw, status }) => {
                    let body = null;
                    try {
                        body = JSON.parse(raw);
                    } catch {
                        body = null;
                    }

                    if (!ok) {
                        const msg = (body && body.message)
                            ? body.message
                            : (status === 403 ? 'CSRF verification failed. Please reload the page and try again.' : `HTTP ${status}`);
                        throw new Error(msg);
                    }

                    // Finish bar
                    const bar = Swal.getHtmlContainer()?.querySelector('.progress-bar');
                    if (bar) bar.style.width = '100%';

                    Swal.close();

                    if (body && body.success) {
                        applyAiTranslations(body.data);
                        themedSwal({
                            title: aiStrings.successTitle,
                            text: aiStrings.translateSuccess || aiStrings.successMessage,
                            icon: 'success'
                        });
                    } else {
                        throw new Error(body && body.message ? body.message : aiStrings.unknownError);
                    }
                })
                .catch((err) => {
                    Swal.close();
                    themedSwal({
                        title: aiStrings.errorTitle,
                        text: String(err),
                        icon: 'error'
                    });
                });
            });
        });
    }
}

function initSupportingDocumentsPicker() {
    const input = document.getElementById('ai-supporting-documents');
    const trigger = document.querySelector('[data-mf-doc-trigger]');
    const meta = document.querySelector('[data-mf-doc-meta]');
    if (!input || !trigger || !meta) {
        return;
    }

    const emptyLabel = meta.getAttribute('data-empty-label') || 'No files selected (optional).';
    const selectedLabel = meta.getAttribute('data-selected-label') || 'selected files';

    const updateMeta = () => {
        const files = input.files ? Array.from(input.files) : [];
        if (!files.length) {
            meta.textContent = emptyLabel;
            return;
        }

        if (files.length === 1) {
            meta.textContent = files[0].name;
            return;
        }

        meta.textContent = `${files.length} ${selectedLabel}: ${files.slice(0, 2).map(f => f.name).join(', ')}${files.length > 2 ? '…' : ''}`;
    };

    trigger.addEventListener('click', () => input.click());
    input.addEventListener('change', updateMeta);
    updateMeta();
}

// Initialize on load (and handle scripts loaded late)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTestBuilderAiTools);
} else {
    initTestBuilderAiTools();
}

function buildTranslatePayload() {
    const sourceLanguageId = (config.currentLanguageId && Number.isFinite(Number(config.currentLanguageId)))
        ? Number(config.currentLanguageId)
        : 0;

    const titleEl = document.getElementById(`test-translations-${sourceLanguageId}-title`);
    const descEl = document.getElementById(`test-translations-${sourceLanguageId}-description`);

    const payload = {
        source_language_id: sourceLanguageId,
        test: {
            title: titleEl ? titleEl.value : '',
            description: descEl ? descEl.value : ''
        },
        questions: []
    };

    const cards = document.querySelectorAll('#questions-container .question-card');
    cards.forEach(card => {
        const qIndex = Number(card.getAttribute('data-index'));
        if (!Number.isFinite(qIndex)) return;

        const qIdInput = card.querySelector(`input[name="questions[${qIndex}][id]"]`);
        const qTypeSelect = card.querySelector(`select[name="questions[${qIndex}][question_type]"]`);
        const qTextInput = card.querySelector(`input[name="questions[${qIndex}][question_translations][${sourceLanguageId}][content]"]`);

        const qId = qIdInput ? Number(qIdInput.value) : null;
        const qType = qTypeSelect ? qTypeSelect.value : '';
        const qContent = qTextInput ? qTextInput.value : '';

        const question = {
            id: qId,
            type: qType,
            content: qContent,
            answers: []
        };

        const answerIdInputs = card.querySelectorAll(`input[name^="questions[${qIndex}][answers]"][name$="[id]"]`);
        answerIdInputs.forEach(idEl => {
            const name = idEl.getAttribute('name') || '';
            const m = name.match(/questions\[(\d+)\]\[answers\]\[(\d+)\]\[id\]/);
            if (!m) return;
            const aIndex = Number(m[2]);

            const aId = Number(idEl.value);
            const aCorrectEl = card.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][is_correct]"]`);
            const aTextEl = card.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][answer_translations][${sourceLanguageId}][content]"]`);
            const aSideEl = card.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][match_side]"]`);
            const aGroupEl = card.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][match_group]"]`);
            const isCorrect = aCorrectEl ? (aCorrectEl.value === '1' || aCorrectEl.checked) : false;
            const aContent = aTextEl ? aTextEl.value : '';
            const matchSide = aSideEl ? String(aSideEl.value || '') : '';
            const rawGroup = aGroupEl ? Number(aGroupEl.value) : null;
            question.answers.push({
                id: aId,
                is_correct: isCorrect,
                content: aContent,
                match_side: matchSide || null,
                match_group: Number.isFinite(rawGroup) && rawGroup > 0 ? rawGroup : null
            });
        });

        // Fallback for non-fixed answers where id is not present yet
        if (question.answers.length === 0) {
            const anyAnswerInputs = card.querySelectorAll(`input[name^="questions[${qIndex}][answers]"][name$="[answer_translations][${sourceLanguageId}][content]"]`);
            anyAnswerInputs.forEach(aTextEl => {
                const name = aTextEl.getAttribute('name') || '';
                const m = name.match(/questions\[(\d+)\]\[answers\]\[(\d+)\]\[answer_translations\]\[\d+\]\[content\]/);
                if (!m) return;
                const aIndex = Number(m[2]);
                const aCorrectEl = card.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][is_correct]"]`);
                const aSideEl = card.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][match_side]"]`);
                const aGroupEl = card.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][match_group]"]`);
                const isCorrect = aCorrectEl ? (aCorrectEl.value === '1' || aCorrectEl.checked) : false;
                const matchSide = aSideEl ? String(aSideEl.value || '') : '';
                const rawGroup = aGroupEl ? Number(aGroupEl.value) : null;
                question.answers.push({
                    id: null,
                    is_correct: isCorrect,
                    content: aTextEl.value,
                    match_side: matchSide || null,
                    match_group: Number.isFinite(rawGroup) && rawGroup > 0 ? rawGroup : null
                });
            });
        }

        payload.questions.push(question);
    });

    return payload;
}

function applyAiTranslations(data) {
    if (!data) return;

    // Test title/description translations
    if (data.translations) {
        for (const [langId, trans] of Object.entries(data.translations)) {
            const titleInput = document.getElementById(`test-translations-${langId}-title`);
            const descInput = document.getElementById(`test-translations-${langId}-description`);
            if (titleInput && trans && trans.title) titleInput.value = trans.title;
            if (descInput && trans && trans.description !== undefined) descInput.value = trans.description;
        }
    }

    if (!Array.isArray(data.questions)) return;

    // Build a map of question id -> index
    const questionIdToIndex = {};
    const cards = document.querySelectorAll('#questions-container .question-card');
    cards.forEach(card => {
        const qIndex = Number(card.getAttribute('data-index'));
        const qIdInput = card.querySelector(`input[name="questions[${qIndex}][id]"]`);
        if (qIdInput && qIdInput.value) {
            questionIdToIndex[qIdInput.value] = qIndex;
        }
    });

    data.questions.forEach(q => {
        if (!q) return;
        const qIndex = q.id !== undefined && q.id !== null ? questionIdToIndex[String(q.id)] : undefined;
        if (qIndex === undefined) return;

        if (q.translations) {
            for (const [langId, content] of Object.entries(q.translations)) {
                const input = document.querySelector(`input[name="questions[${qIndex}][question_translations][${langId}][content]"]`);
                if (input && content) input.value = content;
            }
        }

        if (Array.isArray(q.answers)) {
            q.answers.forEach(a => {
                if (!a || a.id === undefined || a.id === null) return;
                const idEl = document.querySelector(`input[name^="questions[${qIndex}][answers]"][name$="[id]"][value="${a.id}"]`);
                if (!idEl) return;
                const name = idEl.getAttribute('name') || '';
                const m = name.match(/questions\[(\d+)\]\[answers\]\[(\d+)\]\[id\]/);
                if (!m) return;
                const aIndex = Number(m[2]);

                if (a.translations) {
                    for (const [langId, content] of Object.entries(a.translations)) {
                        const input = document.querySelector(`input[name="questions[${qIndex}][answers][${aIndex}][answer_translations][${langId}][content]"]`);
                        if (input && content) input.value = content;
                    }
                }
            });
        }
    });
}

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
            addQuestion(normalizeAiQuestionData(qData));
        });
    }
}

function normalizeAiQuestionData(question) {
    if (!question || typeof question !== 'object') {
        return question;
    }
    if (question.type !== 'matching' || !Array.isArray(question.pairs) || Array.isArray(question.answers)) {
        return question;
    }

    const normalized = Object.assign({}, question);
    normalized.answers = [];
    let group = 1;
    question.pairs.forEach((pair) => {
        if (!pair || typeof pair !== 'object') return;
        const leftTranslations = pair.left_translations || pair.left || {};
        const rightTranslations = pair.right_translations || pair.right || {};
        normalized.answers.push({
            id: null,
            source_type: 'ai',
            is_correct: false,
            match_side: 'left',
            match_group: group,
            translations: leftTranslations,
        });
        normalized.answers.push({
            id: null,
            source_type: 'ai',
            is_correct: false,
            match_side: 'right',
            match_group: group,
            translations: rightTranslations,
        });
        group += 1;
    });

    return normalized;
}

function addQuestion(data = null) {
    const container = document.getElementById('questions-container');
    const index = questionIndex++;
    
    // Determine type from data or default
    let defaultType = questionTypes.MULTIPLE_CHOICE;
    if (data && data.type) {
        if (data.type === 'true_false') defaultType = questionTypes.TRUE_FALSE;
        else if (data.type === 'text') defaultType = questionTypes.TEXT;
        else if (data.type === 'matching') defaultType = questionTypes.MATCHING;
        // else multiple_choice
    }

    const questionSourceType = (data && data.source_type) ? data.source_type : 'human';

    let html = `
    <div class="card mb-3 question-card mf-test-builder__question" id="question-${index}" data-index="${index}">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="drag-handle me-2" style="cursor: grab; color: #6c757d;"><i class="bi bi-grip-vertical fs-5"></i></span>
                <h6 class="mb-0 question-number">Question ${index + 1}</h6>
            </div>
            <button type="button" class="btn btn-sm mf-test-builder__icon-btn mf-test-builder__icon-btn--danger" onclick="removeQuestion(${index})" aria-label="Remove question">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="card-body">
            ${data && data.id ? `<input type="hidden" name="questions[${index}][id]" value="${data.id}">` : ''}
            <input type="hidden" name="questions[${index}][source_type]" value="${questionSourceType}">
            <input type="hidden" class="question-position" name="questions[${index}][position]" value="${index}">
            <input type="hidden" name="questions[${index}][is_active]" value="1">

            <div class="mb-3">
                <label class="form-label">Type</label>
                <select class="form-select" name="questions[${index}][question_type]" onchange="changeQuestionType(${index}, this.value)">
                    <option value="${questionTypes.MULTIPLE_CHOICE}" ${defaultType === questionTypes.MULTIPLE_CHOICE ? 'selected' : ''}>Multiple Choice</option>
                    <option value="${questionTypes.TRUE_FALSE}" ${defaultType === questionTypes.TRUE_FALSE ? 'selected' : ''}>True/False</option>
                    <option value="${questionTypes.TEXT}" ${defaultType === questionTypes.TEXT ? 'selected' : ''}>Text</option>
                    <option value="${questionTypes.MATCHING}" ${defaultType === questionTypes.MATCHING ? 'selected' : ''}>Matching</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Question Text</label>
                ${generateTranslationInputs('questions', index, 'question_translations', 'content', data ? data.translations : null)}
            </div>

            <div id="answers-container-${index}" class="answers-container mb-3">
                <!-- Answers will be loaded here based on type -->
            </div>
            
            <button type="button" id="add-answer-btn-${index}" class="btn btn-sm btn-outline-secondary mf-test-builder__add-answer" onclick="addAnswer(${index})">
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
        let val = '';
        let translationId = '';
        if (values && values[langId] !== undefined && values[langId] !== null) {
            if (typeof values[langId] === 'object') {
                val = values[langId].content || '';
                translationId = values[langId].id || '';
            } else {
                val = values[langId];
            }
        }

        html += `
        <div class="input-group mb-2">
            <span class="input-group-text" style="width: 100px;">${langName}</span>
            ${translationId ? `<input type="hidden" name="${baseName}[${index}][${subName}][${langId}][id]" value="${translationId}">` : ''}
            <input type="hidden" name="${baseName}[${index}][${subName}][${langId}][language_id]" value="${langId}">
            <input type="text" class="form-control" name="${baseName}[${index}][${subName}][${langId}][${fieldName}]" placeholder="Translation for ${langName}" value="${val}">
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
        let translationId = '';
        if (values && values[langId]) {
            if (typeof values[langId] === 'object') {
                val = values[langId].content || '';
                translationId = values[langId].id || '';
            } else {
                val = values[langId];
            }
        }

        html += `
        <div class="input-group input-group-sm mb-1">
            <span class="input-group-text" style="width: 80px;">${langName}</span>
            ${translationId ? `<input type="hidden" name="questions[${qIndex}][answers][${aIndex}][answer_translations][${langId}][id]" value="${translationId}">` : ''}
            <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][answer_translations][${langId}][language_id]" value="${langId}">
            <input type="text" class="form-control" name="questions[${qIndex}][answers][${aIndex}][answer_translations][${langId}][content]" placeholder="Answer text..." value="${val}">
        </div>
        `;
    }
    return html;
}

function changeQuestionType(index, type, answersData = null) {
    const container = document.getElementById(`answers-container-${index}`);
    const addBtn = document.getElementById(`add-answer-btn-${index}`);
    const sourceTypeInput = document.querySelector(`input[name="questions[${index}][source_type]"]`);
    const questionSourceType = sourceTypeInput ? sourceTypeInput.value : 'human';
    container.innerHTML = ''; // Clear existing answers
    if (answerCounters[index] === undefined) {
         answerCounters[index] = 0;
    } else {
         answerCounters[index] = 0; // reset
    }
    
    if (type === questionTypes.TEXT) {
        addBtn.style.display = 'inline-block';
        addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Add Accepted Answer';

        if (answersData && Array.isArray(answersData) && answersData.length > 0) {
            answersData.forEach(ans => {
                addTextAcceptedAnswer(index, ans, questionSourceType);
            });
        } else {
            addTextAcceptedAnswer(index, null, questionSourceType);
        }
    } else if (type === questionTypes.TRUE_FALSE) {
        addBtn.style.display = 'none';
        
        let trueCorrect = true; 
        let falseCorrect = false;
        
        // Prepare translations for True and False inputs
        // Priority:
        // 1) Existing DB-provided translations (when editing)
        // 2) Fallback map built from language codes (when creating)
        let trueTransMap = {};
        let falseTransMap = {};

        // If AI data provided correct/incorrect flags, use them
        let trueId = null;
        let falseId = null;

        if (answersData && answersData.length >= 2) {
             // Assume index 0 is True, index 1 is False from AI standard response
             trueCorrect = answersData[0].is_correct;
             falseCorrect = answersData[1].is_correct;
             trueId = answersData[0].id;
             falseId = answersData[1].id;

             if (answersData[0].translations && answersData[1].translations) {
                 trueTransMap = answersData[0].translations;
                 falseTransMap = answersData[1].translations;
             }
        }

        if (Object.keys(trueTransMap).length === 0 && typeof trueFalseTranslations !== 'undefined') {
            for (const [langId, trans] of Object.entries(trueFalseTranslations)) {
                trueTransMap[langId] = trans.true;
                falseTransMap[langId] = trans.false;
            }
        }

        const fixedAnswerSourceType = (answersData && answersData.length >= 2)
            ? ((answersData[0] && answersData[0].source_type) ? answersData[0].source_type : questionSourceType)
            : questionSourceType;

        addFixedAnswer(
            index,
            0,
            (aiStrings && aiStrings.trueLabel) ? aiStrings.trueLabel : 'True',
            trueCorrect,
            trueTransMap,
            trueId,
            fixedAnswerSourceType,
        );
        addFixedAnswer(
            index,
            1,
            (aiStrings && aiStrings.falseLabel) ? aiStrings.falseLabel : 'False',
            falseCorrect,
            falseTransMap,
            falseId,
            fixedAnswerSourceType,
        );
    } else if (type === questionTypes.MATCHING) {
        addBtn.style.display = 'inline-block';
        addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Add Matching Pair';

        const grouped = {};
        if (Array.isArray(answersData)) {
            answersData.forEach(ans => {
                if (!ans || typeof ans !== 'object') return;
                const side = String(ans.match_side || '');
                const group = Number(ans.match_group);
                if (!['left', 'right'].includes(side) || !Number.isFinite(group) || group <= 0) return;
                if (!grouped[group]) grouped[group] = {};
                grouped[group][side] = ans;
            });
        }

        const groups = Object.keys(grouped)
            .map(Number)
            .filter(v => Number.isFinite(v) && v > 0)
            .sort((a, b) => a - b);

        if (groups.length > 0) {
            groups.forEach(group => {
                addMatchingPair(index, grouped[group], questionSourceType, group);
            });
        } else {
            addMatchingPair(index, null, questionSourceType, 1);
            addMatchingPair(index, null, questionSourceType, 2);
        }
    } else {
        addBtn.style.display = 'inline-block';
        addBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Add Answer Option';
        
        if (answersData && Array.isArray(answersData)) {
            answersData.forEach(ans => {
            addAnswer(index, ans, questionSourceType);
            });
        } else {
            addAnswer(index, null, questionSourceType); // Add at least one option
            addAnswer(index, null, questionSourceType);
        }
    }
}

function addMatchingPair(qIndex, pairData = null, questionSourceType = 'human', forcedGroup = null) {
    if (answerCounters[qIndex] === undefined) answerCounters[qIndex] = 0;

    const leftIndex = answerCounters[qIndex]++;
    const rightIndex = answerCounters[qIndex]++;
    const pairGroup = Number.isFinite(Number(forcedGroup)) && Number(forcedGroup) > 0
        ? Number(forcedGroup)
        : Math.max(1, Math.floor((leftIndex / 2) + 1));

    const left = pairData && pairData.left ? pairData.left : null;
    const right = pairData && pairData.right ? pairData.right : null;

    const leftId = left && left.id ? left.id : '';
    const rightId = right && right.id ? right.id : '';
    const leftSourceType = left && left.source_type ? left.source_type : questionSourceType;
    const rightSourceType = right && right.source_type ? right.source_type : questionSourceType;
    const leftTranslations = left && left.translations ? left.translations : null;
    const rightTranslations = right && right.translations ? right.translations : null;

    const container = document.getElementById(`answers-container-${qIndex}`);
    const rowId = `q${qIndex}-pair-${pairGroup}-${leftIndex}`;

    const html = `
    <div class="card mb-2 mf-test-builder__answer" id="${rowId}">
        <div class="card-body p-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <strong>Matching Pair #${pairGroup}</strong>
                <button type="button" class="btn btn-sm mf-test-builder__icon-btn mf-test-builder__icon-btn--danger" onclick="removeMatchingPair('${rowId}')" aria-label="Remove pair">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div class="text-muted mb-1">Left side</div>
                    ${leftId ? `<input type="hidden" name="questions[${qIndex}][answers][${leftIndex}][id]" value="${leftId}">` : ''}
                    <input type="hidden" name="questions[${qIndex}][answers][${leftIndex}][source_type]" value="${leftSourceType}">
                    <input type="hidden" name="questions[${qIndex}][answers][${leftIndex}][is_correct]" value="0">
                    <input type="hidden" name="questions[${qIndex}][answers][${leftIndex}][match_side]" value="left">
                    <input type="hidden" name="questions[${qIndex}][answers][${leftIndex}][match_group]" value="${pairGroup}">
                    ${generateAnswerTranslationInputs(qIndex, leftIndex, leftTranslations)}
                </div>
                <div class="col-md-6">
                    <div class="text-muted mb-1">Right side</div>
                    ${rightId ? `<input type="hidden" name="questions[${qIndex}][answers][${rightIndex}][id]" value="${rightId}">` : ''}
                    <input type="hidden" name="questions[${qIndex}][answers][${rightIndex}][source_type]" value="${rightSourceType}">
                    <input type="hidden" name="questions[${qIndex}][answers][${rightIndex}][is_correct]" value="0">
                    <input type="hidden" name="questions[${qIndex}][answers][${rightIndex}][match_side]" value="right">
                    <input type="hidden" name="questions[${qIndex}][answers][${rightIndex}][match_group]" value="${pairGroup}">
                    ${generateAnswerTranslationInputs(qIndex, rightIndex, rightTranslations)}
                </div>
            </div>
        </div>
    </div>
    `;

    const div = document.createElement('div');
    div.innerHTML = html;
    container.appendChild(div.firstElementChild);
}

function addTextAcceptedAnswer(qIndex, data = null, questionSourceType = null) {
    if (answerCounters[qIndex] === undefined) answerCounters[qIndex] = 0;
    const aIndex = answerCounters[qIndex]++;
    const container = document.getElementById(`answers-container-${qIndex}`);

    const sourceTypeInput = document.querySelector(`input[name="questions[${qIndex}][source_type]"]`);
    const fallbackQuestionSourceType = sourceTypeInput ? sourceTypeInput.value : 'human';
    const effectiveQuestionSourceType = questionSourceType || fallbackQuestionSourceType;
    const answerSourceType = (data && data.source_type) ? data.source_type : effectiveQuestionSourceType;
    const id = data ? data.id : null;

    const html = `
    <div class="card mb-2 mf-test-builder__answer" id="q${qIndex}-a${aIndex}">
        <div class="card-body p-2">
             <div class="d-flex align-items-center justify-content-between mb-2">
                 <strong>Accepted Answer</strong>
                 <button type="button" class="btn btn-sm mf-test-builder__icon-btn mf-test-builder__icon-btn--danger" onclick="removeAnswer(${qIndex}, ${aIndex})" aria-label="Remove accepted answer">
                     <i class="bi bi-trash3" aria-hidden="true"></i>
                 </button>
             </div>
             ${id ? `<input type="hidden" name="questions[${qIndex}][answers][${aIndex}][id]" value="${id}">` : ''}
             <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][is_correct]" value="1">
             <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][source_type]" value="${answerSourceType}">
             ${generateAnswerTranslationInputs(qIndex, aIndex, data ? data.translations : null)}
        </div>
    </div>
    `;

    const div = document.createElement('div');
    div.innerHTML = html;
    container.appendChild(div.firstElementChild);
}

function removeMatchingPair(rowId) {
    const el = document.getElementById(rowId);
    if (el) el.remove();
}

function addFixedAnswer(qIndex, aIndex, defaultText, isCorrect, translations = null, id = null, sourceType = 'human') {
    const container = document.getElementById(`answers-container-${qIndex}`);
    
    // Determine checkbox state
    const checked = isCorrect ? 'checked' : '';

    let html = `
    <div class="card mb-2 mf-test-builder__answer">
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
                <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][source_type]" value="${sourceType}">
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

function addAnswer(qIndex, data = null, questionSourceType = null) {
    const typeSelect = document.querySelector(`select[name="questions[${qIndex}][question_type]"]`);
    if (typeSelect && typeSelect.value === questionTypes.MATCHING) {
        addMatchingPair(qIndex, null, questionSourceType || 'human');
        return;
    }
    if (typeSelect && typeSelect.value === questionTypes.TEXT) {
        addTextAcceptedAnswer(qIndex, data, questionSourceType || 'human');
        return;
    }

    if (answerCounters[qIndex] === undefined) answerCounters[qIndex] = 0;
    const aIndex = answerCounters[qIndex]++;
    const container = document.getElementById(`answers-container-${qIndex}`);
    
    let isCorrect = data ? data.is_correct : false;
    let id = data ? data.id : null;
    
    const sourceTypeInput = document.querySelector(`input[name="questions[${qIndex}][source_type]"]`);
    const fallbackQuestionSourceType = sourceTypeInput ? sourceTypeInput.value : 'human';
    const effectiveQuestionSourceType = questionSourceType || fallbackQuestionSourceType;
    const answerSourceType = (data && data.source_type) ? data.source_type : effectiveQuestionSourceType;

    let html = `
    <div class="card mb-2 mf-test-builder__answer" id="q${qIndex}-a${aIndex}">
        <div class="card-body p-2">
             <div class="d-flex align-items-center justify-content-between mb-2">
                 <div class="form-check">
                    <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][is_correct]" value="0">
                    <input class="form-check-input" type="checkbox" name="questions[${qIndex}][answers][${aIndex}][is_correct]" value="1" ${isCorrect ? 'checked' : ''}>
                    <label class="form-check-label">Correct</label>
                </div>
                <button type="button" class="btn btn-sm mf-test-builder__icon-btn mf-test-builder__icon-btn--danger" onclick="removeAnswer(${qIndex}, ${aIndex})" aria-label="Remove answer">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>
             </div>
             ${id ? `<input type="hidden" name="questions[${qIndex}][answers][${aIndex}][id]" value="${id}">` : ''}
             <input type="hidden" name="questions[${qIndex}][answers][${aIndex}][source_type]" value="${answerSourceType}">
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
