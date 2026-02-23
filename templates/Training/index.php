<?php
/**
 * Infinity Training – index page.
 *
 * Three screens managed client-side:
 *   1. Category picker  (#mfTrainPicker)
 *   2. Question runner   (#mfTrainRunner)
 *   3. End / results     (#mfTrainEnd)
 *
 * @var \App\View\AppView $this
 * @var list<\App\Model\Entity\Category> $categories
 * @var string $lang
 * @var int|null $languageId
 */

use App\Model\Entity\Question;

$this->assign('title', __('Training'));
?>

<!-- ═══════════════  SCREEN 1 – Category Picker  ═══════════════ -->
<div id="mfTrainPicker" class="container py-4 py-lg-5">
    <div class="text-center mb-4">
        <h1 class="fw-bold text-white" style="font-size:clamp(1.6rem,4vw,2.4rem);">
            <i class="bi bi-infinity me-2"></i><?= __('Training Mode') ?>
        </h1>
        <p class="mf-muted" style="max-width:520px;margin:0.5rem auto;">
            <?= __('Pick a category and practice endlessly. Questions will keep coming until you decide to stop.') ?>
        </p>
    </div>

    <?php if (empty($categories)) : ?>
        <div class="text-center mf-muted py-5">
            <i class="bi bi-emoji-frown" style="font-size:2rem;"></i>
            <p class="mt-2"><?= __('No categories with questions available yet.') ?></p>
        </div>
    <?php else : ?>
        <div class="row g-3 justify-content-center" style="max-width:720px;margin:0 auto;">
            <?php foreach ($categories as $cat) :
                $catName = '';
                if (!empty($cat->category_translations)) {
                    $catName = (string)($cat->category_translations[0]->name ?? '');
                }
                if ($catName === '') {
                    $catName = __('Category') . ' #' . $cat->id;
                }
            ?>
                <div class="col-12 col-sm-6">
                    <button
                        type="button"
                        class="btn w-100 text-start mf-train-cat-btn p-3"
                        data-mf-cat-id="<?= h((string)$cat->id) ?>"
                        data-mf-cat-name="<?= h($catName) ?>"
                    >
                        <i class="bi bi-folder2-open me-2 text-primary"></i>
                        <span class="fw-semibold text-white"><?= h($catName) ?></span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="mfTrainSelectedWrap" class="text-center mt-4" style="display:none;">
            <div class="mf-muted mb-2">
                <?= __('Selected') ?>: <span id="mfTrainSelectedName" class="text-white fw-semibold"></span>
            </div>
            <button
                type="button"
                id="mfTrainStartBtn"
                class="btn btn-primary btn-lg px-5"
                disabled
            >
                <i class="bi bi-play-fill me-1"></i><?= __('Start Training') ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- ═══════════════  SCREEN 2 – Question Runner  ═══════════════ -->
