<?php
/**
 * Home / Landing page
 *
 * @var \App\View\AppView $this
 */

use App\Model\Entity\Role;

$lang = $this->request->getParam('lang', 'en');
$identity = $this->request->getAttribute('identity');

$this->assign('title', __('MindForge'));
$this->assign('mfFullBleed', '1');

$this->Html->css('landing.css?v=2', ['block' => 'css']);
$this->Html->script('landing.js?v=2', ['block' => 'scriptBottom']);

$loginUrl = ['prefix' => false, 'controller' => 'Users', 'action' => 'login', 'lang' => $lang];
$registerUrl = ['prefix' => false, 'controller' => 'Users', 'action' => 'register', 'lang' => $lang];

$dashboardUrl = ['prefix' => false, 'controller' => 'Users', 'action' => 'profile', 'lang' => $lang];
if ($identity) {
    $roleId = (int)$identity->get('role_id');
    if ($roleId === Role::ADMIN) {
        $dashboardUrl = ['prefix' => false, 'controller' => 'Pages', 'action' => 'redirectToAdmin', '?' => ['lang' => $lang]];
    } elseif ($roleId === Role::CREATOR) {
        $dashboardUrl = ['prefix' => false, 'controller' => 'Pages', 'action' => 'redirectToQuizCreator', '?' => ['lang' => $lang]];
    }
}
?>

