<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttempt $attempt
 * @var \App\Model\Entity\Test|null $test
 * @var \Cake\Datasource\ResultSetInterface|iterable<\App\Model\Entity\Question> $questions
 */

use App\Model\Entity\Question;

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Take quiz'));

$testTitle = '';
if (!empty($test?->test_translations)) {
    $testTitle = (string)($test->test_translations[0]->title ?? '');
}

$questionsList = is_array($questions) ? $questions : iterator_to_array($questions);
$totalQuestions = count($questionsList);
?>

<div class="container py-3 py-lg-4">
    <div class="mf-quiz-runner">
        <header class="mf-quiz-runner__header">
            <div class="mf-quiz-runner__titlewrap">
                <div class="mf-quiz-runner__kicker">
                    <?= __('Attempt') ?>
                </div>
                <h1 class="mf-quiz-runner__title">
                    <?= $testTitle !== '' ? h($testTitle) : __('Quiz') ?>
                </h1>
            </div>

            <div class="mf-quiz-runner__meta">
                <div class="mf-quiz-runner__step">
                    <span class="mf-muted"><?= __('Question') ?></span>
                    <span class="text-white" style="font-weight: 800;"> <span data-mf-runner-current>1</span>/<span data-mf-runner-total><?= h((string)max(1, $totalQuestions)) ?></span></span>
                </div>
                <div class="mf-quiz-runner__progress" aria-hidden="true">
                    <div class="mf-quiz-runner__progress-fill" data-mf-runner-progress></div>
                </div>
                <div class="mf-quiz-runner__actions">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        id="mf-abort-attempt-trigger"
                        data-mf-title="<?= h(__('Abort attempt')) ?>"
                        data-mf-text="<?= h(__('Are you sure you want to abort this attempt? Your current progress will be lost.')) ?>"
                        data-mf-confirm="<?= h(__('Abort attempt')) ?>"
                        data-mf-cancel="<?= h(__('Cancel')) ?>"
                    >
                        <?= __('Abort attempt') ?>
                    </button>
                    <?= $this->Form->create(null, [
                        'url' => ['controller' => 'Tests', 'action' => 'abort', (string)$attempt->id, 'lang' => $lang],
                        'id' => 'mf-abort-attempt-form',
                        'style' => 'display:none;',
                    ]) ?>
                    <?= $this->Form->end() ?>
                    <?= $this->Html->link(
                        __('Back to quizzes'),
                        ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                        ['class' => 'btn btn-sm btn-outline-light'],
                    ) ?>
                </div>
            </div>
        </header>

        <div class="mf-quiz-runner__body">
        <?= $this->Form->create(null, [
            'url' => ['action' => 'submit', $attempt->id, 'lang' => $lang],
        ]) ?>

        <?php
        $i = 0;
        foreach ($questionsList as $question) :
            $i += 1;
            $qid = (int)$question->id;
            $qText = '';
            if (!empty($question->question_translations)) {
                $qText = (string)($question->question_translations[0]->content ?? '');
            }

            $answerText = static function ($answer): string {
                $txt = '';
                if (!empty($answer->answer_translations)) {
                    $txt = (string)($answer->answer_translations[0]->content ?? '');
                }
                if ($txt === '' && isset($answer->source_text)) {
                    $txt = (string)$answer->source_text;
                }

                return $txt;
            };
        ?>
            <section class="mf-quiz-step" data-mf-step="<?= h((string)($i - 1)) ?>" <?= $i === 1 ? '' : 'hidden' ?> >
                <div class="mf-quiz-question">
                    <div class="mf-quiz-question__text">
                    <?= $qText !== '' ? h($qText) : __('Untitled question') ?>
                    </div>

                    <?php if ((string)$question->question_type === Question::TYPE_TEXT) : ?>
                    <textarea
                        class="form-control mf-quiz-textarea"
                        name="answers[<?= h((string)$qid) ?>]"
                        rows="3"
                        placeholder="<?= h(__('Type your answer…')) ?>"
                    ></textarea>
                    <?php elseif ((string)$question->question_type === Question::TYPE_MATCHING) : ?>
                    <?php
                    $leftAnswers = [];
                    $rightAnswers = [];
                    $allAnswers = array_values((array)($question->answers ?? []));
                    foreach ($allAnswers as $idx => $answer) {
                        $side = trim((string)($answer->match_side ?? ''));
                        if ($side === '') {
                            $side = ($idx % 2 === 0) ? 'left' : 'right';
                        }
                        if ($side === 'left') {
                            $leftAnswers[] = $answer;
                        } elseif ($side === 'right') {
                            $rightAnswers[] = $answer;
                        }
                    }

                    usort($rightAnswers, static function ($a, $b) use ($attempt, $qid): int {
                        $attemptId = (int)($attempt->id ?? 0);
                        $aId = (int)($a->id ?? 0);
                        $bId = (int)($b->id ?? 0);
                        $aKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $qid . ':right:' . $aId);
                        $bKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $qid . ':right:' . $bId);

                        if ($aKey === $bKey) {
                            return $aId <=> $bId;
                        }

                        return $aKey <=> $bKey;
                    });
                    usort($leftAnswers, static function ($a, $b) use ($attempt, $qid): int {
                        $attemptId = (int)($attempt->id ?? 0);
                        $aId = (int)($a->id ?? 0);
                        $bId = (int)($b->id ?? 0);
                        $aKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $qid . ':left:' . $aId);
                        $bKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $qid . ':left:' . $bId);

                        if ($aKey === $bKey) {
                            return $aId <=> $bId;
                        }

                        return $aKey <=> $bKey;
                    });
                    ?>
                    <?php if (!$leftAnswers || !$rightAnswers) : ?>
                        <div class="mf-muted"><?= __('Matching options are not configured for this question.') ?></div>
                    <?php else : ?>
                        <div class="row g-3 mt-1">
                            <?php foreach ($leftAnswers as $left) : ?>
                                <?php $leftId = (int)$left->id; ?>
                                <div class="col-12">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <div class="text-white" style="min-width: 220px; font-weight: 600;">
                                            <?= h($answerText($left) !== '' ? $answerText($left) : __('(empty)')) ?>
                                        </div>
                                        <div class="mf-muted">&rarr;</div>
                                        <select
                                            class="form-select"
                                            style="max-width: 360px;"
                                            name="answers[<?= h((string)$qid) ?>][pairs][<?= h((string)$leftId) ?>]"
                                        >
                                            <option value=""><?= h(__('Choose a match')) ?></option>
                                            <?php foreach ($rightAnswers as $right) : ?>
                                                <?php $rightId = (int)$right->id; ?>
                                                <option value="<?= h((string)$rightId) ?>">
                                                    <?= h($answerText($right) !== '' ? $answerText($right) : __('(empty)')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php else : ?>
                    <?php
                    $optionAnswers = array_values((array)($question->answers ?? []));
                    usort($optionAnswers, static function ($a, $b) use ($attempt, $qid): int {
                        $attemptId = (int)($attempt->id ?? 0);
                        $aId = (int)($a->id ?? 0);
                        $bId = (int)($b->id ?? 0);
                        $aKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $qid . ':option:' . $aId);
                        $bKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $qid . ':option:' . $bId);

                        if ($aKey === $bKey) {
                            return $aId <=> $bId;
                        }

                        return $aKey <=> $bKey;
                    });
                    ?>
                    <div class="mf-quiz-options">
                        <?php foreach ($optionAnswers as $answer) : ?>
                        <?php
                        $aid = (int)$answer->id;
                        $aText = '';
                        if (!empty($answer->answer_translations)) {
                            $aText = (string)($answer->answer_translations[0]->content ?? '');
                        }
                        if ($aText === '' && isset($answer->source_text)) {
                            $aText = (string)$answer->source_text;
                        }
                        $inputId = 'q' . $qid . '_a' . $aid;
                        ?>
                        <div class="mf-quiz-optionRow">
                            <input
                                class="mf-quiz-option__input"
                                type="radio"
                                name="answers[<?= h((string)$qid) ?>]"
                                id="<?= h($inputId) ?>"
                                value="<?= h((string)$aid) ?>"
                            />
                            <label class="mf-quiz-option" for="<?= h($inputId) ?>">
                                <span class="mf-quiz-option__text">
                                    <?= $aText !== '' ? h($aText) : __('(empty)') ?>
                                </span>
                                <span class="mf-quiz-option__afford" aria-hidden="true"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="mf-quiz-runner__nav">
            <button type="button" class="btn btn-outline-light" data-mf-prev disabled>
                <?= __('Back') ?>
            </button>

            <div class="mf-quiz-runner__nav-right">
                <button type="button" class="btn btn-primary" data-mf-next>
                    <?= __('Next') ?>
                </button>
                <button type="submit" class="btn btn-primary" data-mf-submit hidden>
                    <?= __('Submit answers') ?>
                </button>
            </div>
        </div>

        <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?php $this->start('script'); ?>
