/**
 * WealthMetre — diva-widget.js  (Phase 2 + Phase 3 launcher)
 *
 * Phase 2 (existing):
 *  1. Animated orb identity
 *  2. Context-aware opening — detects page section + UTM
 *  3. Session recovery — localStorage state persistence
 *  4. Voice input — Web Speech API with Hindi/English support
 *  5. Live lender match counter — animated narrowing as user answers
 *
 * Phase 3:
 *  6. initDivaLauncher() — branded pill launcher + popup panel
 *     Connected to /api/diva_v3.php (same backend as old #divaPanel)
 *
 * Load: <script src="js/diva-widget.js" defer></script>
 */

(function () {
    'use strict';

    if (window._divaWidgetLoaded) return;
    window._divaWidgetLoaded = true;

    var API_V2   = '/api/diva_v3.php';
    var API_SAVE = '/api/diva_save.php';

    var SESSION_KEY    = 'wm_diva_v2';
    var SESSION_MAX_MS = 30 * 60 * 1000;

    var st = {
        _sid      : gid(),
        open      : false,
        history   : [],
        profile   : {},
        mode      : 'welcome_mode',
        _t        : null,
        _ctx      : null,
        _recovered: false,
    };

    var LENDER_COUNTS = {
        start          : 140,
        product_type   : 112,
        city           : 89,
        occupation_type: 76,
        property_type  : 63,
        loan_amount    : 55,
        property_value : 50,
        monthly_income : 42,
        existing_emi   : 38,
        cibil_score    : 28,
        customer_name  : 20,
        customer_mobile: 12,
    };

    var currentMatchCount = 140;

    function gid() {
        return 'diva_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
    }

    /* ═══════════════════════════════════════════════
       DOM REFS (old #divaPanel)
    ═══════════════════════════════════════════════ */
    var tog = document.getElementById('divaToggle');
    var pan = document.getElementById('divaPanel');
    var bod = document.getElementById('dvBody');
    var inp = document.getElementById('dvInput');
    var snd = document.getElementById('dvSend');
    var cls = document.getElementById('dvClose');
    var rst = document.getElementById('dvReset');
    var mic = document.getElementById('dvVoice');

    /* Old panel not on this page — init the new launcher only */
    if (!pan || !bod || !inp) {
        document.addEventListener('DOMContentLoaded', function () {
            initDivaLauncher();
        });
        return;
    }

    /* ═══════════════════════════════════════════════
       OPEN / CLOSE  (old panel)
    ═══════════════════════════════════════════════ */
    function openPanel() {
        st.open = true;
        pan.classList.add('dv-open');
        setThinking(false);
        if (!st.history.length) {
            var recovered = tryRecoverSession();
            if (!recovered) { st._ctx = detectPageContext(); init(); }
        }
        setTimeout(function () { inp.focus(); }, 350);
    }

    function closePanel() {
        st.open = false;
        pan.classList.remove('dv-open');
        persistSession();
    }

    window.openDivaWidget  = openPanel;
    window.closeDivaWidget = closePanel;

    if (tog) tog.addEventListener('click', function () { st.open ? closePanel() : openPanel(); });
    if (cls) cls.addEventListener('click', closePanel);
    if (rst) rst.addEventListener('click', resetAll);

    document.addEventListener('DOMContentLoaded', function () {
        initSectionTracking();
        $$('.open-diva-trigger').forEach(function (b) {
            b.addEventListener('click', function (e) { e.preventDefault(); openPanel(); });
        });
        if (mic) {
            var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) mic.classList.add('dv-voice-not-supported');
            else     mic.addEventListener('click', toggleVoice);
        }
        initDivaLauncher();
    });

    function setThinking(on) {
        if (!tog) return;
        if (on) tog.classList.add('dv-thinking');
        else    tog.classList.remove('dv-thinking');
    }

    /* ═══════════════════════════════════════════════
       FEATURE 2 — CONTEXT-AWARE OPENING
    ═══════════════════════════════════════════════ */
    var SECTION_MAP = {
        'hero'              : { product: null, msg: null },
        'loans'             : { product: null, msg: "Kaunsa loan chahiye? 140+ banks & NBFCs mein se best match dhundh deti hoon! \uD83C\uDFE6" },
        'why'               : { product: null, msg: "WealthMetre ke baare mein jaana? Apna requirement batao \u2014 instant matching! \uD83D\uDE0A" },
        'how'               : { product: null, msg: "Process simple hai! Apni details batao \u2014 2 minute mein best lenders milenge. \uD83D\uDE80" },
        'diva'              : { product: null, msg: "Diva AI ready hai! Apni requirement batao \u2014 2 minute mein top lenders ki list! \uD83E\uDD16" },
        'testimonials'      : { product: null, msg: "Real results dekhe? Aapka bhi case match karein \u2014 140+ lenders mein se best! \uD83C\uDF1F" },
        'partners'          : { product: null, msg: "140+ lenders dekhe? Ab apne profile ke liye best match dhundh deti hoon! \uD83C\uDFDB\uFE0F" },
        'calc'              : { product: null, msg: "EMI calculate kar liya? Ab actual lender match karein \u2014 real rates ke saath! \uD83D\uDCCA" },
        'faq'               : { product: null, msg: "Koi sawaal hai? Main Diva hoon \u2014 seedha jawab deti hoon! \uD83D\uDCAC" },
        'lead'              : { product: null, msg: "Form bharna chahte hain? Ya seedha mujhse baat karein \u2014 2 minute mein results! \u2705" },
        'rajasthan-coverage': { product: null, msg: "Aapke city mein best lenders dhundh deti hoon \u2014 Jaipur, Jodhpur, Udaipur, koi bhi! \uD83D\uDCCD" },
    };

    var _trackedSection = null;

    function initSectionTracking() {
        var path = window.location.pathname.toLowerCase();
        if      (path.includes('lap') || path.includes('loan-against')) _trackedSection = { product: 'lap',      msg: "Loan Against Property ke liye aaye hain? Best matching lenders dhundh deti hoon! \uD83C\uDFE2" };
        else if (path.includes('home-loan'))                             _trackedSection = { product: 'home',     msg: "Ghar ka sapna poora karna hai? Best home loan \u2014 140+ lenders mein se! \uD83C\uDFE0" };
        else if (path.includes('business-loan'))                         _trackedSection = { product: 'business', msg: "Business loan chahiye? MSME aur working capital options instantly! \uD83D\uDCBC" };
        else if (path.includes('cibil'))                                 _trackedSection = { product: null,       msg: "Low CIBIL hai? Koi baat nahi \u2014 kuch NBFCs hain jo aapke liye kaam karein! \uD83D\uDCCA" };

        if (!('IntersectionObserver' in window)) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting && entry.intersectionRatio >= 0.3) {
                    var id  = entry.target.id;
                    var ctx = SECTION_MAP[id];
                    if (ctx && ctx.msg) _trackedSection = { id: id, product: ctx.product, msg: ctx.msg };
                }
            });
        }, { threshold: 0.3 });

        Object.keys(SECTION_MAP).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) observer.observe(el);
        });
    }

    function detectPageContext() {
        var ctx = { section: null, product: null, utmSource: null, utmMedium: null };
        try {
            var params = new URLSearchParams(window.location.search);
            ctx.utmSource   = params.get('utm_source')   || null;
            ctx.utmMedium   = params.get('utm_medium')   || null;
            ctx.utmCampaign = params.get('utm_campaign') || null;
        } catch (e) {}
        if (_trackedSection) {
            ctx.section = _trackedSection.id     || null;
            ctx.product = _trackedSection.product || null;
        }
        return ctx;
    }

    function getContextMessage() {
        var ctx = st._ctx;
        if (!ctx) return null;
        if (ctx.utmSource === 'google' || ctx.utmMedium === 'cpc' || ctx.utmMedium === 'ppc')
            return "Google se aaye hain? Bilkul sahi jagah! Apna loan requirement batao \u2014 best rate instantly! \uD83D\uDD0D";
        if (ctx.utmSource === 'whatsapp' || ctx.utmMedium === 'whatsapp')
            return "WhatsApp se aaye hain! Namaste \uD83D\uDE4F Batao kaunsa loan chahiye \u2014 turant matching!";
        if (ctx.utmSource === 'facebook' || ctx.utmSource === 'instagram')
            return "Social media se aaye hain! \uD83D\uDC4B Best loan option dhundh deti hoon \u2014 140+ lenders, bilkul free!";
        if (_trackedSection && _trackedSection.msg) return _trackedSection.msg;
        return null;
    }

    /* ═══════════════════════════════════════════════
       FEATURE 3 — SESSION RECOVERY
    ═══════════════════════════════════════════════ */
    function persistSession() {
        if (!st.history.length && !Object.keys(st.profile).length) return;
        try {
            localStorage.setItem(SESSION_KEY, JSON.stringify({
                ts: Date.now(), sid: st._sid,
                history: st.history.slice(-20), profile: st.profile, mode: st.mode,
            }));
        } catch (e) {}
    }

    function tryRecoverSession() {
        try {
            var raw = localStorage.getItem(SESSION_KEY);
            if (!raw) return false;
            var saved = JSON.parse(raw);
            if (!saved || !saved.ts) return false;
            if (Date.now() - saved.ts > SESSION_MAX_MS) { localStorage.removeItem(SESSION_KEY); return false; }
            if (!saved.history || saved.history.length < 2) return false;
            var profile   = saved.profile || {};
            var loanLabel = (profile.product_type || 'loan').toUpperCase();
            var cityLabel = profile.city ? ' in ' + profile.city.charAt(0).toUpperCase() + profile.city.slice(1) : '';
            showRecoveryBanner(loanLabel, cityLabel, saved);
            return true;
        } catch (e) { return false; }
    }

    function showRecoveryBanner(loanLabel, cityLabel, saved) {
        var bar = document.createElement('div');
        bar.className = 'dv-recovery-bar';
        bar.innerHTML = '<div class="rb-icon">\u26A1</div>'
            + '<div class="rb-text">Welcome back! You were looking at <strong>' + esc(loanLabel) + cityLabel + '</strong> options. Continue?</div>'
            + '<button class="dv-rb-yes" id="dvRecYes">Continue</button>'
            + '<button class="dv-rb-no" id="dvRecNo">Fresh start</button>';
        var head = pan.querySelector('.dv-head');
        if (head && head.nextSibling) pan.insertBefore(bar, head.nextSibling);
        else pan.insertBefore(bar, bod);
        document.getElementById('dvRecYes').addEventListener('click', function () { bar.remove(); restoreSession(saved); });
        document.getElementById('dvRecNo').addEventListener('click', function () { bar.remove(); localStorage.removeItem(SESSION_KEY); st._recovered = false; init(); });
    }

    function restoreSession(saved) {
        st._sid = saved.sid || gid();
        st.history = saved.history || [];
        st.profile = saved.profile || {};
        st.mode    = saved.mode || 'welcome_mode';
        st._recovered = true;
        st.history.forEach(function (msg) {
            if (msg.role === 'user')           bubble(msg.content, 'user');
            else if (msg.role === 'assistant') bubble(msg.content, 'bot');
        });
        var count = LENDER_COUNTS.start;
        Object.keys(LENDER_COUNTS).forEach(function (field) { if (st.profile[field]) count = Math.min(count, LENDER_COUNTS[field]); });
        currentMatchCount = count;
        bubble("Welcome back! Aapka session restore ho gaya. \uD83D\uDC4B Kahaan se chhoda tha, wahin se shuru karte hain.", 'bot');
        setTimeout(function () { sendToAPI('', false); }, 600);
    }

    /* ═══════════════════════════════════════════════
       FEATURE 4 — VOICE INPUT
    ═══════════════════════════════════════════════ */
    var _recognition = null;
    var _isListening = false;

    function toggleVoice() { if (_isListening) stopVoice(); else startVoice(); }

    function startVoice() {
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SR) return;
        _recognition = new SR();
        _recognition.lang = 'hi-IN';
        _recognition.interimResults  = true;
        _recognition.maxAlternatives = 1;
        _recognition.continuous      = false;
        _recognition.onstart = function () {
            _isListening = true;
            if (mic) { mic.classList.add('listening'); mic.title = 'Bol rahein hain... (tap to stop)'; }
            if (tog) tog.classList.add('dv-listening');
            inp.placeholder = '\uD83C\uDF99\uFE0F Bol rahein hain...';
        };
        _recognition.onresult = function (e) {
            var t = '';
            for (var i = e.resultIndex; i < e.results.length; i++) t += e.results[i][0].transcript;
            inp.value = t;
            if (e.results[e.results.length - 1].isFinal) {
                setTimeout(function () { stopVoice(); if (inp.value.trim()) handleSend(); }, 500);
            }
        };
        _recognition.onerror = function (e) {
            stopVoice();
            if      (e.error === 'not-allowed') showToast('Microphone permission denied. Please allow access.');
            else if (e.error === 'no-speech')   showToast('Koi awaz nahi aayi. Phir try karein.');
        };
        _recognition.onend = function () { stopVoice(); };
        try { _recognition.start(); } catch (e) { stopVoice(); }
    }

    function stopVoice() {
        _isListening = false;
        if (_recognition) { try { _recognition.stop(); } catch (e) {} _recognition = null; }
        if (mic) { mic.classList.remove('listening'); mic.title = 'Voice input'; }
        if (tog) tog.classList.remove('dv-listening');
        inp.placeholder = 'Type your message\u2026';
    }

    function showToast(msg) {
        var t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;z-index:99999;font-family:"Plus Jakarta Sans",sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.2)';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 3000);
    }

    /* ═══════════════════════════════════════════════
       FEATURE 5 — LIVE LENDER MATCH COUNTER
    ═══════════════════════════════════════════════ */
    function showMatchCounter(fieldJustAnswered) {
        var target = LENDER_COUNTS[fieldJustAnswered] || currentMatchCount;
        if (target >= currentMatchCount) return;
        var prev = currentMatchCount;
        currentMatchCount = target;
        var wrap = document.createElement('div');
        wrap.className = 'dv-match-counter';
        wrap.innerHTML = '<div class="dv-mc-track">'
            + '<div class="dv-mc-label"><div class="mc-spin"></div>Scanning lenders for your profile\u2026</div>'
            + '<div class="dv-mc-bar-wrap"><div class="dv-mc-bar" id="mcBar" style="width:' + Math.round((prev / 140) * 100) + '%"></div></div>'
            + '<div class="dv-mc-nums"><div><div class="dv-mc-count" id="mcCount">' + prev + '</div><div class="dv-mc-total">of 140 lenders</div></div><div class="dv-mc-found">Narrowing...</div></div>'
            + '</div>';
        bod.appendChild(wrap);
        scroll();
        setTimeout(function () {
            var bar   = wrap.querySelector('#mcBar');
            var count = wrap.querySelector('#mcCount');
            var found = wrap.querySelector('.dv-mc-found');
            var track = wrap.querySelector('.dv-mc-track');
            var label = wrap.querySelector('.dv-mc-label');
            if (bar) bar.style.width = Math.round((target / 140) * 100) + '%';
            var duration = 1200, start = Date.now(), from = prev;
            (function tick() {
                var elapsed  = Date.now() - start;
                var progress = Math.min(elapsed / duration, 1);
                var ease     = 1 - Math.pow(1 - progress, 3);
                var val      = Math.round(from - (from - target) * ease);
                if (count) count.textContent = val;
                if (progress < 1) requestAnimationFrame(tick);
                else {
                    if (count) count.textContent = target;
                    if (found) { found.textContent = target + ' matches found'; found.style.color = '#16a34a'; }
                    if (track) track.classList.add('mc-done');
                    if (label) {
                        label.innerHTML = '\u2705 ' + target + ' matching lenders found';
                        var spin = label.querySelector('.mc-spin');
                        if (spin) spin.remove();
                    }
                }
            })();
        }, 80);
    }

    function removeOldCounters() { bod.querySelectorAll('.dv-match-counter').forEach(function (el) { el.remove(); }); }

    /* ═══════════════════════════════════════════════
       SAVE
    ═══════════════════════════════════════════════ */
    async function save(action, extra) {
        try {
            await fetch(API_SAVE, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify(Object.assign({ action: action, session_id: st._sid, lang: st.profile._lang || 'en' }, extra || {})),
            });
        } catch (e) {}
    }

    /* ═══════════════════════════════════════════════
       DOM HELPERS  (old panel)
    ═══════════════════════════════════════════════ */
    function scroll() { setTimeout(function () { bod.scrollTop = bod.scrollHeight; }, 60); }

    function makeAvatar() {
        var av  = document.createElement('div'); av.className = 'dv-av';
        var inn = document.createElement('div'); inn.className = 'dv-av-in'; inn.textContent = 'D';
        av.appendChild(inn);
        return av;
    }

    function bubble(text, who) {
        clearInter();
        var row = document.createElement('div');
        row.className = 'dv-msg ' + (who || 'bot');
        if (who === 'bot') row.appendChild(makeAvatar());
        var b = document.createElement('div');
        b.className   = 'dv-bub';
        b.textContent = String(text || '').trim();
        row.appendChild(b);
        bod.appendChild(row);
        scroll();
    }

    function showTyping() {
        clearTyping();
        var row = document.createElement('div');
        row.className = 'dv-msg bot dv-typing-row';
        var bub = document.createElement('div');
        bub.className = 'dv-bub';
        bub.innerHTML = '<div class="dv-typing"><div class="dv-dot"></div><div class="dv-dot"></div><div class="dv-dot"></div></div>';
        row.appendChild(makeAvatar());
        row.appendChild(bub);
        bod.appendChild(row);
        scroll();
    }

    function clearTyping() { bod.querySelectorAll('.dv-typing-row').forEach(function (e) { e.remove(); }); }
    function clearInter()  { bod.querySelectorAll('.dv-chips,.dv-inp-wrap,.dv-loader,.dv-cta,.dv-consent,.dv-summary').forEach(function (e) { e.remove(); }); }

    function chips(opts) {
        if (!opts || !opts.length) return;
        var w = document.createElement('div');
        w.className = 'dv-chips';
        opts.forEach(function (o) {
            var b = document.createElement('button');
            b.className   = 'dv-chip';
            b.textContent = o;
            b.onclick     = function () { send(o); };
            w.appendChild(b);
        });
        bod.appendChild(w);
        scroll();
    }

    function inlineInput(type, ph) {
        var w = document.createElement('div'); w.className = 'dv-inp-wrap';
        var i = document.createElement('input');
        i.type = type === 'number' ? 'number' : 'text';
        i.className   = 'dv-inp';
        i.placeholder = ph || 'Type here\u2026';
        var b = document.createElement('button');
        b.className   = 'dv-inp-btn';
        b.textContent = 'Next \u2192';
        b.onclick = function () { if (i.value.trim()) send(i.value.trim()); };
        i.addEventListener('keypress', function (e) { if (e.key === 'Enter' && i.value.trim()) send(i.value.trim()); });
        w.appendChild(i); w.appendChild(b);
        bod.appendChild(w);
        setTimeout(function () { i.focus(); }, 100);
        scroll();
    }

    function showSummary(d) {
        if (!d) return;
        var c = document.createElement('div');
        c.className = 'dv-summary';
        c.innerHTML = '<div style="font-size:12px;font-weight:700;color:#1e3a8a;margin-bottom:10px">\uD83D\uDCCB ' + (d.title || 'Case Summary') + '</div>';
        (d.fields || []).forEach(function (f) {
            var r = document.createElement('div');
            r.className = 'dv-srow';
            r.innerHTML = '<span class="sl">' + esc(f.label) + '</span><span class="sv">' + esc(f.value) + '</span>';
            c.appendChild(r);
        });
        bod.appendChild(c);
        scroll();
    }

    function showConsent() {
        var b = document.createElement('div'); b.className = 'dv-consent';
        b.innerHTML = '<div style="font-size:13px;color:#166534;line-height:1.5">\uD83E\uDD1D Kya main shortlisted lenders ke saath aapka case share karun?</div>'
            + '<div class="dv-cbtns">'
            + '<button class="dv-cyes" onclick="window._dvConsent(true)">\u2705 Haan, share karein</button>'
            + '<button class="dv-cno" onclick="window._dvConsent(false)">Abhi nahi</button>'
            + '</div>';
        bod.appendChild(b); scroll();
    }

    window._dvConsent = function (yes) {
        clearInter();
        if (yes) { bubble('Haan, share karein', 'user'); st.profile.consent_to_share = true; bubble('Shukriya! \uD83D\uDE4F Shortlisted lenders ke sales managers aapko jald contact karenge.\n\uD83D\uDCDE +91 7976218596', 'bot'); }
        else     { bubble('Abhi nahi', 'user'); bubble('Koi baat nahi. \uD83D\uDE0A', 'bot'); }
    };

    function showMobileCTA() {
        var b = document.createElement('div'); b.className = 'dv-cta';
        b.innerHTML = '<div class="dv-cta-text">Mobile number share karein?</div><button class="dv-cta-btn" onclick="window._dvMobile()">\uD83D\uDCDE Mobile Share Karein</button>';
        bod.appendChild(b); scroll();
    }

    window._dvMobile = function () { clearInter(); bubble('Mobile number share karein:', 'bot'); inlineInput('number', '10-digit mobile'); };

    function showContextPill(label) {
        var existing = pan.querySelector('.dv-context-pill'); if (existing) existing.remove();
        if (!label) return;
        var el = document.createElement('div'); el.className = 'dv-context-pill';
        el.innerHTML = '\uD83D\uDCCD <span>' + esc(label) + '</span>';
        var head = pan.querySelector('.dv-head');
        if (head && head.nextSibling) pan.insertBefore(el, head.nextSibling);
        else pan.insertBefore(el, bod);
        setTimeout(function () { if (el.parentNode) el.remove(); }, 8000);
    }

    /* ═══════════════════════════════════════════════
       LENDER RESULTS RENDERER  (old panel)
    ═══════════════════════════════════════════════ */
    function renderResults(data) {
        clearInter(); removeOldCounters();
        var lenders = Array.isArray(data.lenders) ? data.lenders : [];
        var total   = data.total_matches || lenders.length;
        if (!lenders.length) { bubble('Abhi exact match nahi mila.\n\uD83D\uDCDE +91 7976218596', 'bot'); save('zero_results'); return; }
        bubble('\u2705 ' + total + ' lenders mein se top ' + Math.min(lenders.length, 10) + ' match mile:', 'bot');
        var tw = document.createElement('div'); tw.className = 'dv-results';
        tw.innerHTML = '<div class="dv-rh">\uD83C\uDFC6 Diva\'s Top Picks</div>';
        lenders.slice(0, 3).forEach(function (l, i) { tw.appendChild(mkCard(l, i, true)); });
        bod.appendChild(tw);
        if (lenders.length > 3) {
            var mw = document.createElement('div'); mw.className = 'dv-results';
            mw.innerHTML = '<div class="dv-rh" style="color:#94a3b8;">Other Options</div>';
            lenders.slice(3).forEach(function (l, i) { mw.appendChild(mkCard(l, i + 3, false)); });
            bod.appendChild(mw);
        }
        save('save_results', { lenders: lenders.slice(0, 10), total_matches: total });
        setTimeout(function () {
            var disc = document.createElement('div'); disc.className = 'dv-msg bot'; disc.appendChild(makeAvatar());
            var bub = document.createElement('div'); bub.className = 'dv-bub';
            bub.style.cssText = 'font-size:12px;color:#64748b;background:#f8fafc;border-color:#f1f5f9';
            bub.innerHTML = '\u26A0\uFE0F <strong>Indicative only.</strong> Final eligibility subject to lender assessment.';
            disc.appendChild(bub); bod.appendChild(disc);
            bubble('Kuch aur poochhna hai? \uD83D\uDE0A', 'bot');
            chips(['Best rate kaun dega?', '\uD83C\uDFAF Exact quote chahiye', 'EMI calculate karein', 'Start over']);
            scroll();
        }, 400);
        scroll(); persistSession();
    }

    function mkCard(l, i, isTop) {
        var r  = parseFloat(l.roi_min || 0), rx = parseFloat(l.roi_max || 0);
        var roi  = (r > 0 && rx > 0 && rx !== r) ? r + '% \u2013 ' + rx + '%' : (r > 0 ? r + '%+' : 'On request');
        var ltv  = parseFloat(l.max_ltv || 0); var ltvS = ltv > 0 ? ltv + '%' : '\u2014';
        var ten  = l.max_tenure_months > 0 ? Math.round(l.max_tenure_months / 12) + ' yrs' : '\u2014';
        var name = l.lender_name || 'Lender ' + (i + 1);
        var score = l.score != null ? l.score : '\u2014';
        var aiR  = l.ai_reason || '', why = l.why || '', notes = l.notes || '';
        var mgr  = l.sales_manager_name || '', mob = l.sales_manager_mobile || '';
        var bd = '';
        if (isTop && l.score_breakdown) {
            bd = '<div class="dv-spills">'
                + Object.values(l.score_breakdown).map(function (f) {
                    return '<span class="dv-spill ' + (String(f.points).startsWith('-') ? 'neg' : 'pos') + '">' + f.label + ' ' + f.points + '</span>';
                }).join('') + '</div>';
        }
        var c = document.createElement('div'); c.className = 'dv-lcard' + (isTop ? ' top' : '');
        var rb = isTop
            ? '<div class="dv-rank top">#' + (i+1) + ' \u00B7 Score ' + score + ' \u00B7 Diva\'s Pick \u2B50</div>'
            : '<div class="dv-rank">#' + (i+1) + ' \u00B7 ' + score + '</div>';
        c.innerHTML = rb
            + '<div class="dv-lname">' + esc(name) + '</div>'
            + '<div class="dv-lgrid">'
            + '<div class="dv-lstat"><div class="sl">ROI</div><div class="sv">'        + esc(roi)  + '</div></div>'
            + '<div class="dv-lstat"><div class="sl">Max LTV</div><div class="sv or">' + esc(ltvS) + '</div></div>'
            + '<div class="dv-lstat"><div class="sl">Tenure</div><div class="sv">'     + esc(ten)  + '</div></div>'
            + '</div>'
            + (aiR   ? '<div class="dv-lai">\uD83E\uDD16 '  + esc(aiR) + '</div>' : (why ? '<div class="dv-lwhy">' + esc(why) + '</div>' : ''))
            + bd
            + (notes ? '<div class="dv-lnote">\uD83D\uDCCC ' + esc(notes) + '</div>' : '')
            + (mgr && mob ? '<div class="dv-lcontact">\uD83D\uDCDE <strong>' + esc(mgr) + '</strong> \u2014 ' + esc(mob) + '</div>' : '');
        return c;
    }

    function esc(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    /* ═══════════════════════════════════════════════
       API CALLS  (old panel)
    ═══════════════════════════════════════════════ */
    async function init() {
        showTyping();
        var contextMsg = getContextMessage();
        if (contextMsg && st._ctx && st._ctx.section) showContextPill('Detected: ' + st._ctx.section.replace(/-/g, ' '));
        var r = await callV2('', true);
        clearTyping();
        if (contextMsg && r) {
            r.message = contextMsg;
            if (st._ctx && st._ctx.product) { r.profile = r.profile || {}; r.profile.product_type = st._ctx.product; }
        }
        if (r) render(r);
        save('start', { utm: document.referrer || '', section: st._ctx ? st._ctx.section : null });
    }

    async function send(text) {
        var v = text.trim(); if (!v) return;
        if (inp.value === v) inp.value = '';
        clearInter(); bubble(v, 'user'); st.history.push({ role: 'user', content: v });
        setThinking(true); showTyping();
        var r = await callV2(v, false);
        clearTyping(); setThinking(false);
        if (r) render(r);
        clearTimeout(st._t);
        st._t = setTimeout(function () { persistSession(); save('update_profile', { profile: st.profile }); }, 1500);
    }

    function sendToAPI(msg, isInit) {
        return callV2(msg, isInit).then(function (r) { clearTyping(); setThinking(false); if (r) render(r); });
    }

    async function callV2(msg, isInit) {
        try {
            var res = await fetch(API_V2, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ message: msg, history: st.history.slice(-16), profile: st.profile, session_id: st._sid, mode: isInit ? 'welcome_mode' : st.mode }),
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return await res.json();
        } catch (e) {
            console.error('[DIVA]', e);
            return { mode: 'error', message: 'Kuch issue aa gaya. \uD83D\uDE14\n\uD83D\uDCDE +91 7976218596', chips: ['Try again', 'Start over'] };
        }
    }

    /* ═══════════════════════════════════════════════
       RENDER  (old panel)
    ═══════════════════════════════════════════════ */
    function render(data) {
        if (!data) return;
        if (data.mode) st.mode = data.mode;
        var prevExpecting = st.profile._expecting;
        if (data.profile && typeof data.profile === 'object') st.profile = Object.assign({}, st.profile, data.profile);
        if (data.extracted && typeof data.extracted === 'object') {
            Object.entries(data.extracted).forEach(function (kv) { if (kv[1] != null && kv[1] !== '') st.profile[kv[0]] = kv[1]; });
        }
        if (data.message) st.history.push({ role: 'assistant', content: data.message });
        if (prevExpecting && prevExpecting !== 'customer_mobile' && LENDER_COUNTS[prevExpecting]) {
            var newExpecting = st.profile._expecting;
            if (newExpecting !== prevExpecting) { setTimeout(function () { removeOldCounters(); showMatchCounter(prevExpecting); }, 200); }
        }
        if (data.lender_result) {
            if (data.lender_result.type === 'collect_contact') {
                bubble(data.message || '', 'bot');
                bubble('Hamara expert personally best option dhundega.\n\uD83D\uDCDE +91 7976218596', 'bot');
                chips(['Dobara try karein', 'Call karein']); save('zero_results'); return;
            }
            if (data.message) bubble(data.message, 'bot');
            renderResults(data.lender_result); return;
        }
        if (data.message) bubble(data.message, 'bot');
        if (data.trigger_summary && data.summary_data) { showSummary(data.summary_data); chips(['\u2705 Sahi hai, lenders dikhao', 'Kuch change karna hai']); return; }
        if (data.show_consent) { showConsent(); return; }
        if (data.ask_mobile)   { showMobileCTA(); return; }
        if (data.ask_name)     { inlineInput('text', 'Aapka naam\u2026'); return; }
        if (data.chips && data.chips.length) { chips(data.chips); return; }
    }

    /* ═══════════════════════════════════════════════
       INPUT HANDLING  (old panel)
    ═══════════════════════════════════════════════ */
    function handleSend() {
        var v = inp.value.trim(); if (!v) return;
        inp.value = '';
        if (/start over|reset|restart/i.test(v)) { resetAll(); return; }
        if (/^(call|phone)\s*$/i.test(v)) { bubble(v, 'user'); bubble('Call: +91 7976218596\nWhatsApp: wa.me/917976218596', 'bot'); return; }
        send(v);
    }

    function resetAll() {
        st.history = []; st.profile = {}; st.mode = 'welcome_mode'; st._sid = gid();
        st._ctx = null; st._recovered = false; currentMatchCount = 140;
        bod.innerHTML = '';
        var rec = pan.querySelector('.dv-recovery-bar'); if (rec) rec.remove();
        var ctx = pan.querySelector('.dv-context-pill'); if (ctx) ctx.remove();
        localStorage.removeItem(SESSION_KEY);
        st._ctx = detectPageContext(); init();
    }

    snd.addEventListener('click', handleSend);
    inp.addEventListener('keypress', function (e) { if (e.key === 'Enter') handleSend(); });

    function $$(sel) { return Array.from(document.querySelectorAll(sel)); }
    window.addEventListener('beforeunload', persistSession);


    /* ═══════════════════════════════════════════════════════════
       PHASE 3 — FLOATING LAUNCHER + POPUP PANEL
       Connected to /api/diva_v3.php — same backend as old panel
    ═══════════════════════════════════════════════════════════ */
    function initDivaLauncher() {
        var launcher  = document.getElementById('dvLauncher');
        var popup     = document.getElementById('dvPopupPanel');
        var overlay   = document.getElementById('dvOverlay');
        var closeBtn  = document.getElementById('dvPopupClose');
        var launcherX = document.getElementById('dvLauncherX') || document.getElementById('dvLauncherClose');
        var popBody   = document.getElementById('dvPopupBody');
        var inputEl   = document.getElementById('dvPopupInput');
        var sendBtn   = document.getElementById('dvPopupSend');

        if (!launcher || !popup) return;

        var DIVA_IMG   = '/images/ask-diva.png';
        var API_URL    = '/api/diva_v3.php';

        var isOpen     = false;
        var greeted    = false;
        var popHistory = [];
        var popProfile = {};
        var popMode    = 'welcome_mode';
        var isThinking = false;
        var _lendersShown = false;
        var popSid     = 'diva_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);

        /* ── Open ── */
        function openPopup() {
            isOpen = true;
            popup.classList.add('open');
            popup.setAttribute('aria-hidden', 'false');
            if (overlay) overlay.classList.add('active');
            launcher.classList.add('hidden');
            launcher.setAttribute('aria-expanded', 'true');
            if (!greeted) {
                greeted = true;
                setTimeout(function () { popCallAPI('', true); }, 380);
            } else {
                if (inputEl) setTimeout(function () { inputEl.focus(); }, 320);
            }
        }

        /* ── Close ── */
        function closePopup() {
            isOpen = false;
            popup.classList.remove('open');
            popup.setAttribute('aria-hidden', 'true');
            if (overlay) overlay.classList.remove('active');
            launcher.classList.remove('hidden');
            launcher.setAttribute('aria-expanded', 'false');
        }

        /* ── DOM helpers ── */
        function popScroll() {
            if (popBody) setTimeout(function () { popBody.scrollTop = popBody.scrollHeight; }, 80);
        }

        function popEsc(s) {
            return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function popDivaMsg(html) {
            popRemoveTyping();
            var w = document.createElement('div');
            w.className = 'dv-msgRow pop-msg';
            w.innerHTML =
                '<div class="dv-av">' +
                    '<img src="' + DIVA_IMG + '" alt="Diva" draggable="false" ' +
                    'onerror="this.parentNode.style.background=\'linear-gradient(135deg,#1A3A6B,#2563EB)\'" />' +
                '</div>' +
                '<div class="dv-bubble">' + html + '</div>';
            popBody.appendChild(w);
            popScroll();
        }

        function popUserMsg(text) {
            popRemoveChips();
            var w = document.createElement('div');
            w.className = 'dv-replyRow pop-msg';
            w.innerHTML = '<div class="dv-replyBubble">' + popEsc(text) + '</div>';
            popBody.appendChild(w);
            popScroll();
        }

        function popShowTyping() {
            popRemoveTyping();
            var t = document.createElement('div');
            t.className = 'pop-typing pop-msg _pop_typing';
            t.innerHTML =
                '<div class="dv-av">' +
                    '<img src="' + DIVA_IMG + '" alt="" draggable="false" ' +
                    'onerror="this.parentNode.style.background=\'linear-gradient(135deg,#1A3A6B,#2563EB)\'" />' +
                '</div>' +
                '<div class="pop-typing__bubble"><span></span><span></span><span></span></div>';
            popBody.appendChild(t);
            popScroll();
        }

        function popRemoveTyping() {
            if (popBody) popBody.querySelectorAll('._pop_typing').forEach(function (el) { el.remove(); });
        }

        function popAddChips(chipList, onClickFn) {
            popRemoveChips();
            if (!chipList || !chipList.length) return;
            var c = document.createElement('div');
            c.className = 'pop-chips pop-msg _pop_chips';
            chipList.forEach(function (label) {
                var b = document.createElement('button');
                b.className   = 'pop-chip';
                b.textContent = label;
                b.onclick     = function () { onClickFn(label); };
                c.appendChild(b);
            });
            popBody.appendChild(c);
            popScroll();
        }

        function popRemoveChips() {
            if (popBody) popBody.querySelectorAll('._pop_chips').forEach(function (el) { el.remove(); });
        }

        /* ── Lender results renderer ── */
        function popRenderLenders(result) {
            if (!result) return;

            if (result.type === 'collect_contact') {
                popDivaMsg('Hamara expert personally best option dhundega.<br>\uD83D\uDCDE <strong>+91 7976218596</strong>');
                popAddChips(['Dobara try karein', '\uD83D\uDCDE Call karein'], popHandleChip);
                return;
            }

            _lendersShown = true;
            var lenders = Array.isArray(result.lenders) ? result.lenders : [];
            var total   = result.total_matches || lenders.length;
            var profileLenders = lenders.filter(function(l){ return (l.match_reasons||[]).indexOf('Special profile match') > -1; }).slice(0,5);
            var rateLenders = lenders.filter(function(l){ return (l.match_reasons||[]).indexOf('Special profile match') === -1; }).slice(0,5);
            var hasPartition = profileLenders.length > 0;
            var displayLenders = hasPartition ? rateLenders : lenders.slice(0,10);

            if (!lenders.length) {
                popDivaMsg('Abhi exact match nahi mila.<br>\uD83D\uDCDE <strong>+91 7976218596</strong>');
                return;
            }

            if (hasPartition) { var sh=document.createElement('div');sh.className='pop-msg';sh.style.cssText='font-size:11px;font-weight:700;color:#1A3A6B;padding:6px 4px 2px;text-transform:uppercase;letter-spacing:.04em;';sh.textContent='Best Rates for Your Profile';popBody.appendChild(sh); }
            displayLenders.forEach(function (l, i) {
                var isTop = i < 3;
                var r    = parseFloat(l.roi_min || 0), rx = parseFloat(l.roi_max || 0);
                var roi  = (r > 0 && rx > 0 && rx !== r) ? r + '% \u2013 ' + rx + '%' : (r > 0 ? r + '%+' : 'On request');
                var ltv  = parseFloat(l.max_ltv || 0); var ltvS = ltv > 0 ? ltv + '%' : '\u2014';
                var ten  = l.max_tenure_months > 0 ? Math.round(l.max_tenure_months / 12) + ' yrs' : '\u2014';
                var name = l.lender_name || 'Lender ' + (i + 1);
                var score = l.score != null ? l.score : '';
                var aiR  = l.ai_reason || l.why || '';
                var notes = l.notes || '';
                var mgr  = l.sales_manager_name || '', mob = l.sales_manager_mobile || '';

                var card = document.createElement('div');
                card.className = 'pop-msg';

                var rankBadge = isTop
                    ? '<span style="display:inline-block;font-size:10px;font-weight:700;background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;padding:3px 10px;border-radius:100px;margin-bottom:6px;">'
                      + '#' + (i+1) + (score ? ' \u00B7 Score ' + score : '') + ' \u2605 Top Pick</span>'
                    : '<span style="font-size:10px;color:#94A3B8;margin-bottom:6px;display:inline-block;">'
                      + '#' + (i+1) + (score ? ' \u00B7 ' + score : '') + '</span>';

                card.innerHTML =
                    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;' +
                    'padding:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);position:relative;overflow:hidden;">' +
                        '<div style="position:absolute;left:0;top:0;bottom:0;width:4px;' +
                        'background:linear-gradient(180deg,' + (isTop ? '#F59E0B,#D97706' : '#1A3A6B,#2563EB') + ');"></div>' +
                        '<div style="padding-left:8px;">' +
                            rankBadge +
                            '<div style="font-size:14px;font-weight:700;color:#0A1628;margin-bottom:8px;">' + popEsc(name) + '</div>' +
                            '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:8px;">' +
                                '<div style="background:#f8fafc;border-radius:8px;padding:6px 8px;">' +
                                    '<div style="font-size:9.5px;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">ROI</div>' +
                                    '<div style="font-size:13px;font-weight:700;color:#1A3A6B;">' + popEsc(roi) + '</div></div>' +
                                '<div style="background:#f8fafc;border-radius:8px;padding:6px 8px;">' +
                                    '<div style="font-size:9.5px;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">Max LTV</div>' +
                                    '<div style="font-size:13px;font-weight:700;color:#F97316;">' + popEsc(ltvS) + '</div></div>' +
                                '<div style="background:#f8fafc;border-radius:8px;padding:6px 8px;">' +
                                    '<div style="font-size:9.5px;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">Tenure</div>' +
                                    '<div style="font-size:13px;font-weight:700;color:#1A3A6B;">' + popEsc(ten) + '</div></div>' +
                            '</div>' +
                            (aiR   ? '<div style="font-size:11.5px;color:#1e3a8a;background:#eff6ff;border-radius:7px;padding:7px 9px;border-left:3px solid #1e3a8a;margin-bottom:6px;">\uD83E\uDD16 ' + popEsc(aiR) + '</div>' : '') +
                            (notes ? '<div style="font-size:11px;color:#6b7280;font-style:italic;margin-bottom:4px;">\uD83D\uDCCC ' + popEsc(notes) + '</div>' : '') +
                            (mgr && mob ? '<div style="font-size:11.5px;color:#047857;background:#ecfdf5;border-radius:6px;padding:5px 8px;border-left:3px solid #10b981;">\uD83D\uDCDE <strong>' + popEsc(mgr) + '</strong> \u2014 ' + popEsc(mob) + '</div>' : '') +
                        '</div>' +
                    '</div>';
                popBody.appendChild(card);
            });

            if (hasPartition && profileLenders.length > 0) {
                var sh2=document.createElement('div');sh2.className='pop-msg';sh2.style.cssText='font-size:11px;font-weight:700;color:#059669;padding:6px 4px 2px;text-transform:uppercase;letter-spacing:.04em;';sh2.textContent='Your Profile Specialists';popBody.appendChild(sh2);
                profileLenders.forEach(function(l,i){
                    var r2=parseFloat(l.roi_min||0),rx2=parseFloat(l.roi_max||0);
                    var roi2=(r2>0&&rx2>0&&rx2!==r2)?r2+'% - '+rx2+'%':(r2>0?r2+'%+':'On request');
                    var ltv2=parseFloat(l.max_ltv||0); var ltvS2=ltv2>0?ltv2+'%':'--';
                    var ten2=l.max_tenure_months>0?Math.round(l.max_tenure_months/12)+' yrs':'--';
                    var name2=l.lender_name||'Lender '+(i+1);
                    var score2=l.score!=null?l.score:'';
                    var aiR2=l.ai_reason||l.why||'';
                    var notes2=(l.notes||'').substring(0,120);
                    var card2=document.createElement('div');card2.className='pop-msg';
                    var inner2=document.createElement('div');
                    inner2.style.cssText='background:#fff;border:1px solid #d1fae5;border-radius:12px;padding:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);position:relative;overflow:hidden;';
                    var stripe2=document.createElement('div');stripe2.style.cssText='position:absolute;left:0;top:0;bottom:0;width:4px;background:linear-gradient(180deg,#059669,#047857);';
                    var body2=document.createElement('div');body2.style.paddingLeft='8px';
                    body2.innerHTML='<span style="display:inline-block;font-size:10px;font-weight:700;background:linear-gradient(135deg,#059669,#047857);color:#fff;padding:3px 10px;border-radius:100px;margin-bottom:6px;">#'+(i+1)+(score2?' - Score '+String(score2):'')+' Specialist</span>'
                        +'<div style="font-size:14px;font-weight:700;color:#0A1628;margin-bottom:8px;">'+popEsc(name2)+'</div>'
                        +'<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:8px;">'
                        +'<div style="background:#f0fdf4;border-radius:8px;padding:6px 8px;"><div style="font-size:9.5px;color:#64748B;text-transform:uppercase;margin-bottom:2px;">ROI</div><div style="font-size:13px;font-weight:700;color:#1A3A6B;">'+popEsc(roi2)+'</div></div>'
                        +'<div style="background:#f0fdf4;border-radius:8px;padding:6px 8px;"><div style="font-size:9.5px;color:#64748B;text-transform:uppercase;margin-bottom:2px;">Max LTV</div><div style="font-size:13px;font-weight:700;color:#059669;">'+popEsc(ltvS2)+'</div></div>'
                        +'<div style="background:#f0fdf4;border-radius:8px;padding:6px 8px;"><div style="font-size:9.5px;color:#64748B;text-transform:uppercase;margin-bottom:2px;">Tenure</div><div style="font-size:13px;font-weight:700;color:#1A3A6B;">'+popEsc(ten2)+'</div></div>'
                        +'</div>'
                        +(aiR2?'<div style="font-size:11.5px;color:#065f46;background:#ecfdf5;border-radius:7px;padding:7px 9px;border-left:3px solid #059669;margin-bottom:6px;">'+popEsc(aiR2)+'</div>':'')
                        +(notes2?'<div style="font-size:11px;color:#6b7280;font-style:italic;margin-bottom:4px;">'+popEsc(notes2)+'</div>':'');
                    inner2.appendChild(stripe2);inner2.appendChild(body2);card2.appendChild(inner2);popBody.appendChild(card2);
                });
            }
            var shownCount = hasPartition ? (displayLenders.length + profileLenders.length) : displayLenders.length;
            if (total > shownCount) {
                var fn = document.createElement('p');
                fn.className = 'pop-msg';
                fn.style.cssText = 'text-align:center;font-size:11.5px;color:#94A3B8;margin:4px 0;';
                fn.textContent = '+' + (total - shownCount) + ' more lenders matched';
                popBody.appendChild(fn);
            }
            popScroll();
        }

        /* ── API call ── */
        async function popCallAPI(message, isInit) {
            if (isThinking) return;
            isThinking = true;
            popShowTyping();
            var data = null;
            try {
                var res = await fetch(API_URL, {
                    method : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body   : JSON.stringify({
                        message    : message,
                        history    : popHistory.slice(-16),
                        profile    : popProfile,
                        session_id : popSid,
                        mode       : isInit ? 'welcome_mode' : popMode,
                    }),
                });
                if (res.ok) data = await res.json();
            } catch (e) { console.error('[Diva popup]', e); }
            isThinking = false;
            popRemoveTyping();
            if (!data || !data.success) {
                popDivaMsg('Kuch issue aa gaya. \uD83D\uDE14<br>Please try again ya call karein: \uD83D\uDCDE <strong>+91 7976218596</strong>');
                return;
            }
            popRender(data);
        }

        /* ── Render API response ── */
        function popRender(data) {
            if (data.mode)    popMode    = data.mode;
            if (data.profile && typeof data.profile === 'object') Object.assign(popProfile, data.profile);

            if (data.message) {
                popHistory.push({ role: 'assistant', content: data.message });
                popDivaMsg(popEsc(data.message).replace(/\n/g, '<br>'));
            }

            /* Lender results */
            if (data.lender_result) {
                popRenderLenders(data.lender_result);
                if (Array.isArray(data.chips) && data.chips.length) {
                    setTimeout(function () { popAddChips(data.chips, popHandleChip); }, 400);
                }
                if (inputEl) inputEl.placeholder = 'Kuch aur poochhein\u2026';
                return;
            }

            /* Chips + input */
            if (Array.isArray(data.chips) && data.chips.length) {
                popAddChips(data.chips, popHandleChip);
                if (inputEl) inputEl.placeholder = data.input_type === 'chips' ? 'Upar se option chunein\u2026' : 'Ya yahan type karein\u2026';
            } else {
                if (inputEl) { inputEl.placeholder = 'Yahan type karein\u2026'; inputEl.focus(); }
            }
        }

        /* ── Chip click ── */
        function popHandleChip(label) {
            popRemoveChips();
            popUserMsg(label);
            popHistory.push({ role: 'user', content: label });
            popCallAPI(label, false);
        }

        /* ── Text send ── */
        function popHandleTextSend() {
            if (!inputEl) return;
            var text = inputEl.value.trim();
            if (!text || isThinking) return;
            inputEl.value = '';
            if (_lendersShown) {
                _lendersShown = false;
                popRemoveChips();
                popUserMsg(text);
                popHistory.push({ role: 'user', content: text });
                popProfile._extra_notes = (popProfile._extra_notes || '') + ' ' + text;
                popProfile.notes = popProfile._extra_notes;
                popProfile._quote_step = 'final_search';
                popDivaMsg('Note add ho gayi! Lenders dhundh raha hoon...');
                setTimeout(function(){ popCallAPI('find best lenders', false); }, 300);
                return;
            }
            popRemoveChips();
            popUserMsg(text);
            popHistory.push({ role: 'user', content: text });
            popCallAPI(text, false);
        }

        /* ── Events ── */
        launcher.addEventListener('click', function (e) {
            if (e.target.closest('.dv-launcher__closeBtn') || e.target.closest('.dv-launcher__x')) return;
            openPopup();
        });

        launcher.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPopup(); }
        });

        if (launcherX) {
            launcherX.addEventListener('click', function (e) {
                e.stopPropagation();
                launcher.style.display = 'none';
            });
        }

        if (closeBtn) closeBtn.addEventListener('click',  closePopup);
        if (overlay)  overlay.addEventListener('click',   closePopup);
        if (sendBtn)  sendBtn.addEventListener('click',   popHandleTextSend);
        if (inputEl)  inputEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') popHandleTextSend(); });

        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && isOpen) closePopup(); });

        /* Expose for wmOpenDiva */
        window._dvOpenPopup = openPopup;
    }

})();