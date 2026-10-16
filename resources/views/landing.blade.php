{{-- Temporary pre-launch demo landing page, served at "/". Replace when the official site launches. --}}
@verbatim
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Crect width=%2764%27 height=%2764%27 rx=%2715%27 fill=%27%230f766e%27/%3E%3Crect x=%2722%27 y=%2719%27 width=%2711%27 height=%2726%27 rx=%273%27 fill=%27white%27/%3E%3Crect x=%2737%27 y=%2719%27 width=%2711%27 height=%2726%27 rx=%273%27 fill=%27white%27/%3E%3C/svg%3E">
<title>Qudroid Logistics Demo</title>
<meta name="claude:viewport-fit-sides" content="cover">
<meta name="description" content="Try the Qudroid Logistics demo: download the customer and driver apps, sign in with the demo logins and see live delivery tracking in action.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@112..125,500..900&family=Figtree:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap">
<style>
:root{color-scheme:light;padding-top:env(safe-area-inset-top);padding-bottom:env(safe-area-inset-bottom)}body{margin:0}img{max-width:100%}[hidden]{display:none!important}
/* Layout: a deep-teal "city at night" hero with a live route map, then a light paper body of
   app panels, a feature mosaic, a delivery sequence, downloads and a login card. */
:root{
  --paper:#f4f7f6; --surface:#ffffff; --ink:#0e1a1f; --muted:#52646a; --line:#d8e2e0;
  --teal:#0f766e; --teal-deep:#0b3f3b; --teal-soft:#e1f2ef; --amber:#ffd93b; --amber-ink:#3a2f00;
  --night:#06211f; --night-2:#0b3330; --night-line:rgba(255,255,255,.09); --night-fg:#eaf6f3; --night-muted:#9fc3bd;
  --display:"Archivo","Arial Black",system-ui,sans-serif;
  --body:"Figtree",system-ui,-apple-system,"Segoe UI",sans-serif;
  --mono:"JetBrains Mono",ui-monospace,"SFMono-Regular",Menlo,monospace;
  --r:14px;
}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){
  --paper:#0a1514; --surface:#10201e; --ink:#e7f2f0; --muted:#97b0ab; --line:#1f3532;
  --teal:#2fb3a5; --teal-deep:#0b3f3b; --teal-soft:#123230; --amber:#ffd93b; --amber-ink:#3a2f00;
  --night:#041715; --night-2:#082a27; color-scheme:dark }}
:root[data-theme="dark"]{
  --paper:#0a1514; --surface:#10201e; --ink:#e7f2f0; --muted:#97b0ab; --line:#1f3532;
  --teal:#2fb3a5; --teal-deep:#0b3f3b; --teal-soft:#123230; --amber:#ffd93b; --amber-ink:#3a2f00;
  --night:#041715; --night-2:#082a27; color-scheme:dark }

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--paper);color:var(--ink);font-family:var(--body);font-size:16px;line-height:1.6;overflow-x:hidden}
body::before{content:"";position:fixed;inset:0 0 auto;height:env(safe-area-inset-top,0px);z-index:60;background:var(--night)}
a{color:inherit}
:focus-visible{outline:3px solid var(--amber);outline-offset:3px;border-radius:6px}
.wrap{max-width:1180px;margin:0 auto;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}
h1,h2,h3{font-family:var(--display);font-stretch:118%;line-height:1.02;letter-spacing:-.02em;text-wrap:balance;margin:0}
h2{font-size:clamp(2rem,4.6vw,3.4rem);font-weight:850}
h3{font-size:1.15rem;font-weight:750;font-stretch:112%;letter-spacing:-.01em;line-height:1.2}
p{margin:0}
.eyebrow{font-family:var(--mono);font-size:.74rem;letter-spacing:.14em;text-transform:uppercase;color:var(--teal);font-weight:700}
.lede{color:var(--muted);font-size:1.12rem;max-width:62ch}
section{padding-block:clamp(64px,9vw,120px)}
.sec-head{display:grid;gap:14px;margin-bottom:44px;max-width:780px}

/* ---------- nav ---------- */
.nav{position:sticky;top:env(safe-area-inset-top,0px);z-index:50;background:color-mix(in srgb,var(--night) 88%,transparent);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--night-line);color:var(--night-fg)}
.nav .wrap{display:flex;align-items:center;gap:20px;height:64px}
.brand{white-space:nowrap;display:flex;align-items:center;gap:10px;text-decoration:none;font-family:var(--display);font-weight:800;font-stretch:115%;font-size:1.05rem;letter-spacing:-.01em}
.brand svg{flex:none}
.tag{font-family:var(--mono);font-size:.66rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;background:var(--amber);color:var(--amber-ink);padding:3px 8px;border-radius:99px}
.nav-links{display:flex;gap:22px;margin-left:auto;font-size:.92rem}
.nav-links a{text-decoration:none;color:var(--night-muted);transition:color .2s}
.nav-links a:hover{color:var(--night-fg)}
.btn{display:inline-flex;align-items:center;gap:10px;font:600 .98rem var(--body);text-decoration:none;border:0;border-radius:12px;padding:14px 20px;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,background .2s}
.btn:hover{transform:translateY(-2px)}
.btn-amber{background:var(--amber);color:var(--amber-ink);box-shadow:0 10px 30px -10px rgba(255,217,59,.55)}
.btn-ghost{background:transparent;color:var(--night-fg);box-shadow:inset 0 0 0 1.5px rgba(255,255,255,.28)}
.btn-ghost:hover{box-shadow:inset 0 0 0 1.5px var(--night-fg)}
.btn-sm{padding:9px 14px;font-size:.88rem;border-radius:10px}
@media (max-width:820px){.nav-links{display:none}.nav .btn-sm{margin-left:auto}}
@media (max-width:520px){.nav .tag{display:none}.nav .wrap{gap:12px}}

