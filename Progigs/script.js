/* ═══════════════════════════════════════════════════════
   PRO GIGS AUDIO VISUAL — Main Script
   Each module is isolated: if one fails, the rest keep working.
   ═══════════════════════════════════════════════════════ */

function mod(name, fn) {
    try { fn(); }
    catch (err) { console.error('[Pro Gigs] "' + name + '" failed:', err); }
}

/* ── 1 · NAVBAR + MOBILE DROPDOWN ───────────────────── */
mod('navbar', function () {
    var navbar    = document.getElementById('navbar');
    var hamburger = document.getElementById('hamburger');
    var navLinks  = document.getElementById('nav-links');
    var overlay   = document.getElementById('nav-overlay');

    if (navbar) {
        window.addEventListener('scroll', function () {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        }, { passive: true });
    }
    if (!hamburger || !navLinks) return;

    function setMenu(open) {
        hamburger.classList.toggle('active', open);
        navLinks.classList.toggle('active', open);
        if (overlay) overlay.classList.toggle('active', open);
        hamburger.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = (open && window.innerWidth <= 768) ? 'hidden' : '';
    }

    hamburger.addEventListener('click', function (e) {
        e.stopPropagation();
        setMenu(!navLinks.classList.contains('active'));
    });

    if (overlay) overlay.addEventListener('click', function () { setMenu(false); });

    navLinks.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () { setMenu(false); });
    });

    document.addEventListener('click', function (e) {
        if (!navLinks.classList.contains('active')) return;
        if (!navLinks.contains(e.target) && !hamburger.contains(e.target)) setMenu(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setMenu(false);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) setMenu(false);
    });
});

/* ── 2 · ACTIVE LINK HIGHLIGHT ──────────────────────── */
mod('scroll-spy', function () {
    var sections = document.querySelectorAll('section[id], header[id]');
    var anchors  = document.querySelectorAll('.nav-links a[href^="#"]');
    if (!sections.length || !anchors.length) return;

    var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            anchors.forEach(function (a) {
                a.classList.toggle('active', a.getAttribute('href') === '#' + en.target.id);
            });
        });
    }, { threshold: 0.4 });

    sections.forEach(function (s) { spy.observe(s); });
});

/* ── 3 · HERO CAROUSEL ──────────────────────────────── */
mod('carousel', function () {
    var slides   = Array.prototype.slice.call(document.querySelectorAll('.slide'));
    var dotsWrap = document.getElementById('dots');
    if (!slides.length || !dotsWrap) return;

    var DELAY = 6000;
    var current = 0, timer = null;

    slides.forEach(function (_, i) {
        var b = document.createElement('button');
        b.type = 'button';
        b.setAttribute('role', 'tab');
        b.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        if (i === 0) b.classList.add('on');
        b.addEventListener('click', function () { goTo(i); restart(); });
        dotsWrap.appendChild(b);
    });
    var dots = Array.prototype.slice.call(dotsWrap.children);

    function goTo(i) {
        slides[current].classList.remove('is-active');
        dots[current].classList.remove('on');
        current = (i + slides.length) % slides.length;
        slides[current].classList.add('is-active');
        dots[current].classList.add('on');
    }
    function next()    { goTo(current + 1); }
    function prev()    { goTo(current - 1); }
    function start()   { clearInterval(timer); timer = setInterval(next, DELAY); }
    function restart() { start(); }

    var nextBtn = document.getElementById('nextSlide');
    var prevBtn = document.getElementById('prevSlide');
    if (nextBtn) nextBtn.addEventListener('click', function () { next(); restart(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); restart(); });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) clearInterval(timer); else start();
    });

    var hero = document.getElementById('home');
    if (hero && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (en) {
            if (en[0].isIntersecting) start(); else clearInterval(timer);
        }, { threshold: 0.15 }).observe(hero);
    }

    var slidesEl = document.getElementById('slides');
    if (slidesEl) {
        var x0 = null;
        slidesEl.addEventListener('touchstart', function (e) {
            x0 = e.touches[0].clientX;
        }, { passive: true });
        slidesEl.addEventListener('touchend', function (e) {
            if (x0 === null) return;
            var dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 50) { if (dx < 0) next(); else prev(); restart(); }
            x0 = null;
        }, { passive: true });
    }

    start();
});

