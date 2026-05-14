/**
 * WealthMetre — wm-core.js
 * Single source of truth for all UI behaviour.
 *
 * Replaces the three inline <script> blocks at the bottom of index.html.
 * Load with: <script src="js/wm-core.js" defer></script>
 * Place AFTER <script src="js/script.js" defer></script> in <head>.
 *
 * This file owns:
 *  - Mobile menu toggle
 *  - Diva widget open/close (single definition)
 *  - Smooth scroll
 *  - FAQ accordion
 *  - 2-step lead form
 *  - EMI calculator
 *  - Popup logic
 *  - Toast notifications
 *  - Hero rotating text
 *  - Lender logo fallback
 */

(function () {
    'use strict';

    /* ═══════════════════════════════════════════════
       UTILS
    ═══════════════════════════════════════════════ */
    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

    function fmtINR(n) {
        if (!n || isNaN(n)) return '₹0';
        n = +n;
        if (n >= 10000000) return '₹' + (n / 10000000).toFixed(2) + ' Cr';
        if (n >= 100000)   return '₹' + (n / 100000).toFixed(2) + ' L';
        return '₹' + n.toLocaleString('en-IN');
    }

    function showErr(id, show) {
        const el = document.getElementById(id);
        if (el) el.style.display = show ? 'block' : 'none';
    }

    function validMobile(v) { return /^[6-9]\d{9}$/.test(v.replace(/\D/g, '')); }
    function validName(v)   { return v.trim().length >= 2; }

    function toast(msg, type) {
        let wrap = document.getElementById('wm-toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'wm-toast-wrap';
            wrap.style.cssText = [
                'position:fixed', 'bottom:90px', 'left:50%',
                'transform:translateX(-50%)', 'z-index:99999',
                'display:flex', 'flex-direction:column', 'gap:8px',
                'pointer-events:none', 'min-width:280px', 'max-width:400px'
            ].join(';');
            document.body.appendChild(wrap);
        }
        const t   = document.createElement('div');
        const err = (type === 'error');
        t.style.cssText = [
            'background:' + (err ? '#fef2f2' : '#f0fdf4'),
            'color:'      + (err ? '#991b1b' : '#166534'),
            'border:1px solid ' + (err ? '#fecaca' : '#bbf7d0'),
            'border-radius:12px', 'padding:13px 18px',
            'font-size:14px', 'font-weight:500',
            'font-family:"Plus Jakarta Sans",sans-serif',
            'box-shadow:0 8px 24px rgba(0,0,0,.12)',
            'text-align:center', 'pointer-events:all'
        ].join(';');
        t.textContent = msg;
        wrap.appendChild(t);
        setTimeout(() => t.remove(), 4500);
    }

    // Expose toast globally (some inline handlers may need it)
    window._wmToast = toast;


    /* ═══════════════════════════════════════════════
       MOBILE MENU
    ═══════════════════════════════════════════════ */
    window.wmToggleMenu = function (e) {
        if (e) { e.preventDefault(); e.stopImmediatePropagation(); }
        const nav = document.getElementById('hNav');
        const btn = document.getElementById('mobBtn');
        if (!nav) return;
        const open = nav.classList.toggle('open');
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };


    /* ═══════════════════════════════════════════════
       DIVA WIDGET — single authoritative definition
       Previous: 3 competing script blocks all defining
       window.openDivaWidget. Last one won (fragile).
       Now: one definition, registered once after DOM ready.
    ═══════════════════════════════════════════════ */
   /* Diva widget is fully managed by js/diva-widget.js */

    /* ═══════════════════════════════════════════════
       POPUP
    ═══════════════════════════════════════════════ */
    let _popupSubmitted = false;

    window.closePopup = window.wmClosePopup = function () {
        const popup = document.getElementById('popup');
        if (popup) popup.style.display = 'none';
        sessionStorage.setItem('wm_popup_closed', 'true');
    };

    window.wmSubmitPopup = function (e) {
        if (e) { e.preventDefault(); e.stopImmediatePropagation(); }
        if (_popupSubmitted) return;

        const name    = (document.getElementById('pop-name')?.value || '').trim();
        const phone   = (document.getElementById('pop-phone')?.value || '').trim();
        const consent = document.getElementById('popConsent')?.checked;

        let ok = true;
        showErr('popNameErr',    !validName(name));    if (!validName(name))    ok = false;
        showErr('popPhoneErr',   !validMobile(phone)); if (!validMobile(phone)) ok = false;
        showErr('popConsentErr', !consent);            if (!consent)            ok = false;
        if (!ok) return;

        const btn = document.getElementById('popupSubmitBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...'; }

        const fd = new FormData();
        fd.append('name', name); fd.append('mobile', phone);
        fd.append('source', 'exit_popup'); fd.append('consent', '1');

        fetch('/api/lead-submit.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ok') {
                    _popupSubmitted = true;
                    window.wmClosePopup();
                    toast('Thank you! Our team will call you soon. ✅');
                } else {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-phone"></i> Get Free Callback'; }
                    toast(data.message || 'Please try again.', 'error');
                }
            })
            .catch(() => {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-phone"></i> Get Free Callback'; }
                toast('Network error. Please WhatsApp us.', 'error');
            });
    };

    function maybeShowPopup() {
        if (sessionStorage.getItem('wm_popup_closed')) return;
        const popup = document.getElementById('popup');
        if (popup) popup.style.display = 'flex';
    }


    /* ═══════════════════════════════════════════════
       CAPTURE-PHASE CLICK DELEGATION
       Intercepts clicks BEFORE any bubble listeners
       from js/script.js can handle them.
    ═══════════════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        const t = e.target;

        if (t.closest && t.closest('#mobBtn')) {
            window.wmToggleMenu(e); return;
        }
        if (t.closest && t.closest('.open-diva-trigger')) {
            window.wmOpenDiva(e); return;
        }
        if (t.id === 'popupCloseBtn' || (t.closest && t.closest('#popupCloseBtn'))) {
            e.preventDefault(); window.wmClosePopup(); return;
        }
        if (t.id === 'popup') {
            window.wmClosePopup(); return;
        }
    }, true); // capture phase


    /* ═══════════════════════════════════════════════
       SMOOTH SCROLL — all #anchor links
    ═══════════════════════════════════════════════ */
    document.addEventListener('click', function (e) {
        const a = e.target.closest('a[href^="#"]');
        if (!a) return;
        const href = a.getAttribute('href');
        if (!href || href === '#') { e.preventDefault(); return; }
        const target = document.querySelector(href);
        if (!target) return;
        e.preventDefault();
        const top = target.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top, behavior: 'smooth' });
        document.getElementById('hNav')?.classList.remove('open');
    });


    /* ═══════════════════════════════════════════════
       DOM READY — everything that needs elements
    ═══════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', function () {

       

        /* ── Bind close/reset inside Diva panel ── */
        document.getElementById('dvClose')?.addEventListener('click', window.closeDivaWidget);

        /* ── Bind all .open-diva-trigger elements ── */
        $$('.open-diva-trigger').forEach(b => {
            b.addEventListener('click', e => { e.preventDefault(); window.openDivaWidget(); });
        });

        /* ── Mobile nav popup digits only ── */
        const pp = document.getElementById('pop-phone');
        if (pp) pp.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });

        /* ── Popup triggers ── */
        document.addEventListener('mouseleave', e => { if (e.clientY < 10) maybeShowPopup(); });
        setTimeout(maybeShowPopup, 40000);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') window.wmClosePopup(); });


        /* ── FAQ ACCORDION ──────────────────────────────
           Uses clone trick once to strip any prior listeners
           from js/script.js, then attaches a single clean handler.
        ────────────────────────────────────────────── */
        $$('.faq-item').forEach(item => {
            const btn = item.querySelector('.faq-q');
            if (!btn) return;
            btn.removeAttribute('onclick');
            const fresh = btn.cloneNode(true);
            fresh.style.cursor = 'pointer';
            btn.parentNode.replaceChild(fresh, btn);

            fresh.addEventListener('click', e => {
                e.stopPropagation();
                const isOpen = item.classList.contains('open');
                // Close all
                $$('.faq-item').forEach(i => {
                    i.classList.remove('open');
                    const ic = i.querySelector('.fq-icon i');
                    if (ic) ic.className = 'fas fa-plus';
                    const fa = i.querySelector('.faq-a');
                    if (fa) fa.style.display = 'none';
                });
                // Open this if it was closed
                if (!isOpen) {
                    item.classList.add('open');
                    const ic = fresh.querySelector('.fq-icon i');
                    if (ic) ic.className = 'fas fa-minus';
                    const fa = item.querySelector('.faq-a');
                    if (fa) fa.style.display = 'block';
                }
            });
        });


        /* ── 2-STEP LEAD FORM ─────────────────────────── */
        const lfPhone = document.getElementById('lf-phone');
        if (lfPhone) {
            lfPhone.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
            });
        }

        document.getElementById('lfNextBtn')?.addEventListener('click', e => {
            e.preventDefault();
            const name  = document.getElementById('lf-name')?.value.trim() || '';
            const phone = document.getElementById('lf-phone')?.value.trim() || '';
            const loan  = document.getElementById('lf-loantype')?.value.trim() || '';

            let ok = true;
            showErr('lfNameErr',  !validName(name));    if (!validName(name))    ok = false;
            showErr('lfPhoneErr', !validMobile(phone)); if (!validMobile(phone)) ok = false;
            showErr('lfLoanErr',  !loan);               if (!loan)               ok = false;
            if (!ok) return;

            document.getElementById('lfStep1Panel')?.classList.add('hidden');
            document.getElementById('lfStep2Panel')?.classList.remove('hidden');
            document.getElementById('lsiStep1')?.classList.replace('active', 'done') ||
            document.getElementById('lsiStep1')?.classList.remove('active');
            document.getElementById('lsiStep1')?.classList.add('done');
            document.getElementById('lsiLine')?.classList.add('done');
            document.getElementById('lsiStep2')?.classList.add('active');
            document.getElementById('lf-city')?.focus();
        });

        document.getElementById('lfBackBtn')?.addEventListener('click', e => {
            e.preventDefault();
            document.getElementById('lfStep2Panel')?.classList.add('hidden');
            document.getElementById('lfStep1Panel')?.classList.remove('hidden');
            document.getElementById('lsiStep1')?.classList.remove('done');
            document.getElementById('lsiStep1')?.classList.add('active');
            document.getElementById('lsiLine')?.classList.remove('done');
            document.getElementById('lsiStep2')?.classList.remove('active');
        });

        let leadSubmitted = false;
        document.getElementById('mainLeadForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            if (leadSubmitted) return;

            const city    = document.getElementById('lf-city')?.value.trim() || '';
            const consent = document.getElementById('lf-consent')?.checked;
            let ok = true;
            showErr('lfCityErr',    !city);    if (!city)    ok = false;
            showErr('lfConsentErr', !consent); if (!consent) ok = false;
            if (!ok) return;

            const btn = document.getElementById('lfSubmitBtn');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...'; }

            const fd = new FormData();
            fd.append('name',      document.getElementById('lf-name')?.value.trim()     || '');
            fd.append('phone',     document.getElementById('lf-phone')?.value.trim()    || '');
            fd.append('loan_type', document.getElementById('lf-loantype')?.value        || '');
            fd.append('city',      city);
            fd.append('amount',    document.getElementById('lf-amount')?.value          || '');
            fd.append('email',     document.getElementById('lf-email')?.value.trim()    || '');
            fd.append('message',   document.getElementById('lf-msg')?.value.trim()      || '');
            fd.append('source',    'homepage_2step_form');
            fd.append('consent',   '1');

            fetch('/api/lead-submit.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        leadSubmitted = true;
                        this.querySelectorAll('.lf-2col,.lfield,.step-nav-btns,.lf-step-indicator,label')
                            .forEach(el => el.style.display = 'none');
                        document.getElementById('lfSuccess').style.display = 'block';
                        toast('Thank you! Our expert will be in touch. ✅');
                    } else {
                        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Get My Loan Match'; }
                        toast(data.message || 'Something went wrong.', 'error');
                    }
                })
                .catch(() => {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Get My Loan Match'; }
                    toast('Network error. Please WhatsApp us.', 'error');
                });
        });


        /* ── EMI CALCULATOR ───────────────────────────── */
        let _chartInst = null;

        function calcEMI() {
            const P = +document.getElementById('r-amt')?.value || 0;
            const r = (+document.getElementById('r-rt')?.value || 0) / 12 / 100;
            const n = (+document.getElementById('r-tn')?.value || 0) * 12;
            if (!P || !n) return;

            const emi   = r === 0 ? P / n : P * r * Math.pow(1+r,n) / (Math.pow(1+r,n) - 1);
            const total = emi * n;
            const inter = total - P;

            const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            set('l-amt', fmtINR(P));
            set('l-rt',  document.getElementById('r-rt')?.value + '%');
            set('l-tn',  document.getElementById('r-tn')?.value + ' Years');
            set('emi-disp', '₹' + Math.round(emi).toLocaleString('en-IN'));
            set('cr-emi',   '₹' + Math.round(emi).toLocaleString('en-IN'));
            set('cr-int',   fmtINR(Math.round(inter)));
            set('cr-tot',   fmtINR(Math.round(total)));

            const canvas = document.getElementById('cChart');
            if (canvas && window.Chart) {
                if (_chartInst) _chartInst.destroy();
                _chartInst = new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Principal', 'Interest'],
                        datasets: [{
                            data: [Math.round(P), Math.round(inter)],
                            backgroundColor: ['#1e3a8a', '#ff9800'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: c => c.label + ': ' + fmtINR(c.raw) } }
                        }
                    }
                });
            }
        }

        ['r-amt', 'r-rt', 'r-tn'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', calcEMI);
        });
        calcEMI();


        /* ── HERO ROTATING TEXT ───────────────────────── */
        <script>
