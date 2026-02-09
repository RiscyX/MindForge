(function () {
    function initLanding() {
        var root = document.querySelector('[data-mf-landing]');
        if (!root) return;

        var reveal = Array.prototype.slice.call(root.querySelectorAll('.mf-reveal'));
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        io.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '40px 0px -10% 0px', threshold: 0.08 });

            reveal.forEach(function (el) { io.observe(el); });
        } else {
            reveal.forEach(function (el) { el.classList.add('is-in'); });
        }

        var bg = root.querySelector('[data-mf-landing-bg]');
        if (!bg) return;

        var reduceMotion = false;
        try {
            reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {
            reduceMotion = false;
        }

        var finePointer = false;
        try {
            finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
        } catch (e) {
            finePointer = false;
        }

        var raf = 0;
        function onMove(ev) {
            if (raf) return;
            raf = window.requestAnimationFrame(function () {
                raf = 0;
                var x = 0.5;
                var y = 0.35;
                if (ev && typeof ev.clientX === 'number') {
                    x = ev.clientX / Math.max(1, window.innerWidth);
                    y = ev.clientY / Math.max(1, window.innerHeight);
                }
                var tx = (x - 0.5) * 10;
                var ty = (y - 0.35) * 10;
                bg.style.transform = 'translate3d(' + tx.toFixed(2) + 'px,' + ty.toFixed(2) + 'px,0)';
            });
        }

        if (!reduceMotion && finePointer) {
            root.addEventListener('pointermove', onMove, { passive: true });
        }

        // Tiny rotating word in the preview card.
        var wordEl = root.querySelector('[data-mf-rotate-word]');
        if (wordEl) {
            var words = [];
            try {
                var raw = wordEl.getAttribute('data-mf-rotate-words') || '[]';
                words = JSON.parse(raw);
            } catch (e) {
                words = [];
            }

            if (!Array.isArray(words) || words.length === 0) {
                words = [wordEl.textContent || ''].filter(Boolean);
            }

            if (words.length < 2) return;

            var idx = 0;
            window.setInterval(function () {
                idx = (idx + 1) % words.length;
                wordEl.style.opacity = '0.0';
                window.setTimeout(function () {
                    wordEl.textContent = words[idx];
                    wordEl.style.opacity = '1.0';
                }, 220);
            }, 3200);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLanding);
    } else {
        initLanding();
    }
})();