<div id="mfTrainRunner" class="container py-3 py-lg-4" style="display:none;">
    <div class="mf-quiz-runner">
        <header class="mf-quiz-runner__header">
            <div class="mf-quiz-runner__titlewrap">
                <div class="mf-quiz-runner__kicker"><?= __('Training') ?></div>
                <h1 class="mf-quiz-runner__title" id="mfTrainCatTitle"></h1>
            </div>
            <div class="mf-quiz-runner__meta">
                <div class="mf-quiz-runner__step">
                    <span class="mf-muted"><?= __('Question') ?></span>
                    <span class="text-white" style="font-weight:800;">
                        #<span id="mfTrainQNum">1</span>
                    </span>
                </div>
                <!-- Linear progress bar (no total, just grows) -->
                <div class="mf-quiz-runner__progress" aria-hidden="true" style="flex:1;">
                    <div class="mf-quiz-runner__progress-fill" id="mfTrainBar" style="width:0%;transition:width .1s linear;"></div>
                </div>
                <div class="mf-quiz-runner__actions">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        id="mfTrainExitBtn"
                    >
                        <i class="bi bi-box-arrow-left me-1"></i><?= __('End training') ?>
                    </button>
                </div>
            </div>
        </header>

        <div class="mf-quiz-runner__body" id="mfTrainBody">
            <!-- Spinner while loading -->
            <div id="mfTrainSpinner" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mf-muted mt-2"><?= __('Loading questions') ?>…</div>
            </div>

            <!-- Question area -->
            <div id="mfTrainQuestion" style="display:none;">
                <div class="mf-quiz-question">
                    <div class="mf-quiz-question__text" id="mfTrainQText"></div>
                    <div id="mfTrainAnswers" class="mf-quiz-options"></div>
                </div>
            </div>

            <!-- Feedback after answering -->
            <div id="mfTrainFeedback" style="display:none;" class="text-center py-3">
                <div id="mfTrainFeedbackIcon" style="font-size:2.4rem;"></div>
                <div id="mfTrainFeedbackText" class="mt-2 fw-semibold" style="font-size:1.1rem;"></div>
            </div>
        </div>

        <div class="mf-quiz-runner__nav">
            <div></div>
            <div class="mf-quiz-runner__nav-right">
                <button type="button" class="btn btn-primary" id="mfTrainNextBtn" style="display:none;">
                    <?= __('Next') ?> <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════  SCREEN 3 – End Screen  ═══════════════ -->
<div id="mfTrainEnd" class="container py-4 py-lg-5" style="display:none;">
    <div class="py-3 py-lg-4">
        <article class="mf-quiz-card mf-quiz-details mf-result-hero" style="--mf-quiz-accent-rgb:var(--mf-primary-rgb);--mf-quiz-accent-a:0.16;">
            <div class="mf-quiz-card__cover">
                <div class="mf-quiz-card__cover-inner">
                    <div class="mf-quiz-card__icon" aria-hidden="true">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <div class="mf-quiz-card__cover-meta">
                        <div class="mf-quiz-card__category"><?= __('Training Complete') ?></div>
                        <div id="mfTrainEndCat" class="mf-muted"></div>
                    </div>
                    <div class="mf-quiz-card__rightmeta">
                        <span class="mf-result-score" id="mfTrainEndScore">—</span>
                    </div>
                </div>
            </div>

            <div class="mf-quiz-card__content">
                <div class="mf-quiz-details__stats">
                    <div class="mf-quiz-stat">
                        <i class="bi bi-question-circle" aria-hidden="true"></i>
                        <span class="mf-quiz-stat__value" id="mfTrainEndTotal">0</span>
                        <span class="mf-quiz-stat__label"><?= __('Questions') ?></span>
                    </div>
                    <div class="mf-quiz-stat">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <span class="mf-quiz-stat__value" id="mfTrainEndCorrect">0</span>
                        <span class="mf-quiz-stat__label"><?= __('Correct') ?></span>
                    </div>
                    <div class="mf-quiz-stat">
                        <i class="bi bi-percent" aria-hidden="true"></i>
                        <span class="mf-quiz-stat__value" id="mfTrainEndAccuracy">—</span>
                        <span class="mf-quiz-stat__label"><?= __('Accuracy') ?></span>
                    </div>
                </div>
            </div>

            <div class="mf-quiz-card__actions d-flex gap-2 flex-wrap justify-content-center">
                <button type="button" class="btn btn-primary" id="mfTrainAgainBtn">
                    <i class="bi bi-arrow-repeat me-1"></i><?= __('Train again') ?>
                </button>
                <?= $this->Html->link(
                    '<i class="bi bi-house me-1"></i>' . __('Back to quizzes'),
                    ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                    ['class' => 'btn btn-outline-light', 'escape' => false],
                ) ?>
            </div>
        </article>
    </div>
</div>

