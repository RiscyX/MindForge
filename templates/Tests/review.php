<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttempt $attempt
 * @var \App\Model\Entity\Test|null $test
 * @var \Cake\Datasource\ResultSetInterface|iterable<\App\Model\Entity\Question> $questions
 * @var array<int, \App\Model\Entity\TestAttemptAnswer> $attemptAnswers
 * @var array<int, \App\Model\Entity\AttemptAnswerExplanation> $explanationsByAttemptAnswer
 * @var string $csrfToken
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

<div class="container py-3 py-lg-4" data-mf-csrf-token="<?= h($csrfToken) ?>">
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
            $attemptAnswerId = (int)($attemptAnswer->id ?? 0);
            $existingExplanation = $attemptAnswerId > 0 && isset($explanationsByAttemptAnswer[$attemptAnswerId])
                ? trim((string)($explanationsByAttemptAnswer[$attemptAnswerId]->explanation_text ?? ''))
                : '';

            $qText = '';
            if (!empty($question->question_translations)) {
                $qText = (string)($question->question_translations[0]->content ?? '');
            }

            $chosenAnswerId = $attemptAnswer?->answer_id !== null ? (int)$attemptAnswer->answer_id : null;
            $userText = $attemptAnswer?->user_answer_text !== null ? trim((string)$attemptAnswer->user_answer_text) : '';
            $userPayload = $attemptAnswer?->user_answer_payload !== null ? (string)$attemptAnswer->user_answer_payload : '';
            $userPairs = [];
            if ($userPayload !== '') {
                $decodedPayload = json_decode($userPayload, true);
                if (is_array($decodedPayload) && isset($decodedPayload['pairs']) && is_array($decodedPayload['pairs'])) {
                    $userPairs = $decodedPayload['pairs'];
                }
            }

            $answerText = static function ($answer): string {
                $txt = '';
                if (!empty($answer->answer_translations)) {
                    $txt = (string)($answer->answer_translations[0]->content ?? '');
                }
                if ($txt === '' && isset($answer->source_text)) {
                    $txt = (string)$answer->source_text;
                }

                return trim($txt);
            };

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
                    <?php elseif ((string)$question->question_type === Question::TYPE_MATCHING) : ?>
                        <?php
                        $leftById = [];
                        $rightById = [];
                        foreach (array_values((array)($question->answers ?? [])) as $idx => $ans) {
                            $side = trim((string)($ans->match_side ?? ''));
                            if ($side === '') {
                                $side = ($idx % 2 === 0) ? 'left' : 'right';
                            }
                            $aid = (int)$ans->id;
                            if ($side === 'left') {
                                $leftById[$aid] = $ans;
                            } elseif ($side === 'right') {
                                $rightById[$aid] = $ans;
                            }
                        }
                        ?>
                        <div class="mf-muted mb-2"><?= __('Matching pairs') ?></div>

                        <?php foreach ($leftById as $leftId => $leftAnswer) : ?>
                            <?php
                            $chosenRightRaw = $userPairs[(string)$leftId] ?? ($userPairs[$leftId] ?? null);
                            $chosenRightId = is_numeric($chosenRightRaw) ? (int)$chosenRightRaw : null;
                            $chosenRight = $chosenRightId !== null && isset($rightById[$chosenRightId]) ? $rightById[$chosenRightId] : null;

                            $leftGroup = (int)($leftAnswer->match_group ?? 0);
                            $isPairCorrect = $chosenRight !== null && $leftGroup > 0 && $leftGroup === (int)($chosenRight->match_group ?? 0);
                            ?>
                            <div class="mf-answer-choice <?= $isPairCorrect ? 'mf-answer-choice--correct' : 'mf-answer-choice--wrong' ?>">
                                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                                    <div class="text-white">
                                        <strong><?= h($answerText($leftAnswer) !== '' ? $answerText($leftAnswer) : __('(empty)')) ?></strong>
                                        <span class="mf-muted"> &rarr; </span>
                                        <?= h($chosenRight ? ($answerText($chosenRight) !== '' ? $answerText($chosenRight) : __('(empty)')) : __('(no match)')) ?>
                                    </div>
                                    <div>
                                        <?php if ($isPairCorrect) : ?>
                                            <span class="badge bg-success"><?= __('Correct') ?></span>
                                        <?php else : ?>
                                            <span class="badge bg-danger"><?= __('Wrong') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

                    <?php if ($attemptAnswerId > 0) : ?>
                        <?php $explanationTargetId = 'mf-ai-explanation-' . $qid; ?>
                        <div class="mt-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-info"
                                    data-mf-ai-explain
                                    data-loading-label="<?= h(__('Generating...')) ?>"
                                    data-url="<?= h($this->Url->build(['controller' => 'Tests', 'action' => 'explainAnswer', $attempt->id, $qid, 'lang' => $lang])) ?>"
                                    data-target="#<?= h($explanationTargetId) ?>"
                                >
                                    <?= __('Explain with AI') ?>
                                </button>
                                <span class="mf-muted small"><?= __('Useful when you want to understand why your answer was wrong/right.') ?></span>
                            </div>
                            <div id="<?= h($explanationTargetId) ?>" class="mf-answer-box"<?= $existingExplanation === '' ? ' hidden' : '' ?>>
                                <?= $existingExplanation !== '' ? nl2br(h($existingExplanation)) : '' ?>
                            </div>
                        </div>
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
<?= $this->Html->script('tests_review') ?>
<?php $this->end(); ?>
