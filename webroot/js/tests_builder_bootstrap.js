(() => {
    const configNode = document.getElementById('mf-tests-builder-config');
    if (!configNode) {
        return;
    }

    let payload = {};
    try {
        payload = JSON.parse(configNode.textContent || '{}');
    } catch {
        payload = {};
    }

    const languages = (payload.languages && typeof payload.languages === 'object') ? payload.languages : {};
    const languagesMeta = Array.isArray(payload.languagesMeta) ? payload.languagesMeta : [];

    window.languages = languages;
    window.languagesMeta = languagesMeta;
    window.categoryComboboxMap = (payload.categoryComboboxMap && typeof payload.categoryComboboxMap === 'object')
        ? payload.categoryComboboxMap
        : {};
    window.categoryComboboxSelectedId = Number(payload.categoryComboboxSelectedId || 0);
    window.categoryComboboxNoResults = typeof payload.categoryComboboxNoResults === 'string'
        ? payload.categoryComboboxNoResults
        : 'No category found';
    window.categoryComboboxInvalid = typeof payload.categoryComboboxInvalid === 'string'
        ? payload.categoryComboboxInvalid
        : 'Please choose a category from the list.';

    const trueFalseTranslations = {};
    languagesMeta.forEach((lang) => {
        const id = Number(lang && lang.id);
        const code = String((lang && lang.code) || '').toLowerCase();
        if (!id) {
            return;
        }

        if (code.startsWith('hu')) {
            trueFalseTranslations[id] = { true: 'Igaz', false: 'Hamis' };
        } else {
            trueFalseTranslations[id] = { true: 'True', false: 'False' };
        }
    });

    window.trueFalseTranslations = trueFalseTranslations;
    window.questionTypes = (payload.questionTypes && typeof payload.questionTypes === 'object')
        ? payload.questionTypes
        : {};
    window.aiStrings = (payload.aiStrings && typeof payload.aiStrings === 'object')
        ? payload.aiStrings
        : {};
    window.config = (payload.config && typeof payload.config === 'object')
        ? payload.config
        : {};

    if (!Number.isFinite(window.questionIndex)) {
        window.questionIndex = 0;
    }
    if (!window.answerCounters || typeof window.answerCounters !== 'object') {
        window.answerCounters = {};
    }

    const bindAddQuestionButtons = () => {
        const buttons = document.querySelectorAll('[data-mf-add-question]');
        buttons.forEach((button) => {
            if (button.dataset.mfAddQuestionBound === '1') {
                return;
            }
            button.dataset.mfAddQuestionBound = '1';
            button.addEventListener('click', () => {
                if (typeof window.addQuestion === 'function') {
                    window.addQuestion();
                }
            });
        });
    };

    const preloadExistingQuestions = () => {
        const existingQuestions = Array.isArray(payload.existingQuestions) ? payload.existingQuestions : [];
        if (!existingQuestions.length || typeof window.addQuestion !== 'function') {
            return;
        }

        existingQuestions.forEach((questionData) => {
            window.addQuestion(questionData);
        });
    };

    const init = () => {
        bindAddQuestionButtons();
        preloadExistingQuestions();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