/* ── 4 · GALLERY FILTER + LOAD MORE + LIGHTBOX ──────── */
mod('gallery', function () {
    var filters  = document.querySelectorAll('.filter');
    var galItems = Array.prototype.slice.call(document.querySelectorAll('.gal'));
    var loadBtn  = document.getElementById('loadMore');
    if (!galItems.length) return;

    var expanded = false;

    function currentFilter() {
        var on = document.querySelector('.filter.is-on');
        return on ? on.getAttribute('data-filter') : 'all';
    }

    function applyFilter(cat) {
        galItems.forEach(function (item) {
            var isExtra = item.classList.contains('more');
            var matches = (cat === 'all') || (item.getAttribute('data-cat') === cat);
            var show    = matches && (!isExtra || expanded);
            item.hidden = false;
            item.classList.toggle('hide', !show);
        });
    }

    filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filters.forEach(function (b) {
                b.classList.remove('is-on');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('is-on');
            btn.setAttribute('aria-selected', 'true');
            applyFilter(btn.getAttribute('data-filter'));
        });
    });

    if (loadBtn) {
        loadBtn.addEventListener('click', function () {
            expanded = !expanded;
            loadBtn.textContent = expanded ? 'Show Less' : 'Load More Work';
            applyFilter(currentFilter());
        });
    }

    applyFilter('all');

    /* ---- Lightbox ---- */
    var lb    = document.getElementById('lightbox');
    var lbImg = document.getElementById('lbImg');
    var lbCap = document.getElementById('lbCap');
    if (!lb || !lbImg) return;

    var lbIndex = 0;

    function visible() {
        return galItems.filter(function (g) { return !g.classList.contains('hide'); });
    }
    function openLb(idx) {
        var list = visible();
        if (!list.length) return;
        lbIndex = (idx + list.length) % list.length;
        var fig = list[lbIndex];
        var img = fig.querySelector('img');
        var cap = fig.querySelector('figcaption');
        lbImg.src = img.src;
        lbImg.alt = img.alt;
        if (lbCap) lbCap.textContent = cap ? cap.innerText.replace(/\s*\n\s*/g, ' — ') : '';
        lb.hidden = false;
        requestAnimationFrame(function () { lb.classList.add('open'); });
        document.body.style.overflow = 'hidden';
    }
    function closeLb() {
        if (lb.hidden) return;
        lb.classList.remove('open');
        setTimeout(function () { lb.hidden = true; }, 300);
        document.body.style.overflow = '';
    }

    galItems.forEach(function (fig) {
        fig.addEventListener('click', function () { openLb(visible().indexOf(fig)); });
    });

    var closeBtn = document.getElementById('lbClose');
    var nBtn     = document.getElementById('lbNext');
    var pBtn     = document.getElementById('lbPrev');
    if (closeBtn) closeBtn.addEventListener('click', closeLb);
    if (nBtn) nBtn.addEventListener('click', function (e) { e.stopPropagation(); openLb(lbIndex + 1); });
    if (pBtn) pBtn.addEventListener('click', function (e) { e.stopPropagation(); openLb(lbIndex - 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) closeLb(); });

    document.addEventListener('keydown', function (e) {
        if (lb.hidden) return;
        if (e.key === 'Escape')     closeLb();
        if (e.key === 'ArrowRight') openLb(lbIndex + 1);
        if (e.key === 'ArrowLeft')  openLb(lbIndex - 1);
    });
});

/* ── 5 · COUNTERS ───────────────────────────────────── */
mod('counters', function () {
    var counters = document.querySelectorAll('[data-count]');
    if (!counters.length || !('IntersectionObserver' in window)) return;

    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            var el = en.target;
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            var dur = 1400, t0 = null;
            function step(ts) {
                if (!t0) t0 = ts;
                var p = Math.min((ts - t0) / dur, 1);
                el.textContent = Math.floor(p * target) + (p === 1 ? '+' : '');
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
            obs.unobserve(el);
        });
    }, { threshold: 0.6 });

    counters.forEach(function (c) { obs.observe(c); });
});

/* ── 6 · SCROLL REVEAL ──────────────────────────────── */
mod('reveal', function () {
    var targets = document.querySelectorAll(
        '.svc, .ind, .gal, .logo-grid li, .about-copy, .about-media, .c-left, .c-right, .sec-head'
    );
    if (!targets.length) return;

    if (!('IntersectionObserver' in window)) {
        targets.forEach(function (el) { el.classList.add('in'); });
        return;
    }

    targets.forEach(function (el, i) {
        el.classList.add('rv');
        el.style.transitionDelay = ((i % 6) * 0.06) + 's';
    });

    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            en.target.classList.add('in');
            obs.unobserve(en.target);
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    targets.forEach(function (el) { obs.observe(el); });

    setTimeout(function () {
        targets.forEach(function (el) { el.classList.add('in'); });
    }, 3000);
});

/* ── 7 · WHATSAPP FORM ──────────────────────────────── */
mod('whatsapp-form', function () {
    var form = document.getElementById('whatsapp-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var name = document.getElementById('clientName').value.trim();
        var type = document.getElementById('eventType').value.trim();
        var msg  = document.getElementById('clientMessage').value.trim();

        if (!name || !type || !msg) {
            alert('Please fill in all three fields before sending.');
            return;
        }
        var text = 'Hello Pro Gigs!\n\nMy name is *' + name + '*.\n' +
                   "I'm enquiring about a *" + type + '*.\n\n' + msg +
                   '\n\nLooking forward to hearing from you.';
        window.open('https://wa.me/254720440062?text=' + encodeURIComponent(text),
                    '_blank', 'noopener,noreferrer');
    });
});