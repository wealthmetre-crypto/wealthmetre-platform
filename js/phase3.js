/**
 * WealthMetre — phase3.js
 * Merged version:
 *   1. Hero rotating typewriter
 *   2. Hero Diva fixed-height auto-demo  ← UPDATED (new dv-* classes + IDs)
 *   3. Lender network animation
 *   4. AI Eligibility Calculator
 *
 * Load: <script src="js/phase3.js" defer></script>
 */

(function () {
  'use strict';
  if (window._phase3Loaded) return;
  window._phase3Loaded = true;

  document.addEventListener('DOMContentLoaded', function () {
    initHeroTypewriter();
    initHeroDiva();
    initLenderNetwork();
    initAICalc();
  });

  /* ═══════════════════════════════════════════════
     1 — HERO TYPEWRITER
     Targets: #wmTypeWord
     Colors controlled by CSS (.wm-hl--orange etc.)
  ═══════════════════════════════════════════════ */
  function initHeroTypewriter() {
    var elWord = document.getElementById('wmTypeWord');
    if (!elWord) return;

    var words = [
      'Personal Loan',
      'Business Loan',
      'Home Loan',
      'Loan Against Property'
    ];

    var wIdx     = 0;
    var cIdx     = 0;
    var deleting = false;
    var paused   = false;

    var T_TYPE  = 70;
    var T_DEL   = 40;
    var T_PAUSE = 1900;
    var T_NEXT  = 380;

    function tick() {
      var word = words[wIdx];

      if (paused) {
        paused   = false;
        deleting = true;
        setTimeout(tick, T_PAUSE);
        return;
      }

      if (!deleting) {
        cIdx++;
        elWord.textContent = word.slice(0, cIdx);
        if (cIdx === word.length) {
          paused = true;
          setTimeout(tick, 50);
        } else {
          setTimeout(tick, T_TYPE);
        }
      } else {
        cIdx--;
        elWord.textContent = word.slice(0, cIdx);
        if (cIdx === 0) {
          deleting = false;
          wIdx = (wIdx + 1) % words.length;
          setTimeout(tick, T_NEXT);
        } else {
          setTimeout(tick, T_DEL);
        }
      }
    }

    setTimeout(tick, 900);
  }


  /* ═══════════════════════════════════════════════
     2 — HERO DIVA FIXED-HEIGHT AUTO-DEMO
     ─────────────────────────────────────────────
     UPDATED: Uses new element IDs and dv-* CSS classes.

     Targets (HTML Block A-1):
       #dvHeroStage    — screen swap area
       #dvHeroProgress — progress bar fill div
       #dvHeroDots     — step dots container

     CSS classes (from phase3.css dv-* section):
       Screens   : .dv-screen  →  .is-visible / .is-exit
       Message   : .dv-msgRow  .dv-av  .dv-bubble  .dm
       Reply     : .dv-replyRow  .dv-replyBubble
       Options   : .dv-opts  .dv-opts.g2  .dv-opt  .oi  .chosen
       Searching : .dv-searching  .dv-ring  .dv-ring__out  .dv-ring__in
                   .dv-ring__logo  .dv-s-title  .dv-s-lines  .dv-s-line
                   .ck  .dv-s-dots
       Result    : .dv-resultScreen  .dv-card  .dv-card__head
                   .dv-card__bank  .dv-card__bankIcon  .dv-card__bankName
                   .dv-card__badges  .dv-badge  .dv-badge--green  .dv-badge--blue
                   .dv-card__grid  .dv-card__item  .dv-card__lbl  .dv-card__val
                   .dv-card__val.green  .dv-card__val.orange  .dv-card__sub
                   .dv-card__btns  .dv-card__btn1  .dv-card__btn2  .dv-footnote
       Dots      : .dv-dot  .dv-dot.active  .dv-dot.done
  ═══════════════════════════════════════════════ */
  function initHeroDiva() {
    var stage = document.getElementById('dvHeroStage');
    var prog  = document.getElementById('dvHeroProgress');
    var dots  = document.getElementById('dvHeroDots');

    if (!stage || !prog || !dots) return;

    /* Ask Diva logo path — change if you move the file */
    var DIVA_IMG   = '/images/ask-diva.png';
    var TOTAL_DOTS = 7;
    var active     = null;

    /* ── Auto-demo sequence ──────────────────────────
       Each frame:
         msg      – Diva bubble HTML
         opts     – option buttons  { i: emoji, l: label }
         chosen   – index auto-highlighted
         reply    – user reply bubble text
         progress – progress bar %
         dot      – active step dot (0-based)
         noReply  – skip user reply on this frame
         delay    – ms from reply → next frame
         type     – 'searching' | 'result' | (default question)
         duration – ms the searching screen stays
    ─────────────────────────────────────────────── */
    var FRAMES = [
      /* 0 — Welcome */
      {
        msg:
          '<strong>Namaste! Main Diva hoon \uD83D\uDE4F</strong><br>' +
          'WealthMetre ki AI Loan Advisor.<br>' +
          '<span class="dm">140+ banks &amp; NBFCs mein se perfect match dhundh ke dungi — bilkul free.</span><br><br>' +
          '<strong>Kaunsa loan chahiye aapko?</strong>',
        opts: [
          { i: '\uD83C\uDFE0', l: 'Home Loan' },
          { i: '\uD83C\uDFD7\uFE0F', l: 'Loan Against Property' },
          { i: '\uD83D\uDCBC', l: 'Business Loan' },
          { i: '\uD83D\uDCB3', l: 'Personal Loan' },
          { i: '\uD83D\uDE97', l: 'Car Loan' }
        ],
        chosen:   0,
        reply:    '\uD83C\uDFE0 Home Loan',
        progress: 8,
        dot:      0,
        noReply:  true,
        delay:    1300
      },
      /* 1 — City */
      {
        msg:
          '<strong>Badhiya choice! \uD83C\uDFE1</strong><br>' +
          'Aap konse <strong>city</strong> mein hain?',
        opts: [
          { i: '\uD83C\uDFD9\uFE0F', l: 'Jaipur' },
          { i: '\uD83C\uDF06', l: 'Delhi NCR' },
          { i: '\uD83C\uDFE2', l: 'Mumbai' },
          { i: '\uD83C\uDF07', l: 'Bangalore' },
          { i: '\uD83D\uDCCD', l: 'Other City' }
        ],
        chosen:   0,
        reply:    '\uD83C\uDFD9\uFE0F Jaipur',
        progress: 24,
        dot:      1,
        delay:    1200
      },
      /* 2 — CIBIL */
      {
        msg:
          '<strong>Aapka CIBIL score kya hai?</strong><br>' +
          '<span class="dm">Ye lender matching ke liye zaroori hai.</span>',
        opts: [
          { i: '\uD83D\uDFE2', l: '750+  (Excellent)' },
          { i: '\uD83D\uDFE1', l: '700 \u2013 749  (Good)' },
          { i: '\uD83D\uDFE0', l: '650 \u2013 699  (Average)' },
          { i: '\uD83D\uDD34', l: 'Below 650' },
          { i: '\u2753',       l: 'Mujhe pata nahi' }
        ],
        chosen:   0,
        reply:    '\uD83D\uDFE2 750+  (Excellent)',
        progress: 42,
        dot:      2,
        delay:    1200
      },
      /* 3 — Loan amount */
      {
        msg: '<strong>Kitne loan ki zaroorat hai?</strong>',
        opts: [
          { i: '\uD83D\uDCB0', l: 'Up to \u20B925 Lakh' },
          { i: '\uD83D\uDCB0', l: '\u20B925L \u2013 \u20B975L' },
          { i: '\uD83D\uDCB0', l: '\u20B975L \u2013 \u20B92 Crore' },
          { i: '\uD83D\uDCB0', l: '\u20B92 Crore+' }
        ],
        chosen:   1,
        reply:    '\uD83D\uDCB0 \u20B940 Lakh  (\u20B925L \u2013 \u20B975L)',
        progress: 58,
        dot:      3,
        delay:    1200
      },
      /* 4 — Employment */
      {
        msg: '<strong>Aap professionally kya karte hain?</strong>',
        opts: [
          { i: '\uD83C\uDFE2', l: 'Salaried' },
          { i: '\uD83C\uDFD7\uFE0F', l: 'Self Employed' },
          { i: '\uD83D\uDC54', l: 'Business Owner' },
          { i: '\uD83D\uDCCB', l: 'Professional (CA / Doctor)' }
        ],
        chosen:   0,
        reply:    '\uD83C\uDFE2 Salaried',
        progress: 74,
        dot:      4,
        delay:    1200
      },
      /* 5 — Searching */
      {
        type:     'searching',
        progress: 90,
        dot:      5,
        duration: 3300
      },
      /* 6 — Result */
      {
        type:     'result',
        progress: 100,
        dot:      6
      }
    ];


    /* ── Render step indicator dots ──────────────── */
    function renderDots(activeDot) {
      var html = '';
      for (var i = 0; i < TOTAL_DOTS; i++) {
        var cls;
        if      (i < activeDot)  cls = 'dv-dot done';
        else if (i === activeDot) cls = 'dv-dot active';
        else                      cls = 'dv-dot';
        html += '<div class="' + cls + '"></div>';
      }
      dots.innerHTML = html;
    }


    /* ── Build a screen element for the given frame ─ */
    function buildScreen(frame) {
      var d = document.createElement('div');
      d.className = 'dv-screen'; /* starts invisible via CSS opacity:0 */

      /* ── SEARCHING ── */
      if (frame.type === 'searching') {
        d.innerHTML =
          '<div class="dv-searching">' +
            '<div class="dv-ring">' +
              '<div class="dv-ring__out"></div>' +
              '<div class="dv-ring__in"></div>' +
              '<img src="' + DIVA_IMG + '" alt="" class="dv-ring__logo" draggable="false" ' +
                'onerror="this.style.display=\'none\'" />' +
            '</div>' +
            '<div class="dv-s-title">Best lenders dhundh rahi hoon\u2026</div>' +
            '<div class="dv-s-lines">' +
              '<div class="dv-s-line"><span class="ck">\u2713</span>Checking 140+ lender policies\u2026</div>' +
              '<div class="dv-s-line"><span class="ck">\u2713</span>Comparing ROI, CIBIL fit &amp; LTV\u2026</div>' +
              '<div class="dv-s-line"><span class="ck">\u2713</span>Calculating approval chances\u2026</div>' +
            '</div>' +
            '<div class="dv-s-dots"><span></span><span></span><span></span></div>' +
          '</div>';
        return d;
      }

      /* ── RESULT ── */
      if (frame.type === 'result') {
        d.innerHTML =
          '<div class="dv-resultScreen">' +

            /* Diva message */
            '<div class="dv-msgRow">' +
              '<div class="dv-av">' +
                '<img src="' + DIVA_IMG + '" alt="Diva" draggable="false" ' +
                  'onerror="this.parentNode.style.background=\'linear-gradient(135deg,#1A3A6B,#2563EB)\'" />' +
              '</div>' +
              '<div class="dv-bubble">' +
                '<strong>\u2705 Best match mil gayi!</strong><br>' +
                '<span class="dm">Aapki profile ke liye top recommendation:</span>' +
              '</div>' +
            '</div>' +

            /* Result card */
            '<div class="dv-card">' +
              '<div class="dv-card__head">' +
                '<div class="dv-card__bank">' +
                  '<div class="dv-card__bankIcon">\uD83C\uDFE6</div>' +
                  '<span class="dv-card__bankName">HDFC Bank</span>' +
                '</div>' +
                '<div class="dv-card__badges">' +
                  '<span class="dv-badge dv-badge--green">Best Match</span>' +
                  '<span class="dv-badge dv-badge--blue">92% Fit</span>' +
                '</div>' +
              '</div>' +

              '<div class="dv-card__grid">' +
                '<div class="dv-card__item">' +
                  '<span class="dv-card__lbl">Interest Rate</span>' +
                  '<span class="dv-card__val green">8.40%</span>' +
                  '<span class="dv-card__sub">per annum</span>' +
                '</div>' +
                '<div class="dv-card__item">' +
                  '<span class="dv-card__lbl">Max LTV</span>' +
                  '<span class="dv-card__val">80%</span>' +
                  '<span class="dv-card__sub">of property value</span>' +
                '</div>' +
                '<div class="dv-card__item">' +
                  '<span class="dv-card__lbl">Processing Fee</span>' +
                  '<span class="dv-card__val">0.5%</span>' +
                  '<span class="dv-card__sub">one-time</span>' +
                '</div>' +
                '<div class="dv-card__item">' +
                  '<span class="dv-card__lbl">Approval Time</span>' +
                  '<span class="dv-card__val orange">3\u20135</span>' +
                  '<span class="dv-card__sub">working days</span>' +
                '</div>' +
              '</div>' +

              '<div class="dv-card__btns">' +
                '<button class="dv-card__btn1">Apply Now \u2192</button>' +
                '<button class="dv-card__btn2">See All Matches</button>' +
              '</div>' +
            '</div>' +

            '<div class="dv-footnote">+12 more lenders matched \u00B7 Talk to an expert today</div>' +
          '</div>';
        return d;
      }

      /* ── QUESTION (default) ── */
      var optClass = (frame.opts && frame.opts.length === 4)
        ? 'dv-opts g2'
        : 'dv-opts';

      var optsHtml = '';
      for (var i = 0; i < frame.opts.length; i++) {
        optsHtml +=
          '<div class="dv-opt" data-idx="' + i + '">' +
            '<span class="oi">' + frame.opts[i].i + '</span>' +
            frame.opts[i].l +
          '</div>';
      }

      d.innerHTML =
        '<div class="dv-msgRow">' +
          '<div class="dv-av">' +
            '<img src="' + DIVA_IMG + '" alt="Diva" draggable="false" ' +
              'onerror="this.parentNode.style.background=\'linear-gradient(135deg,#1A3A6B,#2563EB)\'" />' +
          '</div>' +
          '<div class="dv-bubble">' + frame.msg + '</div>' +
        '</div>' +
        '<div class="' + optClass + '">' + optsHtml + '</div>';

      return d;
    }


    /* ── Swap to new screen with cinematic transition ── */
    /*
 * ================================================================
 * PASTE THIS INTO: js/phase3.js
 * LOCATION: Inside initHeroDiva(), replace the existing swap() function.
 *
 * Find this line in your phase3.js:
 *   function swap(frame) {
 *
 * Replace that entire swap() function (from "function swap(frame) {"
 * down to its closing "}") with the version below.
 *
 * This is the ONLY change needed in phase3.js.
 * ================================================================
 */

    function swap(frame) {
      prog.style.width = frame.progress + '%';
      renderDots(frame.dot);

      /* ── NEW: Collapse the featured Ask Diva image on first screen swap ──
         The featured image (.dv-featured) is shown on load.
         The moment the demo runs its first screen (any screen),
         the image smoothly collapses (height → 0) giving full space
         to the demo content. It only collapses once. */
      var featured = document.getElementById('dvFeaturedImg');
      if (featured && !featured.classList.contains('is-gone')) {
        /* Small delay so user sees the first screen build-in before image leaves */
        setTimeout(function () {
          featured.classList.add('is-gone');
        }, 600);
      }
      /* ── END NEW ── */

      /* Exit the current screen */
      if (active) {
        var old = active;
        old.classList.remove('is-visible');
        old.classList.add('is-exit');
        setTimeout(function () {
          if (old && old.parentNode) old.parentNode.removeChild(old);
        }, 420);
      }

      /* Build and enter new screen */
      var s = buildScreen(frame);
      stage.appendChild(s);

      /* Double rAF ensures CSS transition fires after DOM insert */
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          s.classList.add('is-visible');
        });
      });

      active = s;
    }


    /* ── Auto-demo loop ─────────────────────────────
       Timeline per question frame:
         t = 0        → question + options appear
         t = 1500ms   → chosen option highlights
         t = 2000ms   → user reply bubble appears
         t = 2000+delay → advance to next frame
    ─────────────────────────────────────────────── */
    function run(idx) {
      /* After last frame → wait then restart */
      if (idx >= FRAMES.length) {
        setTimeout(function () { run(0); }, 5000);
        return;
      }

      var frame = FRAMES[idx];
      swap(frame);

      /* Searching: auto-advance after duration */
      if (frame.type === 'searching') {
        setTimeout(function () { run(idx + 1); }, frame.duration || 3000);
        return;
      }

      /* Result: stay, then restart loop */
      if (frame.type === 'result') {
        setTimeout(function () { run(idx + 1); }, 5400);
        return;
      }

      var T_HIGHLIGHT = 1500;
      var T_REPLY     = 500;

      setTimeout(function () {
        if (!active) return;

        /* Highlight the chosen option */
        var opts = active.querySelectorAll('.dv-opt');
        if (opts[frame.chosen]) {
          opts[frame.chosen].classList.add('chosen');
        }

        setTimeout(function () {
          /* Append user reply bubble */
          if (!frame.noReply && frame.reply && active) {
            var r     = document.createElement('div');
            r.className = 'dv-replyRow';
            r.innerHTML = '<div class="dv-replyBubble">' + frame.reply + '</div>';
            active.appendChild(r);
          }

          /* Advance to next frame */
          setTimeout(function () { run(idx + 1); }, frame.delay || 1200);
        }, T_REPLY);

      }, T_HIGHLIGHT);
    }


    /* ── Boot ──────────────────────────────────────── */
    renderDots(0);
    prog.style.width = '4%';
    run(0);
  }


  /* ═══════════════════════════════════════════════
     3 — LENDER NETWORK ANIMATION
     (unchanged from original phase3.js)
  ═══════════════════════════════════════════════ */
  function initLenderNetwork() {
    var canvas = document.getElementById('lvwCanvas');
    if (!canvas) return;

    var NODES = [
      { n: 'HDFC',     x: 50, y: 16 },
      { n: 'HSBC',     x: 78, y: 30 },
      { n: 'IDFC',     x: 86, y: 55 },
      { n: 'Axis',     x: 78, y: 78 },
      { n: 'Bajaj',    x: 55, y: 88 },
      { n: 'IIFL',     x: 30, y: 84 },
      { n: 'PNB',      x: 16, y: 64 },
      { n: 'Tata',     x: 12, y: 40 },
      { n: 'AU',       x: 24, y: 20 },
      { n: 'Chola',    x: 50, y: 6  },
      { n: 'IndusInd', x: 90, y: 42 },
      { n: 'Godrej',   x: 8,  y: 54 }
    ];

    NODES.forEach(function (l, i) {
      var node      = document.createElement('div');
      node.className = 'lvw-node';
      node.style.left = l.x + '%';
      node.style.top  = l.y + '%';
      node.id         = 'lvwNode' + i;
      node.innerHTML  =
        '<div class="lvw-nd">' + l.n + '</div>' +
        '<div class="lvw-nl">' + l.n + '</div>';
      canvas.appendChild(node);
    });

    var matched = new Set();

    setInterval(function () {
      var avail = NODES.map(function (_, i) { return i; }).filter(function (i) {
        return !matched.has(i);
      });

      if (!avail.length) { matched.clear(); return; }

      var pick = avail[Math.floor(Math.random() * avail.length)];
      matched.add(pick);

      var node = document.getElementById('lvwNode' + pick);
      if (node) {
        node.classList.add('active');
        setTimeout(function () { if (node) node.classList.remove('active'); }, 2000);
      }

      var mc = document.getElementById('lvwMatchCount');
      if (mc) mc.textContent = matched.size;
    }, 550);

    var LENDER_NAMES = [
      'HDFC Bank', 'HSBC', 'Bajaj Finserv', 'Axis Bank',
      'IDFC FIRST', 'Tata Capital', 'PNB Housing', 'AU Bank',
      'Cholamandalam', 'IIFL', 'IndusInd', 'Godrej Capital'
    ];

    var scanIdx  = 0;
    var scanPct  = 0;
    var scanTxt  = document.getElementById('lvwScanTxt');
    var scanFill = document.getElementById('lvwScanFill');
    var scanPctEl = document.getElementById('lvwScanPct');

    function runScan() {
      scanPct = 0;
      if (scanTxt) {
        scanTxt.textContent = 'Scanning ' + LENDER_NAMES[scanIdx % LENDER_NAMES.length] + ' policy\u2026';
      }
      scanIdx++;

      (function tick() {
        scanPct += Math.random() * 14 + 4;
        if (scanPct > 100) scanPct = 100;
        var r = Math.round(scanPct);
        if (scanFill)  scanFill.style.width  = r + '%';
        if (scanPctEl) scanPctEl.textContent  = r + '%';
        if (scanPct < 100) setTimeout(tick, 60 + Math.random() * 80);
        else setTimeout(runScan, 350);
      })();
    }

    runScan();
  }


  /* ═══════════════════════════════════════════════
     4 — AI ELIGIBILITY CALCULATOR
     (unchanged from original phase3.js)
  ═══════════════════════════════════════════════ */
  function initAICalc() {
    var incSlider  = document.getElementById('aciIncome');
    var cibilSlider = document.getElementById('aciCibil');
    var amtSlider  = document.getElementById('aciAmount');
    var emiSlider  = document.getElementById('aciEmi');
    var loanType   = document.getElementById('aciLoanType');

    if (!incSlider || !cibilSlider || !amtSlider || !emiSlider || !loanType) return;

    var LENDERS = [
      { n: 'HSBC Bank',     r: 8.25,  minC: 720, badge: 'Best rate',      bc: 'green' },
      { n: 'HDFC Bank',     r: 8.50,  minC: 700, badge: 'Fast process',   bc: 'green' },
      { n: 'IDFC FIRST',    r: 8.75,  minC: 680, badge: 'High LTV',       bc: 'green' },
      { n: 'Axis Bank',     r: 9.00,  minC: 660, badge: 'Quick disbursal',bc: 'green' },
      { n: 'Bajaj Finserv', r: 9.25,  minC: 640, badge: 'Low CIBIL OK',   bc: 'amber' },
      { n: 'Tata Capital',  r: 9.50,  minC: 620, badge: '',               bc: ''      },
      { n: 'PNB Housing',   r: 9.75,  minC: 600, badge: 'NBFC option',    bc: 'amber' }
    ];

    function fmtINR(n) {
      if (n >= 10000000) return '\u20B9' + (Math.round(n / 1000000) / 10) + 'Cr';
      if (n >= 100000)   return '\u20B9' + (Math.round(n / 10000)   / 10) + 'L';
      return '\u20B9' + Math.round(n).toLocaleString('en-IN');
    }

    function calcEMI(P, annualRate, years) {
      var r = annualRate / 12 / 100;
      var n = years * 12;
      if (!r) return P / n;
      return P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
    }

    function getParams(lt) {
      if (lt === 'personal') return { rate: 12, years: 5  };
      if (lt === 'business') return { rate: 10, years: 5  };
      if (lt === 'lap')      return { rate: 9,  years: 15 };
      return                        { rate: 8.5,years: 20 };
    }

    function calcApproval(inc, cibil, amt, existEmi, params) {
      var maxAffordable = inc * 0.50 - existEmi;
      var monthlyEMI    = calcEMI(amt, params.rate, params.years);
      var foir          = Math.round((existEmi + monthlyEMI) / inc * 100);
      var eligAmt       = Math.max(
        0,
        maxAffordable / (params.rate / 12 / 100 + 1 / (params.years * 12)) * 0.9
      );

      var score = 0;

      if      (cibil >= 750) score += 40;
      else if (cibil >= 720) score += 34;
      else if (cibil >= 700) score += 26;
      else if (cibil >= 650) score += 14;
      else                   score += 5;

      if      (amt <= eligAmt * 1.05) score += 35;
      else if (amt <= eligAmt * 1.25) score += 20;
      else if (amt <= eligAmt * 1.5 ) score += 10;
      else                            score += 2;

      if      (foir < 35) score += 25;
      else if (foir < 45) score += 18;
      else if (foir < 55) score += 10;
      else                score += 3;

      var pct     = Math.min(95, Math.max(4, Math.round(score)));
      var matches = Math.round((pct / 100) * 30);
      var note;

      if      (pct >= 72) note = 'Strong profile \u2014 ' + matches + ' lenders very likely to approve.';
      else if (pct >= 48) note = 'Moderate profile \u2014 ' + matches + ' lenders may approve. Reducing loan helps.';
      else                note = 'Low likelihood \u2014 improve CIBIL or reduce loan amount.';

      return { pct: pct, matches: matches, note: note, foir: foir, elig: Math.round(eligAmt) };
    }

    function setText(id, val) {
      var el = document.getElementById(id);
      if (el) el.textContent = val;
    }

    function setStyle(id, prop, val) {
      var el = document.getElementById(id);
      if (el) el.style[prop] = val;
    }

    function update() {
      var inc   = +incSlider.value;
      var cibil = +cibilSlider.value;
      var amt   = +amtSlider.value;
      var emi   = +emiSlider.value;
      var lt    = loanType.value;
      var p     = getParams(lt);

      setText('aciIncomeVal', '\u20B9' + inc.toLocaleString('en-IN'));
      setText('aciCibilVal',  cibil);
      setText('aciAmountVal', fmtINR(amt));
      setText('aciEmiVal',    emi > 0 ? '\u20B9' + emi.toLocaleString('en-IN') : '\u20B90');

      var monthlyEMI = calcEMI(amt, p.rate, p.years);
      var totalPay   = monthlyEMI * p.years * 12;
      var apr        = calcApproval(inc, cibil, amt, emi, p);
      var col        = apr.pct >= 70 ? '#16a34a' : apr.pct >= 45 ? '#d97706' : '#dc2626';

      setText('acrPct',     apr.pct + '%');
      setStyle('acrPct',    'color', col);
      setStyle('acrBar',    'width', apr.pct + '%');
      setStyle('acrBar',    'background', col);
      setText('acrNote',    apr.note);
      setText('acrMatches', apr.matches);

      setText('acrEMI',  '\u20B9' + Math.round(monthlyEMI).toLocaleString('en-IN'));
      setText('acrInt',  fmtINR(Math.round(totalPay - amt)));
      setText('acrFOIR', apr.foir + '%');
      setStyle('acrFOIR', 'color', apr.foir > 55 ? '#dc2626' : apr.foir > 45 ? '#d97706' : '#16a34a');
      setText('acrElig', fmtINR(apr.elig));

      var el = document.getElementById('acrLenders');
      if (!el) return;

      var eligible = LENDERS.filter(function (l) { return cibil >= l.minC; }).slice(0, 3);

      if (!eligible.length) {
        el.innerHTML = '<div class="acr-empty">Improve CIBIL to 600+ for lender matches.</div>';
        return;
      }

      el.innerHTML = eligible.map(function (l) {
        return (
          '<div class="acr-lender-row">' +
            '<div>' +
              '<div class="acr-lname">' + l.n + '</div>' +
              (l.badge ? '<span class="acr-lbadge ' + l.bc + '">' + l.badge + '</span>' : '') +
            '</div>' +
            '<div class="acr-lrate">' + l.r + '%+</div>' +
          '</div>'
        );
      }).join('');
    }

    [incSlider, cibilSlider, amtSlider, emiSlider].forEach(function (sl) {
      sl.addEventListener('input', update);
    });

    loanType.addEventListener('change', update);
    update();
  }

})();