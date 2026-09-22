<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Small Update In Progress</title>
<style>
  :root{
    --mx: 0;
    --my: 0;
    --violet: #7b5cff;
    --cyan:   #00e0ff;
    --pink:   #ff4d9d;
  }

  *{ margin:0; padding:0; box-sizing:border-box; }

  html, body{
    height:100%; width:100%;
    overflow:hidden;               /* no scroll, ever */
    background:#04050d;
  }

  body{
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                 "Helvetica Neue", Arial, sans-serif;
    color:#e9eeff;
    -webkit-font-smoothing:antialiased;
    text-rendering:optimizeLegibility;
  }

  /* ================= SCENE ================= */
  .scene{
    position:fixed; inset:0;
    perspective:1400px;
    perspective-origin:50% 50%;
    transform-style:preserve-3d;
    transform:
      rotateX(calc(var(--my) * -3deg))
      rotateY(calc(var(--mx) *  3deg));
    will-change:transform;
  }

  /* generic parallax layer */
  .layer{
    position:absolute; inset:-15%;
    pointer-events:none;
    will-change:transform;
    transform:
      translate3d(
        calc(var(--mx) * var(--d, 0) * 1px),
        calc(var(--my) * var(--d, 0) * 1px),
        0
      );
  }

  /* ---------- aurora blobs ---------- */
  .blob{
    position:absolute;
    border-radius:50%;
    filter:blur(80px);
    opacity:.55;
    mix-blend-mode:screen;
  }
  .b1{
    width:55vmax; height:55vmax; left:-18vmax; top:-22vmax;
    background:radial-gradient(circle at 35% 35%, #6a4bff, transparent 62%);
    animation:drift1 20s ease-in-out infinite;
  }
  .b2{
    width:48vmax; height:48vmax; right:-16vmax; top:-12vmax;
    background:radial-gradient(circle at 60% 40%, #00c2ff, transparent 62%);
    animation:drift2 24s ease-in-out infinite;
  }
  .b3{
    width:52vmax; height:52vmax; left:18vmax; bottom:-30vmax;
    background:radial-gradient(circle at 50% 50%, #ff3d9a, transparent 62%);
    opacity:.35;
    animation:drift3 28s ease-in-out infinite;
  }
  @keyframes drift1{ 50%{ transform:translate3d( 6vmax, 4vmax,0) scale(1.12); } }
  @keyframes drift2{ 50%{ transform:translate3d(-5vmax, 5vmax,0) scale(1.08); } }
  @keyframes drift3{ 50%{ transform:translate3d( 4vmax,-4vmax,0) scale(1.15); } }

  /* ---------- perspective grid floor ---------- */
  .grid-wrap{
    position:absolute; inset:-6%;
    overflow:hidden;
    pointer-events:none;
    will-change:transform;
    transform:
      translate3d(
        calc(var(--mx) * 14px),
        calc(var(--my) * 14px),
        0
      );
  }
  .grid{
    position:absolute;
    left:-60%; right:-60%; bottom:-12%;
    height:70%;
    background-image:
      linear-gradient(rgba(123,92,255,.30) 1px, transparent 1px),
      linear-gradient(90deg, rgba(123,92,255,.30) 1px, transparent 1px);
    background-size:64px 64px;
    transform: perspective(520px) rotateX(74deg);
    transform-origin: bottom center;
    -webkit-mask-image: linear-gradient(to top, #000 0%, rgba(0,0,0,.4) 45%, transparent 78%);
            mask-image: linear-gradient(to top, #000 0%, rgba(0,0,0,.4) 45%, transparent 78%);
    animation: gridmove 5.5s linear infinite;
  }
  @keyframes gridmove{
    from{ background-position: 0 0,   0 0; }
    to  { background-position: 0 64px, 0 0; }
  }

  /* ---------- floating particles ---------- */
  .particle{
    position:absolute;
    border-radius:50%;
    pointer-events:none;
    box-shadow:0 0 6px rgba(140,180,255,.85);
    animation-name:rise;
    animation-timing-function:linear;
    animation-iteration-count:infinite;
    will-change:transform, opacity;
  }
  @keyframes rise{
    0%   { transform:translate3d(0,0,0) scale(.4);          opacity:0; }
    12%  {                                                   opacity:1; }
    85%  {                                                   opacity:.65; }
    100% { transform:translate3d(var(--dx), -170px, 0) scale(1.15); opacity:0; }
  }

  /* ---------- vignette + grain ---------- */
  .vignette{
    position:absolute; inset:0;
    pointer-events:none;
    background: radial-gradient(120% 90% at 50% 45%,
                transparent 28%, rgba(2,3,10,.78) 100%);
  }
  .grain{
    position:fixed; inset:0;
    pointer-events:none;
    z-index:99;
    opacity:.035;
    mix-blend-mode:overlay;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  }

  /* ================= CARD ================= */
  .card-layer{
    position:absolute; inset:0;
    display:grid; place-items:center;
    padding:24px;
    perspective:1100px;
  }

  .card-float{
    transform-style:preserve-3d;
    animation: bob 7s ease-in-out infinite;
  }
  @keyframes bob{
    0%,100%{ transform:translateY(-9px); }
    50%    { transform:translateY( 9px); }
  }

  .card{
    position:relative;
    width:min(92vw, 540px);
    padding:clamp(28px, 4.2vw, 48px);
    border-radius:28px;
    text-align:center;
    background:
      linear-gradient(155deg,
        rgba(255,255,255,.10) 0%,
        rgba(255,255,255,.035) 45%,
        rgba(255,255,255,.075) 100%);
    border:1px solid rgba(255,255,255,.14);
    box-shadow:
      0 50px 90px -30px rgba(0,0,0,.9),
      0 0 90px -25px rgba(123,92,255,.55),
      inset 0 1px 0 rgba(255,255,255,.30),
      inset 0 -1px 0 rgba(255,255,255,.05);
    transform-style:preserve-3d;
    transform:
      rotateX(calc(var(--my) * -9deg))
      rotateY(calc(var(--mx) *  9deg))
      translateZ(40px);
    will-change:transform;
  }

  /* moving specular highlight that follows the cursor */
  .sheen{
    position:absolute; inset:0;
    border-radius:inherit;
    pointer-events:none;
    background: radial-gradient(
      420px circle at
        calc(50% + var(--mx) * 42%)
        calc(50% + var(--my) * 42%),
      rgba(255,255,255,.11),
      transparent 62%);
  }

  .card > *{ position:relative; z-index:1; }

  /* ---------- icon ---------- */
  .icon{
    width:64px; height:64px;
    margin:0 auto 22px;
    position:relative;
    transform:translateZ(55px);
  }
  .icon::before{
    content:"";
    position:absolute; inset:0;
    border-radius:50%;
    background: conic-gradient(from 0deg,
      transparent 0% 52%, var(--violet) 76%, var(--cyan) 100%);
    -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 0);
            mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 0);
    animation: spin 1.5s linear infinite;
    filter: drop-shadow(0 0 10px rgba(123,92,255,.9));
  }
  .icon::after{
    content:"";
    position:absolute; inset:6px;
    border-radius:50%;
    border:1px solid rgba(255,255,255,.10);
  }
  .icon .core{
    position:absolute; top:50%; left:50%;
    width:11px; height:11px;
    margin:-5.5px 0 0 -5.5px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--violet), var(--cyan));
    box-shadow:0 0 18px rgba(123,92,255,1);
    animation: pulse 1.8s ease-in-out infinite;
  }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  @keyframes pulse{
    0%,100%{ transform:scale(1);   opacity:1; }
    50%    { transform:scale(1.5); opacity:.65; }
  }

  /* ---------- badge ---------- */
  .badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 16px;
    margin-bottom:22px;
    border-radius:99px;
    font-size:11.5px;
    font-weight:700;
    letter-spacing:.15em;
    text-transform:uppercase;
    color:#c9d4ff;
    background:rgba(123,92,255,.14);
    border:1px solid rgba(123,92,255,.38);
    box-shadow: inset 0 0 22px -6px rgba(123,92,255,.9);
    transform:translateZ(32px);
  }
  .dot{
    width:7px; height:7px;
    border-radius:50%;
    background:#5cffb1;
    box-shadow:0 0 10px #5cffb1;
    animation: blink 1.6s ease-in-out infinite;
  }
  @keyframes blink{
    0%,100%{ opacity:1;   transform:scale(1); }
    50%    { opacity:.35; transform:scale(.75); }
  }

  /* ---------- text ---------- */
  h1{
    font-size:clamp(28px, 4.6vw, 42px);
    line-height:1.12;
    font-weight:700;
    letter-spacing:-.02em;
    margin-bottom:16px;
    background:linear-gradient(180deg, #ffffff 0%, #b6c3ff 100%);
    -webkit-background-clip:text;
            background-clip:text;
    color:transparent;
    transform:translateZ(48px);
  }

  p{
    max-width:42ch;
    margin:0 auto 30px;
    font-size:clamp(14px, 1.6vw, 16px);
    line-height:1.68;
    color:#95a2c9;
    transform:translateZ(26px);
  }

  /* ---------- progress ---------- */
  .progress{
    position:relative;
    height:6px;
    border-radius:99px;
    background:rgba(255,255,255,.07);
    box-shadow: inset 0 1px 3px rgba(0,0,0,.7);
    overflow:hidden;
    margin-bottom:16px;
    transform:translateZ(20px);
  }
  .bar{
    position:absolute; top:0; bottom:0; left:0;
    width:38%;
    border-radius:99px;
    background:linear-gradient(90deg, transparent, var(--violet), var(--cyan), transparent);
    filter: drop-shadow(0 0 8px rgba(0,224,255,.95));
    animation: slide 2.6s cubic-bezier(.65,.05,.36,1) infinite;
  }
  @keyframes slide{
    0%  { transform:translateX(-110%); }
    100%{ transform:translateX(300%);  }
  }

  .meta{
    display:flex;
    justify-content:space-between;
    gap:12px;
    font-size:11px;
    font-weight:700;
    letter-spacing:.09em;
    text-transform:uppercase;
    color:#6d79a3;
    transform:translateZ(16px);
  }

  /* ---------- reduced motion ---------- */
  @media (prefers-reduced-motion: reduce){
    *{ animation:none !important; }
    .card{ transform:translateZ(0); }
  }
</style>
</head>
<body>

  <div class="scene">

    <!-- glowing background -->
    <div class="layer" style="--d:30">
      <div class="blob b1"></div>
      <div class="blob b2"></div>
      <div class="blob b3"></div>
    </div>

    <!-- perspective grid -->
    <div class="grid-wrap">
      <div class="grid"></div>
    </div>

    <!-- floating dust -->
    <div class="layer" id="particles" style="--d:60"></div>

    <!-- dark edges -->
    <div class="vignette"></div>

    <!-- main card -->
    <div class="card-layer">
      <div class="card-float">
        <div class="card">
          <div class="sheen"></div>

          <div class="icon"><span class="core"></span></div>

          <span class="badge"><i class="dot"></i>Minor update in progress</span>

          <h1>Back in a few minutes</h1>

          <p>
            We&rsquo;re applying a small update behind the scenes &mdash; nothing major,
            just a few tiny improvements. Please check back in a couple of minutes.
          </p>

          <div class="progress"><span class="bar"></span></div>

          <div class="meta">
            <span>Deploying changes</span>
            <span>&asymp; 2 minutes</span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="grain"></div>

<script>
(function () {
  var root = document.documentElement;
  var targetX = 0, targetY = 0;
  var curX = 0, curY = 0;
  var interacted = false;
  var startTime = performance.now();

  function setTarget(clientX, clientY) {
    interacted = true;
    targetX = (clientX / window.innerWidth) * 2 - 1;
    targetY = (clientY / window.innerHeight) * 2 - 1;
  }

  window.addEventListener('mousemove', function (e) {
    setTarget(e.clientX, e.clientY);
  }, { passive: true });

  window.addEventListener('touchmove', function (e) {
    if (e.touches && e.touches[0]) setTarget(e.touches[0].clientX, e.touches[0].clientY);
  }, { passive: true });

  function tick(now) {
    // gentle auto-drift until the visitor moves the mouse
    if (!interacted) {
      var t = (now - startTime) / 1000;
      targetX = Math.sin(t * 0.35) * 0.45;
      targetY = Math.cos(t * 0.27) * 0.35;
    }
    curX += (targetX - curX) * 0.06;
    curY += (targetY - curY) * 0.06;

    root.style.setProperty('--mx', curX.toFixed(4));
    root.style.setProperty('--my', curY.toFixed(4));

    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);

  /* ---- generate floating particles ---- */
  var wrap = document.getElementById('particles');
  var i, p, size, alpha;
  for (i = 0; i < 48; i++) {
    p = document.createElement('span');
    p.className = 'particle';

    size  = (Math.random() * 2.4 + 1).toFixed(2);
    alpha = (0.25 + Math.random() * 0.6).toFixed(2);

    p.style.width  = size + 'px';
    p.style.height = size + 'px';
    p.style.left   = (Math.random() * 100).toFixed(2) + '%';
    p.style.top    = (Math.random() * 100).toFixed(2) + '%';
    p.style.background = 'rgba(200,220,255,' + alpha + ')';
    p.style.animationDuration = (7 + Math.random() * 11).toFixed(2) + 's';
    p.style.animationDelay    = (-Math.random() * 18).toFixed(2) + 's';
    p.style.setProperty('--dx', (Math.random() * 60 - 30).toFixed(1) + 'px');

    wrap.appendChild(p);
  }
})();
</script>

</body>
</html>