<script>
(() => {
  const steps = Array.from(document.querySelectorAll('[data-mf-step]'));
  const prevBtn = document.querySelector('[data-mf-prev]');
  const nextBtn = document.querySelector('[data-mf-next]');
  const submitBtn = document.querySelector('[data-mf-submit]');
  const currentEl = document.querySelector('[data-mf-runner-current]');
  const totalEl = document.querySelector('[data-mf-runner-total]');
  const barFill = document.querySelector('[data-mf-runner-progress]');
  const abortTrigger = document.getElementById('mf-abort-attempt-trigger');
  const abortForm = document.getElementById('mf-abort-attempt-form');

  if (!steps.length || !prevBtn || !nextBtn || !submitBtn) return;

  let idx = 0;

  const render = () => {
    steps.forEach((el, i) => {
      el.hidden = i !== idx;
    });

    prevBtn.disabled = idx === 0;

    const isLast = idx === steps.length - 1;
    nextBtn.hidden = isLast;
    submitBtn.hidden = !isLast;

    if (currentEl) currentEl.textContent = String(idx + 1);
    if (totalEl) totalEl.textContent = String(steps.length);
    if (barFill) {
      const pct = steps.length ? Math.round(((idx + 1) / steps.length) * 100) : 0;
      barFill.style.width = pct + '%';
    }

    // Scroll step into view when navigating.
    steps[idx].scrollIntoView({ block: 'start', behavior: 'smooth' });
  };

  prevBtn.addEventListener('click', () => {
    if (idx > 0) {
      idx -= 1;
      render();
    }
  });

  nextBtn.addEventListener('click', () => {
    if (idx < steps.length - 1) {
      idx += 1;
      render();
    }
  });

  if (abortTrigger && abortForm && window.Swal) {
    abortTrigger.addEventListener('click', () => {
      Swal.fire({
        title: abortTrigger.dataset.mfTitle || 'Abort attempt',
        text: abortTrigger.dataset.mfText || 'Are you sure you want to abort this attempt?',
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonText: `<i class="bi bi-x-octagon"></i><span>${abortTrigger.dataset.mfConfirm || 'Abort attempt'}</span>`,
        cancelButtonText: `<i class="bi bi-arrow-left"></i><span>${abortTrigger.dataset.mfCancel || 'Cancel'}</span>`,
        buttonsStyling: false,
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
      }).then((result) => {
        if (result.isConfirmed) {
          abortForm.submit();
        }
      });
    });
  }

  render();
})();
</script>
<?php $this->end(); ?>