<!-- ═══════════════  Inline styles for category buttons  ═══════════════ -->
<style>
.mf-train-cat-btn {
    background: rgba(var(--mf-primary-rgb, 99,102,241), 0.08);
    border: 1px solid rgba(var(--mf-primary-rgb, 99,102,241), 0.18);
    border-radius: 0.75rem;
    transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
}
.mf-train-cat-btn:hover,
.mf-train-cat-btn:focus {
    background: rgba(var(--mf-primary-rgb, 99,102,241), 0.16);
    border-color: rgba(var(--mf-primary-rgb, 99,102,241), 0.40);
}
.mf-train-cat-btn.active {
    background: rgba(var(--mf-primary-rgb, 99,102,241), 0.22);
    border-color: rgb(var(--mf-primary-rgb, 99,102,241));
    box-shadow: 0 0 0 2px rgba(var(--mf-primary-rgb, 99,102,241), 0.30);
}

/* Feedback colours */
.mf-train-correct { color: #22c55e; }
.mf-train-wrong   { color: #ef4444; }

/* Selected answer highlight */
.mf-quiz-optionRow.mf-selected .mf-quiz-option {
    border-color: rgb(var(--mf-primary-rgb, 99,102,241));
    background: rgba(var(--mf-primary-rgb, 99,102,241), 0.12);
}
.mf-quiz-optionRow.mf-correct .mf-quiz-option {
    border-color: #22c55e !important;
    background: rgba(34,197,94,0.12) !important;
}
.mf-quiz-optionRow.mf-wrong .mf-quiz-option {
    border-color: #ef4444 !important;
    background: rgba(239,68,68,0.10) !important;
}
</style>

<?php $this->start('script'); ?>
<script>
(function () {
    'use strict';

    /* ────── Config ────── */
    const questionsUrl = <?= json_encode($this->Url->build(['controller' => 'Training', 'action' => 'questions', 'lang' => $lang])) ?>;

    /* ────── i18n labels ────── */
    const I18N = {
        correct:      <?= json_encode(__('Correct!')) ?>,
        wrong:        <?= json_encode(__('Wrong!')) ?>,
        noQuestions:  <?= json_encode(__('No questions found for this category.')) ?>,
        networkError: <?= json_encode(__('Failed to load questions. Please try again.')) ?>,
        endConfirm:   <?= json_encode(__('End this training session?')) ?>,
        yes:          <?= json_encode(__('Yes')) ?>,
        no:           <?= json_encode(__('No')) ?>,
    };

    /* ────── DOM refs ────── */
    const $picker     = document.getElementById('mfTrainPicker');
    const $runner     = document.getElementById('mfTrainRunner');
    const $end        = document.getElementById('mfTrainEnd');
    const $catBtns    = document.querySelectorAll('[data-mf-cat-id]');
    const $selWrap    = document.getElementById('mfTrainSelectedWrap');
    const $selName    = document.getElementById('mfTrainSelectedName');
    const $startBtn   = document.getElementById('mfTrainStartBtn');
    const $catTitle   = document.getElementById('mfTrainCatTitle');
    const $spinner    = document.getElementById('mfTrainSpinner');
    const $qWrap      = document.getElementById('mfTrainQuestion');
    const $qText      = document.getElementById('mfTrainQText');
    const $answers    = document.getElementById('mfTrainAnswers');
    const $qNum       = document.getElementById('mfTrainQNum');
    const $bar        = document.getElementById('mfTrainBar');
    const $feedback   = document.getElementById('mfTrainFeedback');
    const $fbIcon     = document.getElementById('mfTrainFeedbackIcon');
    const $fbText     = document.getElementById('mfTrainFeedbackText');
    const $nextBtn    = document.getElementById('mfTrainNextBtn');
    const $exitBtn    = document.getElementById('mfTrainExitBtn');
    const $endScore   = document.getElementById('mfTrainEndScore');
    const $endTotal   = document.getElementById('mfTrainEndTotal');
    const $endCorrect = document.getElementById('mfTrainEndCorrect');
    const $endAccuracy= document.getElementById('mfTrainEndAccuracy');
    const $endCat     = document.getElementById('mfTrainEndCat');
    const $againBtn   = document.getElementById('mfTrainAgainBtn');

    if (!$picker) return;

    /* ────── State ────── */
    let selectedCatId   = null;
    let selectedCatName = '';
    let questions       = [];
    let qIndex          = 0;
    let correct         = 0;
    let answered        = 0;
    let locked          = false; // prevent double-click while showing feedback
    let lastQuestionId  = null; // track to avoid repeats across loops

    /* ────── Wandering progress bar ────── */
    let barTarget   = 0;   // current target %
    let barCurrent  = 0;   // current rendered %
    let barRafId    = null;
    let barIntervalId = null;

    function pickNewBarTarget() {
        // random target between 10% and 90%
        barTarget = 10 + Math.random() * 80;
    }

    function animateBar() {
        const speed = 0.15; // slow drift
        const diff = barTarget - barCurrent;
        if (Math.abs(diff) < 0.3) {
            barCurrent = barTarget;
        } else {
            barCurrent += diff * speed;
        }
        $bar.style.width = barCurrent.toFixed(1) + '%';
        barRafId = requestAnimationFrame(animateBar);
    }

    function startBarWander() {
        stopBarWander();
        barCurrent = parseFloat($bar.style.width) || 0;
        pickNewBarTarget();
        barIntervalId = setInterval(pickNewBarTarget, 2500 + Math.random() * 3000);
        barRafId = requestAnimationFrame(animateBar);
    }

    function stopBarWander() {
        if (barRafId) { cancelAnimationFrame(barRafId); barRafId = null; }
        if (barIntervalId) { clearInterval(barIntervalId); barIntervalId = null; }
    }

    /* ────── Screen switch ────── */
    function show(screen) {
        $picker.style.display = screen === 'picker' ? '' : 'none';
        $runner.style.display = screen === 'runner' ? '' : 'none';
        $end.style.display    = screen === 'end'    ? '' : 'none';
    }

    /* ────── Category selection ────── */
    $catBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            $catBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            selectedCatId = this.dataset.mfCatId;
            selectedCatName = this.dataset.mfCatName;
            $selName.textContent = selectedCatName;
            $selWrap.style.display = '';
            $startBtn.disabled = false;
        });
    });

    /* ────── Start ────── */
    $startBtn?.addEventListener('click', async function () {
        if (!selectedCatId) return;
        show('runner');
        $catTitle.textContent = selectedCatName;
        $spinner.style.display = '';
        $qWrap.style.display = 'none';
        $feedback.style.display = 'none';
        $nextBtn.style.display = 'none';

        qIndex = 0;
        correct = 0;
        answered = 0;
        locked = false;
        lastQuestionId = null;

        try {
            const resp = await fetch(questionsUrl + '?category_id=' + encodeURIComponent(selectedCatId));
            const data = await resp.json();
            questions = data.questions || [];
        } catch {
            questions = [];
        }

        $spinner.style.display = 'none';

        if (!questions.length) {
            $qWrap.style.display = '';
            $qText.textContent = I18N.noQuestions;
            $answers.innerHTML = '';
            return;
        }

        shuffleQuestions();
        startBarWander();
        renderQuestion();
    });

    /* ────── Shuffle helper (Fisher–Yates) ensuring no back-to-back repeats ── */
    function shuffleQuestions() {
        for (let i = questions.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [questions[i], questions[j]] = [questions[j], questions[i]];
        }
        // If the first question after shuffle is the same as the last one shown, swap it
        if (lastQuestionId !== null && questions.length > 1 && questions[0].id === lastQuestionId) {
            const swapIdx = 1 + Math.floor(Math.random() * (questions.length - 1));
            [questions[0], questions[swapIdx]] = [questions[swapIdx], questions[0]];
        }
    }

    /* ────── Render current question ────── */
    function renderQuestion() {
        // All questions exhausted → reshuffle and loop (infinity!)
        if (qIndex >= questions.length) {
            shuffleQuestions();
            qIndex = 0;
        }

        locked = false;
        const q = questions[qIndex];
        lastQuestionId = q.id;
        $qNum.textContent = String(answered + 1);
        $qText.textContent = q.text || '—';
        $answers.innerHTML = '';
        $qWrap.style.display = '';
        $feedback.style.display = 'none';
        $nextBtn.style.display = 'none';

        // Bar is animated by the wandering system — no manual update needed

        if (q.type === 'true_false' || q.type === 'single_choice' || q.type === 'multiple_choice') {
            renderChoiceOptions(q);
        } else if (q.type === 'matching') {
            renderMatchingOptions(q);
        } else if (q.type === 'text') {
            renderTextOption(q);
        } else {
            renderChoiceOptions(q);
        }
    }

    /* ── Single / multiple choice + true/false ── */
    function renderChoiceOptions(q) {
        q.answers.forEach(a => {
            const row = document.createElement('div');
            row.className = 'mf-quiz-optionRow';
            row.dataset.aid = a.id;
            row.dataset.correct = a.is_correct ? '1' : '0';

            const label = document.createElement('label');
            label.className = 'mf-quiz-option';
            label.style.cursor = 'pointer';

            const txt = document.createElement('span');
            txt.className = 'mf-quiz-option__text';
            txt.textContent = a.text || '—';

            const afford = document.createElement('span');
            afford.className = 'mf-quiz-option__afford';
            afford.setAttribute('aria-hidden', 'true');

            label.append(txt, afford);
            row.append(label);

            row.addEventListener('click', () => handleChoiceClick(q, row));
            $answers.append(row);
        });
    }

    function handleChoiceClick(q, row) {
        if (locked) return;
        locked = true;

        const isCorrect = row.dataset.correct === '1';
        answered++;
        if (isCorrect) correct++;

        // Highlight selected
        row.classList.add('mf-selected');

        // Reveal correct / wrong
        $answers.querySelectorAll('.mf-quiz-optionRow').forEach(r => {
            if (r.dataset.correct === '1') {
                r.classList.add('mf-correct');
            } else if (r === row && !isCorrect) {
                r.classList.add('mf-wrong');
            }
            r.style.pointerEvents = 'none';
        });

        showFeedback(isCorrect);
    }

    /* ── Text answer (freeform) ── */
    function renderTextOption(q) {
        const wrap = document.createElement('div');
        wrap.className = 'mt-3';

        const ta = document.createElement('textarea');
        ta.className = 'form-control mf-quiz-textarea';
        ta.rows = 3;
        ta.placeholder = '…';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-primary mt-2';
        btn.textContent = <?= json_encode(__('Check')) ?>;

        btn.addEventListener('click', () => {
            if (locked) return;
            locked = true;
            answered++;

            // Simple text matching: compare trimmed lowercase
            const userAnswer = ta.value.trim().toLowerCase();
            const correctAnswers = q.answers.filter(a => a.is_correct).map(a => (a.text || '').trim().toLowerCase());
            const isCorrect = correctAnswers.some(c => c !== '' && userAnswer === c);
            if (isCorrect) correct++;

            ta.disabled = true;
            btn.disabled = true;

            // Show correct answer(s)
            const hint = document.createElement('div');
            hint.className = 'mt-2 ' + (isCorrect ? 'mf-train-correct' : 'mf-train-wrong');
            hint.textContent = (isCorrect ? '✓ ' : '✗ ') + correctAnswers.join(', ');
            wrap.append(hint);

            showFeedback(isCorrect);
        });

        wrap.append(ta, btn);
        $answers.append(wrap);
    }

    /* ── Matching ── */
    function renderMatchingOptions(q) {
        const leftItems = q.answers.filter(a => a.match_side === 'left');
        const rightItems = q.answers.filter(a => a.match_side === 'right');

        if (!leftItems.length || !rightItems.length) {
            const msg = document.createElement('div');
            msg.className = 'mf-muted';
            msg.textContent = <?= json_encode(__('Matching options not configured.')) ?>;
            $answers.append(msg);
            return;
        }

        // Shuffle right side
        const shuffledRight = [...rightItems].sort(() => Math.random() - 0.5);

        const wrap = document.createElement('div');
        wrap.className = 'row g-3 mt-1';

        const selects = [];

        leftItems.forEach(left => {
            const col = document.createElement('div');
            col.className = 'col-12';

            const flex = document.createElement('div');
            flex.className = 'd-flex align-items-center gap-2 flex-wrap';

            const label = document.createElement('div');
            label.className = 'text-white fw-semibold mb-1 mb-sm-0';
            label.style.minWidth = '140px';
            label.textContent = left.text || '—';

            const arrow = document.createElement('div');
            arrow.className = 'mf-muted';
            arrow.innerHTML = '&rarr;';

            const sel = document.createElement('select');
            sel.className = 'form-select';
            sel.style.maxWidth = '100%';
            sel.style.flex = '1 1 200px';
            sel.dataset.leftGroup = left.match_group;

            const def = document.createElement('option');
            def.value = '';
            def.textContent = <?= json_encode(__('Choose a match')) ?>;
            sel.append(def);

            shuffledRight.forEach(right => {
                const opt = document.createElement('option');
                opt.value = right.match_group;
                opt.textContent = right.text || '—';
                sel.append(opt);
            });

            selects.push(sel);
            flex.append(label, arrow, sel);
            col.append(flex);
            wrap.append(col);
        });

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-primary mt-3';
        btn.textContent = <?= json_encode(__('Check')) ?>;

        btn.addEventListener('click', () => {
            if (locked) return;
            locked = true;
            answered++;

            let allCorrect = true;
            selects.forEach(sel => {
                const expected = String(sel.dataset.leftGroup);
                const chosen   = sel.value;
                if (chosen === expected) {
                    sel.classList.add('is-valid');
                } else {
                    sel.classList.add('is-invalid');
                    allCorrect = false;
                }
                sel.disabled = true;
            });

            if (allCorrect) correct++;
            btn.disabled = true;
            showFeedback(allCorrect);
        });

        $answers.append(wrap, btn);
    }

    /* ────── Feedback + next ────── */
    function showFeedback(isCorrect) {
        $feedback.style.display = '';
        $fbIcon.innerHTML = isCorrect
            ? '<i class="bi bi-check-circle-fill mf-train-correct"></i>'
            : '<i class="bi bi-x-circle-fill mf-train-wrong"></i>';
        $fbText.textContent = isCorrect ? I18N.correct : I18N.wrong;
        $fbText.className = 'mt-2 fw-semibold ' + (isCorrect ? 'mf-train-correct' : 'mf-train-wrong');
        $nextBtn.style.display = '';
    }

    /* ────── Next question ────── */
    $nextBtn?.addEventListener('click', () => {
        qIndex++;
        renderQuestion();
    });

    /* ────── Exit / end ────── */
    function showEnd() {
        const total = answered;
        const pct = total > 0 ? Math.round((correct / total) * 100) : 0;

        $endCat.textContent = selectedCatName;
        $endTotal.textContent = String(total);
        $endCorrect.textContent = String(correct);
        $endAccuracy.textContent = pct + '%';
        $endScore.textContent = pct + '%';

        // Tone
        $endScore.className = 'mf-result-score';
        if (pct >= 80) $endScore.classList.add('mf-result-score--good');
        else if (pct < 50) $endScore.classList.add('mf-result-score--low');
        else $endScore.classList.add('mf-result-score--mid');

        stopBarWander();
        $bar.style.width = '100%';
        show('end');
    }

    $exitBtn?.addEventListener('click', () => {
        if (window.Swal) {
            Swal.fire({
                title: I18N.endConfirm,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: I18N.yes,
                cancelButtonText: I18N.no,
                buttonsStyling: false,
                customClass: {
                    popup: 'mf-swal2-popup',
                    title: 'mf-swal2-title',
                    actions: 'mf-swal2-actions',
                    confirmButton: 'btn btn-primary mf-swal2-confirm',
                    cancelButton: 'btn btn-outline-light mf-swal2-cancel',
                },
            }).then(result => {
                if (result.isConfirmed) showEnd();
            });
        } else if (confirm(I18N.endConfirm)) {
            showEnd();
        }
    });

    /* ────── Train again ────── */
    $againBtn?.addEventListener('click', () => {
        stopBarWander();
        $bar.style.width = '0%';
        show('picker');
    });

}());
</script>
<?php $this->end(); ?>
