const hdr=document.getElementById("hdr");
const sBar=document.getElementById("stickyBar");
window.addEventListener("scroll",()=>{
  hdr.classList.toggle("scrolled",scrollY>40);
  sBar.classList.toggle("visible",scrollY>500);
});

const mobBtn=document.getElementById("mobBtn");
const hNav=document.getElementById("hNav");
mobBtn.onclick=()=>{
  hNav.classList.toggle("open");
  mobBtn.querySelector("i").className=hNav.classList.contains("open")?"fas fa-times":"fas fa-bars";
};
document.querySelectorAll(".hdr-nav a").forEach(a=>a.addEventListener("click",()=>{
  hNav.classList.remove("open");
  mobBtn.querySelector("i").className="fas fa-bars";
}));

const navAs=document.querySelectorAll(".hdr-nav a");
window.addEventListener("scroll",()=>{
  let cur="";
  document.querySelectorAll("section[id]").forEach(s=>{if(scrollY>=s.offsetTop-100)cur=s.id});
  navAs.forEach(a=>{a.classList.remove("active");if(a.getAttribute("href")==="#"+cur)a.classList.add("active")});
});

const obs=new IntersectionObserver(e=>e.forEach(x=>{if(x.isIntersecting)x.target.classList.add("in")}),{threshold:.08});
document.querySelectorAll(".fade-up").forEach(el=>obs.observe(el));

function toggleFaq(btn){
  const item=btn.parentElement;
  const isOpen=item.classList.contains("active");
  document.querySelectorAll(".faq-item.active").forEach(i=>i.classList.remove("active"));
  if(!isOpen)item.classList.add("active");
}

/* ── EMI CALCULATOR ───────────────────────────────────── */
/* EMI calculator is managed by js/wm-core.js */

/* ── JOURNEY ANIMATION ───────────────────────────────── */
(function initJourney() {
  var orb   = document.getElementById('journeyOrb');
  var cards = [
    document.getElementById('wm-stage-1'),
    document.getElementById('wm-stage-2'),
    document.getElementById('wm-stage-3'),
    document.getElementById('wm-stage-4'),
  ];
  if (!orb || cards.some(function(c){return !c;})) return;
  var cur = 0;
  function getOrbTarget(card) {
    var wrap  = document.getElementById('journeyWrap');
    if (!wrap) return {top:0,left:0};
    var wRect = wrap.getBoundingClientRect();
    var cRect = card.getBoundingClientRect();
    var node  = card.querySelector('.wm-node');
    var nRect = node ? node.getBoundingClientRect() : cRect;
    return {
      top:  nRect.top  - wRect.top  + nRect.height / 2 - 7,
      left: nRect.left - wRect.left + nRect.width  / 2 - 7,
    };
  }
  function activate(idx) {
    cards.forEach(function(card,i){
      card.classList.remove('wm-active','wm-done');
      if(i < idx)  card.classList.add('wm-done');
      if(i === idx) card.classList.add('wm-active');
    });
    var pos = getOrbTarget(cards[idx]);
    orb.style.top  = pos.top  + 'px';
    orb.style.left = pos.left + 'px';
  }
  orb.style.transition = 'none';
  var init = getOrbTarget(cards[0]);
  orb.style.top  = init.top  + 'px';
  orb.style.left = init.left + 'px';
  cards[0].classList.add('wm-active');
  requestAnimationFrame(function(){
    requestAnimationFrame(function(){
      orb.style.transition = 'top .65s cubic-bezier(.4,0,.2,1), left .65s cubic-bezier(.4,0,.2,1)';
    });
  });
  setInterval(function(){
    cur = (cur + 1) % cards.length;
    activate(cur);
  }, 2400);
})();

/* ── LEAD FORM SUBMIT ──────────────────────── */
async function submitLead(e){
  e.preventDefault();
  const form=e.target;
  const btn=form.querySelector("[type=submit]");
  const orig=btn.innerHTML;
  btn.innerHTML="<i class='fas fa-spinner fa-spin'></i> Sending...";
  btn.disabled=true;
  const q=function(n){return form.querySelector("[name="+n+"]");};
  const data={
    source:"contact_form",
    name:   q("name")?.value||"",
    phone:  q("phone")?.value||"",
    email:  q("email")?.value||"",
    loan_type:q("loan_type")?.value||"",
    city:   q("city")?.value||"",
    message:q("message")?.value||"",
    utm:window.location.search
  };
  try{
    const res=await fetch("/api/save_lead.php",{method:"POST",
      headers:{"Content-Type":"application/json"},body:JSON.stringify(data)});
    const j=await res.json();
    if(j.ok){
      form.reset();
      btn.innerHTML="&#10003; Sent! We'll call you soon";
      btn.style.background="#22c55e";
      setTimeout(()=>{btn.innerHTML=orig;btn.disabled=false;btn.style.background="";},4000);
    }else throw new Error(j.error||"Failed");
  }catch(err){
    btn.innerHTML="Error - try WhatsApp";
    btn.disabled=false;
    btn.style.background="#dc2626";
    setTimeout(()=>{btn.innerHTML=orig;btn.style.background="";},3000);
  }
}

/* ── NOTE ──────────────────────────────────────────────────
   openDivaWidget / closeDivaWidget are defined inside the
   Diva IIFE in index.html (window.openDivaWidget = openPanel).
   DO NOT redefine them here — that was the root cause of the
   "buttons not working" bug.
   .open-diva-trigger click handling is also done in index.html.
────────────────────────────────────────────────────────── */