document.addEventListener("DOMContentLoaded", function () {
    const rotEl = document.getElementById("rotating-text");
    if (!rotEl) return;

    const words = [
        "Personal Loan",
        "Business Loan",
        "Home Loan",
        "Working Capital"
    ];

    let wi = 0;
    let ci = 0;
    let deleting = false;

    function typeEffect() {
        const word = words[wi];

        // update text
        rotEl.textContent = word.substring(0, ci);

        // dynamic speed (more natural)
        let speed;
        if (deleting) {
            speed = 35;
        } else {
            speed = 60 + Math.random() * 40; // human-like typing
        }

        if (!deleting) {
            ci++;

            if (ci > word.length) {
                deleting = true;
                speed = 1200; // pause at full word
            }
        } else {
            ci--;

            if (ci < 0) {
                deleting = false;
                wi = (wi + 1) % words.length;
                ci = 0;
                speed = 250; // pause before next word
            }
        }

        setTimeout(typeEffect, speed);
    }

    typeEffect();
});
</script>

        /* ── LENDER LOGO FALLBACK ─────────────────────── */
        /* If image fails to load, show initials instead of broken img */
        $$('.lcard-logo img').forEach(img => {
            img.addEventListener('error', function () {
                this.classList.add('img-error');
                this.style.display = 'none';
                const wrap = this.parentElement;
                if (wrap) {
                    const name = this.alt || '';
                    const initials = name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
                    wrap.setAttribute('data-initials', initials);
                    const span = document.createElement('span');
                    span.textContent = initials;
                    span.style.cssText = 'font-size:13px;font-weight:800;color:#1e3a8a';
                    wrap.appendChild(span);
                }
            });
        });


        /* ── FADE-IN ON SCROLL ────────────────────────── */
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });

            $$('.fade-up').forEach(el => observer.observe(el));
        } else {
            // Fallback: show all immediately
            $$('.fade-up').forEach(el => el.classList.add('visible'));
        }

    }); // end DOMContentLoaded

})();
