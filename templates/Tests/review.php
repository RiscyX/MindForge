<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttempt $attempt
 * @var \App\Model\Entity\Test|null $test
 * @var \Cake\Datasource\ResultSetInterface|iterable<\App\Model\Entity\Question> $questions
 * @var array<int, \App\Model\Entity\TestAttemptAnswer> $attemptAnswers
 */

use App\Model\Entity\Question;

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Review'));

$testTitle = '';
if (!empty($test?->test_translations)) {
    $testTitle = (string)($test->test_translations[0]->title ?? '');
}

$total = (int)($attempt->total_questions ?? 0);
$correct = (int)($attempt->correct_answers ?? 0);
$score = $attempt->score !== null ? (string)$attempt->score : null;

$questionsList = is_array($questions) ? $questions : iterator_to_array($questions);
$totalQuestions = count($questionsList);
?>

<div class="container py-3 py-lg-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <h1 class="h3 mb-1 text-white">
                <?= __('Review') ?><?= $testTitle !== '' ? ': ' . h($testTitle) : '' ?>
            </h1>
            <div class="mf-muted">
                <?= __('Attempt') ?> #<?= h((string)$attempt->id) ?>
                <?php if ($attempt->finished_at) : ?>
                    · <?= h($attempt->finished_at->i18nFormat('yyyy-MM-dd HH:mm')) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Html->link(
                __('Back to quizzes'),
                ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-sm btn-outline-light'],
            ) ?>
            <?php if ($attempt->test_id !== null) : ?>
                <?= $this->Form->postLink(
                    __('Try again'),
                    ['controller' => 'Tests', 'action' => 'start', $attempt->test_id, 'lang' => $lang],
                    ['class' => 'btn btn-sm btn-primary'],
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="mf-admin-card p-3 mt-3">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="mf-muted mb-1"><?= __('Correct answers') ?></div>
                <div class="text-white" style="font-size: 1.5rem; font-weight: 700;">
                    <?= h((string)$correct) ?> / <?= h((string)$total) ?>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="mf-muted mb-1"><?= __('Score') ?></div>
                <div class="text-white" style="font-size: 1.5rem; font-weight: 700;">
                    <?= $score !== null ? h($score) . '%' : '—' ?>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="mf-muted mb-1"><?= __('Questions') ?></div>
                <div class="text-white" style="font-size: 1.5rem; font-weight: 700;">
                    <?= h((string)$totalQuestions) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mf-admin-card p-3 mt-3">
        <?php
        $i = 0;
        foreach ($questionsList as $question) :
            $i += 1;
            $qid = (int)$question->id;
            $attemptAnswer = $attemptAnswers[$qid] ?? null;
            $isCorrect = $attemptAnswer ? (bool)$attemptAnswer->is_correct : false;

            $qText = '';
            if (!empty($question->question_translations)) {
                $qText = (string)($question->question_translations[0]->content ?? '');
            }

            $chosenAnswerId = $attemptAnswer?->answer_id !== null ? (int)$attemptAnswer->answer_id : null;
            $userText = $attemptAnswer?->user_answer_text !== null ? trim((string)$attemptAnswer->user_answer_text) : '';

            $correctAnswerTexts = [];
            foreach (($question->answers ?? []) as $ans) {
                if (!$ans->is_correct) {
                    continue;
                }
                $t = '';
                if (!empty($ans->answer_translations)) {
                    $t = (string)($ans->answer_translations[0]->content ?? '');
                }
                if ($t === '' && isset($ans->source_text)) {
                    $t = (string)$ans->source_text;
                }
                $t = trim($t);
                if ($t !== '') {
                    $correctAnswerTexts[] = $t;
                }
            }
        ?>
            <section class="mf-quiz-step" data-mf-step="<?= h((string)($i - 1)) ?>" <?= $i === 1 ? '' : 'hidden' ?> >
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="mf-muted">
                            <?= __('Question') ?> <?= $i ?>/<?= (string)$totalQuestions ?>
                        </div>
                        <div class="h5 text-white mt-1 mb-0">
                            <?= $qText !== '' ? h($qText) : __('Untitled question') ?>
                        </div>
                    </div>
                    <div>
                        <?php if ($isCorrect) : ?>
                            <span class="badge bg-success"><?= __('Correct') ?></span>
                        <?php else : ?>
                            <span class="badge bg-danger"><?= __('Wrong') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-3">
                    <?php if ((string)$question->question_type === Question::TYPE_TEXT) : ?>
                        <div class="mf-muted mb-1"><?= __('Your answer') ?></div>
                        <div class="mf-answer-box <?= $isCorrect ? 'mf-answer-box--correct' : 'mf-answer-box--wrong' ?>">
                            <?= $userText !== '' ? h($userText) : __('(no answer)') ?>
                        </div>

                        <div class="mf-muted mt-3 mb-1"><?= __('Correct answer') ?></div>
                        <div class="mf-answer-box mf-answer-box--correct">
                            <?= $correctAnswerTexts ? h(implode(' / ', $correctAnswerTexts)) : __('(not set)') ?>
                        </div>
                    <?php else : ?>
                        <div class="mf-muted mb-2"><?= __('Answers') ?></div>

                        <?php foreach (($question->answers ?? []) as $ans) : ?>
                            <?php
                            $aid = (int)$ans->id;
                            $aText = '';
                            if (!empty($ans->answer_translations)) {
                                $aText = (string)($ans->answer_translations[0]->content ?? '');
                            }
                            if ($aText === '' && isset($ans->source_text)) {
                                $aText = (string)$ans->source_text;
                            }
                            $aText = trim($aText);

                            $isChosen = ($chosenAnswerId !== null && $chosenAnswerId === $aid);
                            $isAnswerCorrect = (bool)$ans->is_correct;

                            $classes = ['mf-answer-choice'];
                            if ($isAnswerCorrect) {
                                $classes[] = 'mf-answer-choice--correct';
                            }
                            if ($isChosen && !$isAnswerCorrect) {
                                $classes[] = 'mf-answer-choice--wrong';
                            }
                            if ($isChosen && $isAnswerCorrect) {
                                $classes[] = 'mf-answer-choice--chosen';
                            }
                            ?>
                            <div class="<?= h(implode(' ', $classes)) ?>">
                                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                                    <div class="text-white">
                                        <?= $aText !== '' ? h($aText) : __('(empty)') ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($isChosen) : ?>
                                            <span class="badge text-bg-light"><?= __('You') ?></span>
                                        <?php endif; ?>
                                        <?php if ($isAnswerCorrect) : ?>
                                            <span class="badge bg-success"><?= __('Correct') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($chosenAnswerId === null) : ?>
                            <div class="mf-muted mt-2"><?= __('You did not answer this question.') ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mt-2">
            <button type="button" class="btn btn-outline-light" data-mf-prev disabled>
                <?= __('Back') ?>
            </button>
            <button type="button" class="btn btn-primary" data-mf-next <?= $totalQuestions <= 1 ? 'disabled' : '' ?> >
                <?= __('Next') ?>
            </button>
        </div>
    </div>
</div>

<?php $this->start('script'); ?>
<script>
(() => {
  const steps = Array.from(document.querySelectorAll('[data-mf-step]'));
  const prevBtn = document.querySelector('[data-mf-prev]');
  const nextBtn = document.querySelector('[data-mf-next]');
  if (!steps.length || !prevBtn || !nextBtn) return;

  let idx = 0;
  const render = () => {
    steps.forEach((el, i) => { el.hidden = i !== idx; });
    prevBtn.disabled = idx === 0;
    nextBtn.disabled = idx === steps.length - 1;
    steps[idx].scrollIntoView({ block: 'start', behavior: 'smooth' });
  };

  prevBtn.addEventListener('click', () => { if (idx > 0) { idx -= 1; render(); } });
  nextBtn.addEventListener('click', () => { if (idx < steps.length - 1) { idx += 1; render(); } });
  render();
})();
</script>
<?php $this->end(); ?>
