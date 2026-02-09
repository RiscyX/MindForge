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
                    <?= __('Attempt') ?> #<?= h((string)$attempt->id) ?>
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
                    <?php else : ?>
                    <div class="mf-quiz-options">
                        <?php foreach (($question->answers ?? []) as $answer) : ?>
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

  render();
})();
</script>
<?php $this->end(); ?>