<section class="mf-landing" data-mf-landing>
    <div class="mf-landing__bg" data-mf-landing-bg></div>
    <div class="mf-landing__grid" aria-hidden="true"></div>
    <div class="mf-landing__grain" aria-hidden="true"></div>

    <div class="mf-landing__stage">
        <div class="mf-landing__hero">
            <div class="mf-landing__hero-copy">
                <div class="mf-reveal is-in">
                    <div class="mf-landing__kicker">
                        <span class="mf-landing__kicker-dot" aria-hidden="true"></span>
                        <span><?= __('AI-powered quiz engine') ?></span>
                        <span class="mf-landing-card__kbd">beta</span>
                    </div>

                    <h1 class="mf-landing__headline">
                        <?= $this->Html->tag(
                            'span',
                            __('Forge your learning into {0}.', ['<em>' . __('mastery') . '</em>']),
                            ['escape' => false],
                        ) ?>
                    </h1>

                    <p class="mf-landing__sub">
                        <?= __('MindForge turns studying into a fast loop: build quizzes, test understanding, then sharpen weak spots with structured practice.') ?>
                    </p>
                </div>

                <div class="mf-landing__cta mf-reveal" style="transition-delay: 90ms;">
                    <?php if (!$identity) : ?>
                        <a href="<?= $this->Url->build($loginUrl) ?>" class="btn btn-primary mf-landing__btn mf-landing__btn--primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i><?= __('Log In') ?>
                        </a>
                        <a href="<?= $this->Url->build($registerUrl) ?>" class="btn btn-outline-light mf-landing__btn mf-landing__btn--ghost">
                            <i class="bi bi-person-plus me-2"></i><?= __('Create Account') ?>
                        </a>
                    <?php else : ?>
                        <a href="<?= $this->Url->build($dashboardUrl) ?>" class="btn btn-primary mf-landing__btn mf-landing__btn--primary">
                            <i class="bi bi-speedometer2 me-2"></i><?= __('Continue') ?>
                        </a>
                        <span class="mf-landing__hint"><?= __('You are signed in.') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mf-landing__hero-visual mf-reveal" style="transition-delay: 120ms;" aria-hidden="true">
                <div class="mf-forge">
                    <div class="mf-forge__ring"></div>
                    <div class="mf-forge__ring mf-forge__ring--inner"></div>
                    <div class="mf-forge__spark mf-forge__spark--a"></div>
                    <div class="mf-forge__spark mf-forge__spark--b"></div>
                    <div class="mf-forge__spark mf-forge__spark--c"></div>

                    <div class="mf-float-stack">
                        <div class="mf-float-card mf-float-card--top">
                            <div class="mf-float-card__hdr">
                                <span class="mf-float-pill"><?= __('Live preview') ?></span>
                                <span class="mf-float-chip"><i class="bi bi-translate"></i> <?= __('Translations') ?></span>
                            </div>
                            <div class="mf-float-card__q">
                                <div class="mf-float-card__q-label"><?= __('Question') ?></div>
                                <div
                                    class="mf-float-card__q-text"
                                    data-mf-rotate-word
                                    data-mf-rotate-words='<?= h((string)json_encode([
                                        __('What drives long-term memory?'),
                                        __('Why does spaced repetition work?'),
                                        __('Which option best fits the definition?'),
                                        __('What is the key idea in one sentence?'),
                                    ])) ?>'
                                >
                                    <?= __('What drives long-term memory?') ?>
                                </div>
                            </div>
                            <div class="mf-float-opts">
                                <div class="mf-float-opt"><span class="mf-dot"></span><?= __('Spaced repetition') ?></div>
                                <div class="mf-float-opt"><span class="mf-dot"></span><?= __('Random guessing') ?></div>
                                <div class="mf-float-opt"><span class="mf-dot"></span><?= __('Passive reading') ?></div>
                            </div>
                        </div>

                        <div class="mf-float-card mf-float-card--bottom">
                            <div class="mf-float-card__hdr">
                                <span class="mf-float-chip"><i class="bi bi-robot"></i> <?= __('AI assistance') ?></span>
                                <span class="mf-float-chip"><i class="bi bi-shield-check"></i> <?= __('Built for safety') ?></span>
                            </div>
                            <div class="mf-float-card__meta">
                                <?= __('Generate, translate, then review. Always structured. Always yours.') ?>
                            </div>
                            <div class="mf-mini-bars" aria-hidden="true">
                                <div></div><div></div><div></div><div></div><div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mf-landing__features">
            <div class="mf-feature mf-reveal" style="transition-delay: 180ms;">
                <div class="mf-feature__icon"><i class="bi bi-robot"></i></div>
                <div class="mf-feature__label"><?= __('AI assistance') ?></div>
                <p class="mf-feature__text"><?= __('Generate drafts, translate content, and iterate quickly without breaking structure.') ?></p>
            </div>

            <div class="mf-feature mf-reveal" style="transition-delay: 220ms;">
                <div class="mf-feature__icon"><i class="bi bi-phone"></i></div>
                <div class="mf-feature__label"><?= __('Mobile-ready auth') ?></div>
                <p class="mf-feature__text"><?= __('Access + refresh tokens, rotation, revoke, and device-aware logging.') ?></p>
            </div>

            <div class="mf-feature mf-reveal" style="transition-delay: 260ms;">
                <div class="mf-feature__icon"><i class="bi bi-lightning-charge"></i></div>
                <div class="mf-feature__label"><?= __('Speed') ?></div>
                <p class="mf-feature__text"><?= __('Less clicking. More learning. Admin-style tools where they matter.') ?></p>
            </div>
        </div>

        <div class="mf-landing__deck">
            <article class="mf-landing-card mf-reveal" style="transition-delay: 120ms;">
                <div class="mf-landing-card__pulse" aria-hidden="true"></div>
                <div class="mf-landing-card__inner">
                    <div class="mf-landing-card__title">
                        <?= __('One place for quizzes, questions, answers, translations.') ?>
                    </div>
                    <div class="mf-landing-card__meta">
                        <?= __('Create multilingual content, keep consistency across difficulties and categories, and ship clean question banks for mobile and web.') ?>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="mf-landing-card__kbd"><i class="bi bi-translate"></i> <?= __('Translations') ?></span>
                        <span class="mf-landing-card__kbd"><i class="bi bi-filter"></i> <?= __('Categories') ?></span>
                        <span class="mf-landing-card__kbd"><i class="bi bi-shield-check"></i> <?= __('Rotation & revoke') ?></span>
                    </div>
                </div>
            </article>

            <aside class="mf-landing-card mf-reveal" style="transition-delay: 160ms;">
                <div class="mf-landing-card__inner">
                    <div class="mf-landing-card__title"><?= __('Fast loop') ?></div>
                    <div class="mf-landing-card__meta">
                        <?= __('Write once, test repeatedly, improve daily. AI helps generate and translate, you keep control and review.') ?>
                    </div>
                    <div class="mt-3">
                        <div class="mf-landing-card__meta">
                            <span class="mf-landing-card__kbd">1</span> <?= __('Generate') ?>
                            <span class="mf-landing-card__kbd">2</span> <?= __('Translate') ?>
                            <span class="mf-landing-card__kbd">3</span> <?= __('Practice') ?>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="mf-landing__final mf-reveal" style="transition-delay: 300ms;">
            <div class="mf-landing__final-inner">
                <div>
                    <div class="mf-landing__final-title"><?= __('Ready to build your first quiz?') ?></div>
                    <div class="mf-landing__final-sub"><?= __('Start with AI, then refine by hand. You stay in control.') ?></div>
                </div>
                <div class="mf-landing__final-actions">
                    <?php if (!$identity) : ?>
                        <a href="<?= $this->Url->build($loginUrl) ?>" class="btn btn-primary mf-landing__btn mf-landing__btn--primary">
                            <?= __('Log In') ?>
                        </a>
                        <a href="<?= $this->Url->build($registerUrl) ?>" class="btn btn-outline-light mf-landing__btn mf-landing__btn--ghost">
                            <?= __('Create Account') ?>
                        </a>
                    <?php else : ?>
                        <a href="<?= $this->Url->build($dashboardUrl) ?>" class="btn btn-primary mf-landing__btn mf-landing__btn--primary">
                            <?= __('Continue') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