/* ---------- hero ---------- */
.hero{background:radial-gradient(1200px 600px at 85% -10%,#12564f 0%,transparent 60%),linear-gradient(180deg,var(--night) 0%,var(--night-2) 100%);color:var(--night-fg);position:relative;overflow:hidden;padding-block:clamp(48px,7vw,96px) clamp(56px,8vw,110px)}
.hero::after{content:"";position:absolute;inset:0;background-image:linear-gradient(var(--night-line) 1px,transparent 1px),linear-gradient(90deg,var(--night-line) 1px,transparent 1px);background-size:56px 56px;mask-image:linear-gradient(180deg,#000 0%,transparent 85%);-webkit-mask-image:linear-gradient(180deg,#000 0%,transparent 85%);pointer-events:none}
.hero .wrap{position:relative;z-index:1;display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center}
.hero-copy{display:grid;gap:26px;min-width:0}
.hero h1{font-size:clamp(2.6rem,6.4vw,5.3rem);font-weight:900;font-stretch:122%}
.hero h1 .hl{color:var(--amber);display:inline-block}
.hero .lede{color:var(--night-muted);font-size:clamp(1.05rem,1.6vw,1.22rem)}
.cta-row{display:flex;flex-wrap:wrap;gap:12px}
.chips{display:flex;flex-wrap:wrap;gap:8px;font-size:.84rem;color:var(--night-muted)}
.chips span{border:1px solid var(--night-line);border-radius:99px;padding:5px 12px;background:rgba(255,255,255,.03)}
.chips b{color:var(--night-fg);font-weight:600}
@media (max-width:900px){.hero .wrap{grid-template-columns:1fr;gap:40px}}

/* live map card */
.live{background:linear-gradient(180deg,#0d3a36,#082724);border:1px solid rgba(255,255,255,.1);border-radius:22px;padding:14px;box-shadow:0 40px 80px -30px rgba(0,0,0,.6),0 0 0 8px rgba(255,255,255,.02);min-width:0}
.live-top{display:flex;justify-content:space-between;align-items:center;padding:4px 6px 12px;font-size:.8rem;color:var(--night-muted)}
.live-top .dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#ff5d5d;margin-right:6px;animation:blink 1.4s infinite}
.live-top code{font-family:var(--mono);color:var(--night-fg);font-size:.78rem}
.map{position:relative;border-radius:14px;overflow:hidden;background:#0a2f2c;aspect-ratio:36/27;max-width:100%}
.map svg{display:block;width:100%;height:100%}
.status{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;margin-top:12px;padding:14px;background:rgba(255,255,255,.05);border-radius:14px;border:1px solid rgba(255,255,255,.07)}
.status .ic{width:40px;height:40px;border-radius:12px;background:var(--amber);color:var(--amber-ink);display:grid;place-items:center;transition:background .3s}
.status .t1{font-weight:650;font-size:.98rem;line-height:1.3}
.status .t2{font-size:.82rem;color:var(--night-muted);font-family:var(--mono)}
.eta{text-align:right;font-family:var(--display);font-stretch:118%;font-weight:850;font-size:1.6rem;line-height:1;font-variant-numeric:tabular-nums}
.eta small{display:block;font-family:var(--mono);font-size:.62rem;letter-spacing:.12em;color:var(--night-muted);font-weight:500;margin-top:4px}
.steps-bar{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-top:12px}
.steps-bar i{height:4px;border-radius:4px;background:rgba(255,255,255,.12);transition:background .4s}
.steps-bar i.on{background:var(--amber)}

/* ---------- ticker ---------- */
.ticker{background:var(--teal);color:#fff;overflow:hidden;white-space:nowrap;border-block:1px solid rgba(0,0,0,.1)}
.ticker-track{display:inline-flex;gap:48px;padding-block:14px;animation:scroll 40s linear infinite;font-family:var(--display);font-stretch:115%;font-weight:700;font-size:1rem;letter-spacing:.01em}
.ticker-track span{display:inline-flex;align-items:center;gap:48px}
.ticker-track span::after{content:"";width:8px;height:8px;background:var(--amber);border-radius:2px;transform:rotate(45deg)}

/* ---------- apps ---------- */
.apps{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.app{background:var(--surface);border:1px solid var(--line);border-radius:20px;padding:28px;display:grid;gap:18px;align-content:start;transition:transform .3s ease,box-shadow .3s ease;min-width:0}
.app:hover{transform:translateY(-6px);box-shadow:0 24px 50px -28px rgba(15,118,110,.45)}
.app-ic{width:52px;height:52px;border-radius:15px;display:grid;place-items:center;background:var(--teal-soft);color:var(--teal)}
.app .who{font-family:var(--mono);font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}
.app ul{list-style:none;margin:0;padding:0;display:grid;gap:10px;font-size:.95rem}
.app li{display:grid;grid-template-columns:18px 1fr;gap:10px;align-items:start}
.app li::before{content:"";width:18px;height:18px;margin-top:3px;border-radius:50%;background:var(--teal-soft) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 18 18'%3E%3Cpath d='M5 9.2l2.6 2.6L13 6.4' fill='none' stroke='%230f766e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/18px no-repeat}
@media (max-width:960px){.apps{grid-template-columns:1fr}}

/* ---------- feature mosaic ---------- */
.features{background:var(--surface);border-block:1px solid var(--line)}
.mosaic{display:grid;grid-template-columns:repeat(6,1fr);gap:16px}
.f{position:relative;border:1px solid var(--line);border-radius:18px;padding:24px;background:var(--paper);display:grid;gap:12px;align-content:start;overflow:hidden;min-width:0;transition:border-color .25s,transform .25s}
.f:hover{border-color:var(--teal);transform:translateY(-3px)}
.f p{color:var(--muted);font-size:.94rem}
.f .k{font-family:var(--mono);font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:var(--teal);font-weight:700}
.f.big{grid-column:span 3;padding:30px}
.f.big h3{font-size:1.5rem}
.f.mid{grid-column:span 2}
.f.wide{grid-column:span 3}
.f.dark{background:var(--teal-deep);border-color:var(--teal-deep);color:#eaf6f3}
.f.dark p{color:#a9cfc8}.f.dark .k{color:var(--amber)}
@media (max-width:980px){.mosaic{grid-template-columns:repeat(2,1fr)}.f.big,.f.mid,.f.wide{grid-column:span 2}.f.mid.half{grid-column:span 1}}
@media (max-width:560px){.mosaic{grid-template-columns:1fr}.f.big,.f.mid,.f.wide,.f.mid.half{grid-column:span 1}}

/* mini visuals inside feature tiles */
.viz{border-radius:12px;background:var(--surface);border:1px solid var(--line);padding:14px;font-family:var(--mono);font-size:.78rem;display:grid;gap:8px}
.f.dark .viz{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.qrow{display:flex;justify-content:space-between;gap:10px;align-items:center}
.pill{font-size:.68rem;padding:2px 8px;border-radius:99px;font-weight:700;letter-spacing:.04em;white-space:nowrap}
.p-wait{background:#fff3c4;color:#6b5200}.p-ok{background:#d5f2e9;color:#0b5b4c}.p-sync{background:#dbe8ff;color:#1f3f87}.p-off{background:#ffe1dc;color:#8a2516}
.alerts{display:flex;gap:6px;flex-wrap:wrap}
.alerts span{border-radius:8px;padding:6px 9px;background:rgba(255,255,255,.08);font-size:.74rem}
.alerts span.on{background:var(--amber);color:var(--amber-ink);font-weight:700}
.codebox{display:flex;gap:6px}
.codebox b{width:38px;height:44px;display:grid;place-items:center;border-radius:9px;background:var(--surface);border:1.5px solid var(--teal);font-size:1.2rem;color:var(--ink)}
.calc{display:grid;grid-template-columns:1fr auto;gap:4px 14px;font-variant-numeric:tabular-nums}
.calc .tot{border-top:1px dashed var(--line);padding-top:6px;font-weight:700;color:var(--teal)}
.gw{display:flex;flex-wrap:wrap;gap:6px}
.gw span{border:1px solid var(--line);border-radius:8px;padding:5px 9px;background:var(--surface);font-family:var(--body);font-weight:600;font-size:.8rem}
.f.dark .gw span{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.12)}
.bubble{max-width:85%;padding:8px 11px;border-radius:12px;font-family:var(--body);font-size:.84rem;line-height:1.35}
.bubble.me{justify-self:end;background:var(--teal);color:#fff;border-bottom-right-radius:3px}
.bubble.them{background:var(--teal-soft);color:var(--ink);border-bottom-left-radius:3px}

/* ---------- flow ---------- */
.flow{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;counter-reset:s;position:relative}
.flow::before{content:"";position:absolute;left:4%;right:4%;top:23px;height:2px;background:repeating-linear-gradient(90deg,var(--teal) 0 10px,transparent 10px 18px);opacity:.5}
.flow li{list-style:none;display:grid;gap:10px;justify-items:start;position:relative;min-width:0}
.flow li::before{counter-increment:s;content:counter(s);width:48px;height:48px;border-radius:50%;display:grid;place-items:center;background:var(--surface);border:2px solid var(--teal);color:var(--teal);font-family:var(--display);font-weight:850;font-size:1.1rem;font-stretch:115%}
.flow li:last-child::before{background:var(--amber);border-color:var(--amber);color:var(--amber-ink)}
.flow h3{font-size:1rem}
.flow p{font-size:.88rem;color:var(--muted)}
@media (max-width:980px){.flow{grid-template-columns:1fr;gap:18px}.flow::before{left:23px;right:auto;top:10px;bottom:10px;width:2px;height:auto;background:repeating-linear-gradient(180deg,var(--teal) 0 10px,transparent 10px 18px)}.flow li{grid-template-columns:48px 1fr;column-gap:16px}.flow li::before{grid-row:span 2}}
.flow{padding:0;margin:0}

/* ---------- modes ---------- */
.modes{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.mode{border-radius:22px;padding:34px;display:grid;gap:14px;min-width:0}
.mode.a{background:var(--teal);color:#fff}
.mode.a p,.mode.a li{color:#d6efea}
.mode.b{background:var(--surface);border:1px solid var(--line)}
.mode.b p,.mode.b li{color:var(--muted)}
.mode h3{font-size:1.7rem;font-stretch:120%}
.mode ul{margin:0;padding-left:18px;display:grid;gap:6px;font-size:.95rem}
.mode .k{font-family:var(--mono);font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;font-weight:700}
.mode.a .k{color:var(--amber)}.mode.b .k{color:var(--teal)}
@media (max-width:820px){.modes{grid-template-columns:1fr}}

/* ---------- download ---------- */
.dl{background:linear-gradient(180deg,var(--night),var(--night-2));color:var(--night-fg)}
.dl .eyebrow{color:var(--amber)}
.dl .lede{color:var(--night-muted)}
.dl-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.dl-card{border:1px solid rgba(255,255,255,.12);border-radius:22px;padding:28px;background:rgba(255,255,255,.04);display:grid;grid-template-columns:auto 1fr;gap:18px 20px;align-items:center;min-width:0}
.dl-card .appicon{width:72px;height:72px;border-radius:20px;display:grid;place-items:center;box-shadow:0 14px 30px -12px rgba(0,0,0,.6)}
.dl-card h3{font-size:1.4rem}
.dl-card .meta{font-family:var(--mono);font-size:.76rem;color:var(--night-muted)}
.dl-card .actions{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.dl-card p{grid-column:1/-1;color:var(--night-muted);font-size:.94rem}
.install{margin-top:28px;display:grid;grid-template-columns:repeat(4,1fr);gap:14px;padding:0;counter-reset:i}
.install li{list-style:none;border-top:1px solid rgba(255,255,255,.14);padding-top:14px;font-size:.92rem;color:var(--night-muted);min-width:0}
.install li::before{counter-increment:i;content:"Step " counter(i);display:block;font-family:var(--mono);font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:var(--amber);margin-bottom:6px}
.install b{color:var(--night-fg);font-weight:600}
@media (max-width:860px){.dl-grid{grid-template-columns:1fr}.install{grid-template-columns:1fr 1fr}}
@media (max-width:480px){.install{grid-template-columns:1fr}}

/* ---------- logins ---------- */
.login-card{background:var(--surface);border:1px solid var(--line);border-radius:22px;overflow:hidden}
.login-head{display:flex;flex-wrap:wrap;justify-content:space-between;gap:16px;align-items:center;padding:22px 26px;border-bottom:1px solid var(--line);background:var(--teal-soft)}
.pw{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.pw code{font-family:var(--mono);font-weight:700;font-size:1.1rem;background:var(--surface);border:1.5px dashed var(--teal);border-radius:10px;padding:6px 12px;color:var(--ink)}
.rows{display:grid}
.row{display:grid;grid-template-columns:1.1fr 1.6fr 1.3fr;gap:18px;align-items:center;padding:20px 26px;border-bottom:1px solid var(--line)}
.row:last-child{border-bottom:0}
.row .role{display:flex;gap:14px;align-items:center;min-width:0}
.row .role .app-ic{width:44px;height:44px;border-radius:12px;flex:none}
.row .role b{display:block;font-family:var(--display);font-stretch:112%;font-size:1.02rem;line-height:1.2}
.row .role small{color:var(--muted);font-size:.84rem}
.email{display:flex;align-items:center;gap:8px;min-width:0}
.email code{font-family:var(--mono);font-size:.9rem;overflow-wrap:anywhere;min-width:0}
.copy{border:1px solid var(--line);background:var(--paper);color:var(--ink);border-radius:8px;padding:5px 10px;font:600 .78rem var(--body);cursor:pointer;flex:none;transition:background .2s,color .2s}
.copy:hover{background:var(--teal);color:#fff;border-color:var(--teal)}
.copy.done{background:var(--teal);color:#fff;border-color:var(--teal)}
.where{font-size:.88rem;color:var(--muted)}
.where b{color:var(--ink);font-weight:600}
@media (max-width:820px){.row{grid-template-columns:1fr;gap:10px}}
.notes{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:20px}
.note{border-left:3px solid var(--amber);padding:4px 0 4px 14px;font-size:.92rem;color:var(--muted)}
.note b{color:var(--ink)}
@media (max-width:820px){.notes{grid-template-columns:1fr}}

footer{background:var(--night);color:var(--night-muted);padding-block:44px;font-size:.9rem}
footer .wrap{display:flex;flex-wrap:wrap;gap:16px 32px;justify-content:space-between;align-items:center}
footer .brand{color:var(--night-fg)}

/* ---------- motion ---------- */
@keyframes blink{50%{opacity:.25}}
@keyframes scroll{to{transform:translateX(-50%)}}
@keyframes rise{from{transform:translateY(32px)}to{transform:none}}
@keyframes heroIn{from{transform:translateY(18px);opacity:0}to{transform:none;opacity:1}}
@keyframes pulse{0%{r:6;opacity:.7}100%{r:22;opacity:0}}
@media (prefers-reduced-motion:no-preference){
  .hero-copy>*{animation:heroIn .8s cubic-bezier(.2,.7,.2,1) both}
  .hero-copy>*:nth-child(2){animation-delay:.08s}.hero-copy>*:nth-child(3){animation-delay:.16s}
  .hero-copy>*:nth-child(4){animation-delay:.24s}.hero-copy>*:nth-child(5){animation-delay:.32s}
  .live{animation:heroIn 1s .2s cubic-bezier(.2,.7,.2,1) both}
  @supports (animation-timeline:view()){
    .reveal{animation:rise linear both;animation-timeline:view();animation-range:entry 0% entry 60%}
  }
}
@media (prefers-reduced-motion:reduce){.ticker-track{animation:none}.live-top .dot{animation:none}}
</style>
</head>
<body>


<nav class="nav" aria-label="Main">
  <div class="wrap">
    <a class="brand" href="#top" aria-label="Qudroid Logistics home">
      <svg width="34" height="34" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="15" fill="#0f766e"/><g fill="#fff"><rect x="22" y="19" width="10.8" height="25.6" rx="3"/><rect x="36.5" y="19" width="10.8" height="25.6" rx="3"/><rect x="13" y="23.5" width="5.8" height="2" rx="1"/><rect x="9" y="31" width="9.8" height="2" rx="1"/><rect x="14" y="38.5" width="4.8" height="2" rx="1"/></g><rect x="21.5" y="28.3" width="26" height="2.5" rx="1" fill="#0f766e"/></svg>
      Qudroid Logistics
    </a>
    <span class="tag">Demo preview</span>
    <div class="nav-links">
      <a href="#apps">Apps</a><a href="#features">Features</a><a href="#how">How it works</a><a href="#download">Download</a><a href="#logins">Demo logins</a><a href="/login">Sign in</a>
    </div>
    <a class="btn btn-amber btn-sm" href="#download">Get the demo</a>
  </div>
</nav>

<header class="hero" id="top">
  <div class="wrap">
    <div class="hero-copy">
      <span class="eyebrow" style="color:var(--amber)">City delivery, rebuilt for Nigeria</span>
      <h1>Every parcel in the city, <span class="hl">live on one map.</span></h1>
      <p class="lede">Book a rider in a few taps, watch the parcel move street by street, and release payment only when it reaches the right hands. Delivery companies, independent riders and businesses all run on the same platform.</p>
      <div class="cta-row">
        <a class="btn btn-amber" href="#download">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
          Download the demo apps
        </a>
        <a class="btn btn-ghost" href="#logins">See demo logins</a>
      </div>
      <div class="chips" aria-label="Highlights">
        <span><b>Live GPS</b> tracking</span><span><b>Works offline</b> for riders</span><span><b>QR code</b> on every parcel</span><span><b>Escrow</b> payments</span>
      </div>
    </div>

    <div class="live" aria-label="Animated example of a live delivery">
      <div class="live-top"><span><span class="dot"></span>Live delivery · Lokoja</span><code id="trk">QD7M4X‑Q2CK</code></div>
      <div class="map">
        <svg viewBox="0 0 360 270" role="img" aria-label="Map of a rider travelling from Ganaja Road to Lokongoma">
          <defs>
            <linearGradient id="riv" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1a6b8f"/><stop offset="1" stop-color="#145a78"/></linearGradient>
          </defs>
          <rect width="360" height="270" fill="#0b302d"/>
          <!-- Niger meets Benue -->
          <path d="M300 -10 C 300 60, 330 110, 370 140 L370 -10Z" fill="url(#riv)" opacity=".55"/>
          <path d="M370 140 C 320 150, 300 200, 310 280 L 370 280Z" fill="url(#riv)" opacity=".55"/>
          <text x="352" y="70" fill="#7fb8cf" font-size="8" font-family="JetBrains Mono,monospace" text-anchor="end" opacity=".8">R. Niger</text>
          <text x="352" y="250" fill="#7fb8cf" font-size="8" font-family="JetBrains Mono,monospace" text-anchor="end" opacity=".8">R. Benue</text>
          <!-- blocks -->
          <g fill="#0f3d39">
            <rect x="14" y="14" width="60" height="44" rx="4"/><rect x="86" y="14" width="74" height="44" rx="4"/><rect x="172" y="14" width="56" height="44" rx="4"/><rect x="240" y="14" width="44" height="44" rx="4"/>
            <rect x="14" y="70" width="60" height="58" rx="4"/><rect x="86" y="70" width="74" height="58" rx="4"/><rect x="172" y="70" width="56" height="58" rx="4"/><rect x="240" y="70" width="50" height="58" rx="4"/>
            <rect x="14" y="140" width="60" height="52" rx="4"/><rect x="86" y="140" width="74" height="52" rx="4"/><rect x="172" y="140" width="56" height="52" rx="4"/><rect x="240" y="140" width="54" height="52" rx="4"/>
            <rect x="14" y="204" width="60" height="52" rx="4"/><rect x="86" y="204" width="74" height="52" rx="4"/><rect x="172" y="204" width="56" height="52" rx="4"/><rect x="240" y="204" width="56" height="52" rx="4"/>
          </g>
          <g fill="#13524c" opacity=".7"><rect x="96" y="80" width="20" height="16" rx="2"/><rect x="190" y="150" width="24" height="14" rx="2"/><rect x="24" y="214" width="18" height="18" rx="2"/><rect x="250" y="84" width="22" height="14" rx="2"/></g>
          <!-- route -->
          <path id="route" d="M44 41 L 80 41 Q 80 41 80 50 L 80 128 Q 80 134 86 134 L 160 134 Q 166 134 166 140 L 166 192 Q 166 198 172 198 L 228 198 Q 234 198 234 204 L 234 230" fill="none" stroke="#2c6d66" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
          <path id="done" d="M44 41 L 80 41 Q 80 41 80 50 L 80 128 Q 80 134 86 134 L 160 134 Q 166 134 166 140 L 166 192 Q 166 198 172 198 L 228 198 Q 234 198 234 204 L 234 230" fill="none" stroke="#ffd93b" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
          <!-- rider start (comes to pickup) -->
          <!-- pickup pin -->
          <g transform="translate(44 41)"><circle r="9" fill="#0f766e" stroke="#eaf6f3" stroke-width="2.5"/><circle r="3" fill="#eaf6f3"/></g>
          <g font-family="Figtree,system-ui,sans-serif" font-size="9" font-weight="700" fill="#eaf6f3"><rect x="20" y="58" width="74" height="17" rx="5" fill="#06211f" opacity=".85"/><text x="57" y="70" text-anchor="middle">Ganaja Road</text></g>
          <!-- drop pin -->
          <g transform="translate(234 230)"><path d="M0 0 C -9 -10 -11 -14 -11 -19 A 11 11 0 0 1 11 -19 C 11 -14 9 -10 0 0Z" fill="#ffd93b" stroke="#06211f" stroke-width="1.5"/><circle cy="-19" r="4" fill="#06211f"/></g>
          <g font-family="Figtree,system-ui,sans-serif" font-size="9" font-weight="700" fill="#eaf6f3"><rect x="180" y="238" width="104" height="17" rx="5" fill="#06211f" opacity=".85"/><text x="232" y="250" text-anchor="middle">Lokongoma Phase 1</text></g>
          <!-- geofence -->
          <circle cx="234" cy="214" r="30" fill="none" stroke="#ffd93b" stroke-width="1.2" stroke-dasharray="3 4" opacity=".55"/>
          <!-- rider -->
          <g id="rider">
            <circle r="6" fill="#ffd93b" opacity=".6"><animate attributeName="r" values="7;20" dur="1.6s" repeatCount="indefinite"/><animate attributeName="opacity" values=".6;0" dur="1.6s" repeatCount="indefinite"/></circle>
            <circle r="9" fill="#ffd93b" stroke="#06211f" stroke-width="2"/>
            <path d="M-4.5 2.5 a2 2 0 1 0 0.01 0 M4.5 2.5 a2 2 0 1 0 0.01 0 M-4.5 2.5 L-1 -3 L3 -3 L4.5 2.5 M-1 -3 L-2.5 -5" fill="none" stroke="#06211f" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </g>
        </svg>
      </div>
      <div class="status" aria-live="polite">
        <div class="ic" id="sIc" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/></svg>
        </div>
        <div style="min-width:0"><div class="t1" id="s1">Rider assigned</div><div class="t2" id="s2">Musa · Bajaj Boxer · ★ 4.9</div></div>
        <div class="eta" id="eta">18<small>MIN ETA</small></div>
      </div>
      <div class="steps-bar" aria-hidden="true"><i class="on"></i><i></i><i></i><i></i><i></i></div>
    </div>
  </div>
</header>

<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <span>Paystack</span><span>OPay</span><span>Monnify</span><span>Stripe</span><span>Cash on delivery</span><span>SMS alerts</span><span>WhatsApp</span><span>Push notifications</span><span>Excel &amp; PDF reports</span><span>Partner API</span><span>Pickup points</span><span>Gift cards</span>
    <span>Paystack</span><span>OPay</span><span>Monnify</span><span>Stripe</span><span>Cash on delivery</span><span>SMS alerts</span><span>WhatsApp</span><span>Push notifications</span><span>Excel &amp; PDF reports</span><span>Partner API</span><span>Pickup points</span><span>Gift cards</span>
  </div>
</div>

<main>
<section id="apps">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="eyebrow">Three ways in</span>
      <h2>One platform for the sender, the rider and the company.</h2>
      <p class="lede">Customers book and track from their phone. Riders get a job app built for patchy networks. Delivery companies run dispatch, pricing and payouts from the web dashboard.</p>
    </div>
    <div class="apps">
      <article class="app reveal">
        <div class="app-ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/></svg></div>
        <div><div class="who">Android app · for senders</div><h3 style="font-size:1.5rem;margin-top:6px">Qudroid Logistics</h3></div>
        <ul>
          <li>Send a parcel, compare offers from riders and companies, agree a price</li>
          <li>Pay by card, transfer, wallet or cash on delivery</li>
          <li>Follow the rider live with an ETA, and get "5 minutes away" alerts</li>
          <li>Chat with your rider or call through a masked number</li>
          <li>Promo codes, loyalty points, gift cards and invite-a-friend credit</li>
        </ul>
      </article>
      <article class="app reveal">
        <div class="app-ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M15 6h3l3 7.5M5.5 17.5 9 11h6l3.5 6.5M9 11 7 7H4"/></svg></div>
        <div><div class="who">Android app · for riders</div><h3 style="font-size:1.5rem;margin-top:6px">Qudroid Driver</h3></div>
        <ul>
          <li>Go online, accept or decline jobs, one-tap navigation</li>
          <li>Scan each parcel's QR code at pickup and drop-off</li>
          <li>Photo proof, customer signature and one-time delivery codes</li>
          <li>Keeps working with no signal and syncs when the network returns</li>
          <li>Earnings, withdrawals and cash-collected records in one place</li>
        </ul>
      </article>
      <article class="app reveal">
        <div class="app-ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3M6 14l3-3 3 2 5-5"/></svg></div>
        <div><div class="who">Web dashboard · for companies</div><h3 style="font-size:1.5rem;margin-top:6px">Company workspace</h3></div>
        <ul>
          <li>Drag-and-drop dispatch board with every rider in a lane</li>
          <li>Book jobs by hand, in bulk from a spreadsheet, or through the partner API</li>
          <li>Pricing calculator built on fuel, distance and your margin</li>
          <li>Business accounts with credit limits and monthly invoices</li>
          <li>Revenue, profit and rider reports, exported to Excel or PDF</li>
        </ul>
      </article>
    </div>
  </div>
</section>

<section class="features" id="features">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="eyebrow">What makes it different</span>
      <h2>Built for real streets, real networks and real money.</h2>
    </div>
    <div class="mosaic">
      <article class="f big dark reveal">
        <span class="k">Live tracking</span>
        <h3>See the rider move, and know before they knock.</h3>
        <p>GPS updates draw the route on a live map. The customer gets alerts as the rider gets close, and the app marks the stop as arrived when the rider enters the drop-off zone.</p>
        <div class="viz"><div class="alerts" id="alerts"><span>15 min away</span><span>5 min away</span><span>Within 500 m</span><span>Arrived</span></div></div>
      </article>
      <article class="f big reveal">
        <span class="k">Offline-first rider app</span>
        <h3>No signal? The job keeps going.</h3>
        <p>Pickups, scans and photos are saved on the phone first and sent in order when the network comes back. The rider always sees what is waiting.</p>
        <div class="viz">
          <div class="qrow"><span>Parcel scanned · PK7M4XQ2C</span><span class="pill p-ok">Accepted</span></div>
          <div class="qrow"><span>Photo proof · 1.2 MB</span><span class="pill p-sync">Syncing</span></div>
          <div class="qrow"><span>Delivered · 14:32</span><span class="pill p-wait">Saved on phone</span></div>
        </div>
      </article>
      <article class="f mid reveal">
        <span class="k">Proof of delivery</span>
        <h3>The right parcel, to the right person.</h3>
        <p>Every parcel carries a QR label. The receiver reads out a one-time code, and the rider can add a photo and a signature.</p>
        <div class="codebox" aria-label="Example delivery code 4 8 1 9"><b>4</b><b>8</b><b>1</b><b>9</b></div>
      </article>
      <article class="f mid reveal">
        <span class="k">Safe payments</span>
        <h3>Money moves only when the parcel does.</h3>
        <p>Customers pay into escrow. The provider is paid, minus the platform share, after the delivery is confirmed.</p>
        <div class="gw"><span>Paystack</span><span>OPay</span><span>Monnify</span><span>Stripe</span><span>Wallet</span><span>Cash</span></div>
      </article>
      <article class="f mid reveal">
        <span class="k">Pricing calculator</span>
        <h3>Know your cost before you quote.</h3>
        <div class="viz calc">
          <span>Fuel · 12.4 km</span><span>₦1,190</span>
          <span>Rider time</span><span>₦800</span>
          <span>Maintenance</span><span>₦250</span>
          <span>Margin · 25%</span><span>₦560</span>
          <span class="tot">Suggested price</span><span class="tot">₦2,800</span>
        </div>
      </article>
      <article class="f mid half reveal">
        <span class="k">Chat &amp; calls</span>
        <h3>Talk without sharing numbers.</h3>
        <div class="viz" style="font-family:var(--body)">
          <span class="bubble them">I'm at the gate by the pharmacy</span>
          <span class="bubble me">Coming down now 👍</span>
        </div>
      </article>
      <article class="f mid half reveal">
        <span class="k">Cash on delivery</span>
        <h3>Collect at the door, settle with a reference.</h3>
        <p>Riders record cash collected, hand it in, and the sender is paid out with a traceable reference.</p>
      </article>
      <article class="f mid reveal">
        <span class="k">For businesses</span>
        <h3>Bulk bookings, returns and pickup points.</h3>
        <p>Upload a spreadsheet of deliveries, plug a store in through the partner API, and handle returns through nearby pickup points.</p>
      </article>
      <article class="f wide reveal">
        <span class="k">Grow with rewards</span>
        <h3>Promos, points and gift cards.</h3>
        <p>Run promo codes, reward repeat customers with loyalty points, sell gift cards and give credit for inviting friends.</p>
      </article>
      <article class="f wide reveal">
        <span class="k">Security</span>
        <h3>Two-step sign-in and a full audit trail.</h3>
        <p>Staff accounts can require a second step at sign-in, and every important change is recorded with who made it and when.</p>
      </article>
    </div>
  </div>
</section>

<section id="how">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="eyebrow">How a delivery works</span>
      <h2>From request to "received" in seven steps.</h2>
    </div>
    <ol class="flow">
      <li class="reveal"><h3>Request</h3><p>Pickup, drop-off and parcel details.</p></li>
      <li class="reveal"><h3>Agree a price</h3><p>Offers come in, or a fixed company rate applies.</p></li>
      <li class="reveal"><h3>Pay safely</h3><p>Money is held until the job is done.</p></li>
      <li class="reveal"><h3>Pickup</h3><p>The rider scans each parcel's QR label.</p></li>
      <li class="reveal"><h3>Track live</h3><p>Map, ETA and "almost there" alerts.</p></li>
      <li class="reveal"><h3>Hand over</h3><p>Delivery code, photo or signature.</p></li>
      <li class="reveal"><h3>Everyone's paid</h3><p>The provider receives their share.</p></li>
    </ol>
  </div>
</section>

<section style="padding-top:0">
  <div class="wrap">
    <div class="modes">
      <div class="mode a reveal">
        <span class="k">For a delivery company</span>
        <h3>Your brand, your fleet.</h3>
        <p>Run the whole platform as your own delivery service: your riders, your prices, your customers.</p>
        <ul><li>Your own dispatch team and riders</li><li>Your own rates and business accounts</li><li>Customer and rider apps in your name</li></ul>
      </div>
      <div class="mode b reveal">
        <span class="k">As a marketplace</span>
        <h3>Many providers, one city.</h3>
        <p>Delivery companies, independent riders and market shoppers join one marketplace, and customers pick the offer that suits them.</p>
        <ul><li>Providers apply and are approved</li><li>Customers compare offers and ratings</li><li>The platform keeps a share of each job</li></ul>
      </div>
    </div>
  </div>
</section>

<section class="dl" id="download">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="eyebrow">Download the demo</span>
      <h2>Try both apps on your Android phone.</h2>
      <p class="lede">These are demo builds connected to our test server. Use the demo logins below to sign in as a customer or a rider.</p>
    </div>
    <div class="dl-grid">
      <article class="dl-card reveal">
        <div class="appicon" style="background:#0f766e"><svg width="44" height="44" viewBox="0 0 64 64" aria-hidden="true"><g fill="#fff"><rect x="22" y="19" width="10.8" height="25.6" rx="3"/><rect x="36.5" y="19" width="10.8" height="25.6" rx="3"/><rect x="13" y="23.5" width="5.8" height="2" rx="1"/><rect x="9" y="31" width="9.8" height="2" rx="1"/><rect x="14" y="38.5" width="4.8" height="2" rx="1"/></g><rect x="21.5" y="28.3" width="26" height="2.5" rx="1" fill="#0f766e"/></svg></div>
        <div><h3>Qudroid Logistics</h3><div class="meta">Customer app · Android 6.0+ · APK</div></div>
        <p>Book deliveries, pay, track your rider and chat. Sign in with the customer demo login.</p>
        <div class="actions">
          <a class="btn btn-amber" href="/downloads/qudroid-logistics-demo.apk" download>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
            Download customer app
          </a>
        </div>
      </article>
      <article class="dl-card reveal">
        <div class="appicon" style="background:#ffd93b"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#3a2f00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M15 6h3l3 7.5M5.5 17.5 9 11h6l3.5 6.5M9 11 7 7H4"/></svg></div>
        <div><h3>Qudroid Driver</h3><div class="meta">Rider app · Android 6.0+ · APK</div></div>
        <p>Go online, take jobs, scan parcels and record proof of delivery. Sign in with the driver demo login.</p>
        <div class="actions">
          <a class="btn btn-amber" href="/downloads/qudroid-driver-demo.apk" download>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
            Download driver app
          </a>
        </div>
      </article>
    </div>
    <ol class="install">
      <li><b>Download the APK</b> on your Android phone using the buttons above.</li>
      <li>Open the file. If asked, <b>allow installs from this source</b> in settings.</li>
      <li>Tap <b>Install</b>, then open the app.</li>
      <li>Sign in with a <b>demo login</b> from the next section.</li>
    </ol>
  </div>
</section>

<section id="logins">
  <div class="wrap">
    <div class="sec-head reveal">
      <span class="eyebrow">Demo logins</span>
      <h2>Sign in and look around.</h2>
      <p class="lede">The same accounts work in the apps and on the web at <a href="https://logistics.gozakmart.ng/login" target="_blank" rel="noopener" style="color:var(--teal);font-weight:600">logistics.gozakmart.ng/login</a>.</p>
    </div>
    <div class="login-card reveal">
      <div class="login-head">
        <div><b style="font-family:var(--display);font-stretch:115%;font-size:1.1rem">One password for every demo account</b><div class="where">Type the email exactly as shown.</div></div>
        <div class="pw"><code id="pw">Demo1234!</code><button class="copy" type="button" data-copy="Demo1234!" id="copy-pw">Copy password</button></div>
      </div>
      <div class="rows">
        <div class="row">
          <div class="role"><div class="app-ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/></svg></div><div><b>Customer</b><small>Wallet pre-funded with ₦50,000</small></div></div>
          <div class="email"><code>demo.customer@example.test</code><button class="copy" type="button" data-copy="demo.customer@example.test" id="copy-c">Copy</button></div>
          <div class="where">Use in the <b>Qudroid Logistics</b> app, or on the web under My account.</div>
        </div>
        <div class="row">
          <div class="role"><div class="app-ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M15 6h3l3 7.5M5.5 17.5 9 11h6l3.5 6.5M9 11 7 7H4"/></svg></div><div><b>Rider</b><small>Active driver for the demo company</small></div></div>
          <div class="email"><code>demo.driver@example.test</code><button class="copy" type="button" data-copy="demo.driver@example.test" id="copy-d">Copy</button></div>
          <div class="where">Use in the <b>Qudroid Driver</b> app.</div>
        </div>
        <div class="row">
          <div class="role"><div class="app-ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg></div><div><b>Delivery company owner</b><small>Approved company with one rider</small></div></div>
          <div class="email"><code>demo.provider@example.test</code><button class="copy" type="button" data-copy="demo.provider@example.test" id="copy-p">Copy</button></div>
          <div class="where">Use on the <b>web dashboard</b> to dispatch, price and see reports.</div>
        </div>
      </div>
    </div>
    <div class="notes">
      <p class="note"><b>Try the full loop.</b> Book as the customer, accept the job as the rider, then watch it on the company dashboard.</p>
      <p class="note"><b>The demo city is Lokoja.</b> Pick pickup and drop-off points inside Lokoja so a rider can be matched.</p>
      <p class="note"><b>These accounts are shared.</b> Don't enter real card details or personal information. Demo data may be reset.</p>
    </div>
  </div>
</section>
</main>

<footer>
  <div class="wrap">
    <a class="brand" href="#top">
      <svg width="28" height="28" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="15" fill="#0f766e"/><g fill="#fff"><rect x="22" y="19" width="10.8" height="25.6" rx="3"/><rect x="36.5" y="19" width="10.8" height="25.6" rx="3"/><rect x="13" y="23.5" width="5.8" height="2" rx="1"/><rect x="9" y="31" width="9.8" height="2" rx="1"/><rect x="14" y="38.5" width="4.8" height="2" rx="1"/></g><rect x="21.5" y="28.3" width="26" height="2.5" rx="1" fill="#0f766e"/></svg>
      Qudroid Logistics
    </a>
    <p>This is a preview site for the demo apps. Our official website arrives with the launch.</p>
  </div>
</footer>

<script>
(function(){
  // Copy buttons
  document.querySelectorAll('.copy').forEach(function(b){
    b.addEventListener('click',function(){
      var text=b.getAttribute('data-copy'), label=b.textContent;
      function ok(){b.textContent='Copied';b.classList.add('done');setTimeout(function(){b.textContent=label;b.classList.remove('done')},1600)}
      function fallback(){var code=b.previousElementSibling;if(code){var r=document.createRange();r.selectNodeContents(code);var s=getSelection();s.removeAllRanges();s.addRange(r)}b.textContent='Selected, press copy';setTimeout(function(){b.textContent=label},2200)}
      try{navigator.clipboard.writeText(text).then(ok,fallback)}catch(e){fallback()}
    });
  });

  // Live delivery animation
  var route=document.getElementById('route'), done=document.getElementById('done'), rider=document.getElementById('rider');
  if(!route||!rider) return;
  var L=route.getTotalLength();
  done.style.strokeDasharray=L; done.style.strokeDashoffset=L;
  var s1=document.getElementById('s1'), s2=document.getElementById('s2'), eta=document.getElementById('eta'),
      bars=document.querySelectorAll('.steps-bar i'), alerts=document.querySelectorAll('#alerts span');
  var phases=[
    {at:0,   t:'Rider assigned', d:'Musa · Bajaj Boxer · ★ 4.9', step:1},
    {at:.06, t:'Picked up · parcel scanned', d:'PK7M4XQ2C · custody logged', step:2},
    {at:.35, t:'On the way to you', d:'Ganaja Rd → Lokongoma', step:3},
    {at:.78, t:'About 5 minutes away', d:'SMS + push sent to receiver', step:4},
    {at:.97, t:'Delivered · code verified', d:'Code 4819 · photo proof saved', step:5}
  ];
  var reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;
  var DUR=14000, HOLD=3000, t0=null, last=-1;
  function setPhase(i){
    if(i===last) return; last=i; var p=phases[i];
    s1.textContent=p.t; s2.textContent=p.d;
    bars.forEach(function(b,k){b.classList.toggle('on',k<p.step)});
  }
  function frame(ts){
    if(t0===null) t0=ts;
    var e=(ts-t0)%(DUR+HOLD), k=Math.min(e/DUR,1);
    var ease=k<.5?2*k*k:1-Math.pow(-2*k+2,2)/2;
    var pt=route.getPointAtLength(ease*L);
    rider.setAttribute('transform','translate('+pt.x.toFixed(1)+' '+pt.y.toFixed(1)+')');
    done.style.strokeDashoffset=(L*(1-ease)).toFixed(1);
    var i=0; for(var j=0;j<phases.length;j++){if(ease>=phases[j].at) i=j}
    setPhase(i);
    var mins=Math.max(0,Math.ceil(18*(1-ease)));
    eta.firstChild.nodeValue = ease>=.97 ? '✓' : String(mins);
    var a = ease>=.97?3 : ease>=.88?2 : ease>=.78?1 : ease>=.5?0 : -1;
    alerts.forEach(function(el,n){el.classList.toggle('on',n===a)});
    requestAnimationFrame(frame);
  }
  if(reduce){
    var pt=route.getPointAtLength(L*.8); rider.setAttribute('transform','translate('+pt.x+' '+pt.y+')');
    done.style.strokeDashoffset=L*.2; setPhase(3); eta.firstChild.nodeValue='5'; alerts[1].classList.add('on');
  } else requestAnimationFrame(frame);
})();
</script>

</body>
</html>
@endverbatim
