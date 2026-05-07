<?php
// home.php — Soulence Dating Website Landing Page
$site_name    = "Soulence";
$site_tagline = "Where Souls Find Their Silence";
$current_year = date("Y");
$login_url    = "login.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $site_name; ?> — <?php echo $site_tagline; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      /* gradient palette extracted */
      --grad-pink:   #ff6ec4;
      --grad-purple: #8b5cf6;
      --grad-blue:   #3b82f6;

      /* UI tokens that live ON the gradient */
      --glass-bg:        rgba(255, 255, 255, 0.14);
      --glass-bg-strong: rgba(255, 255, 255, 0.22);
      --glass-border:    rgba(255, 255, 255, 0.32);
      --glass-hover:     rgba(255, 255, 255, 0.28);

      /* Text on the gradient */
      --text-primary:   #ffffff;
      --text-secondary: rgba(255, 255, 255, 0.78);
      --text-muted:     rgba(255, 255, 255, 0.55);
      --text-accent:    #ffd6f0;   /* soft pink highlight */

      /* Accent for buttons / rings */
      --accent-pink:  #ff6ec4;
      --accent-light: #e8c8ff;
      --white:        #ffffff;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: linear-gradient(135deg, #ff6ec4, #8b5cf6, #3b82f6);
      background-attachment: fixed;
      min-height: 100vh;
      color: var(--text-primary);
      overflow-x: hidden;
      position: relative;
    }

    /* ── ambient glow orbs ── */
    body::before, body::after {
      content: '';
      position: fixed;
      border-radius: 50%;
      pointer-events: none;
      z-index: 0;
      filter: blur(90px);
      opacity: .35;
    }
    body::before {
      width: 560px; height: 560px;
      background: radial-gradient(circle, rgba(255,110,196,.7), transparent 70%);
      top: -180px; left: -180px;
      animation: drift 13s ease-in-out infinite alternate;
    }
    body::after {
      width: 460px; height: 460px;
      background: radial-gradient(circle, rgba(59,130,246,.65), transparent 70%);
      bottom: -130px; right: -130px;
      animation: drift 17s ease-in-out infinite alternate-reverse;
    }
    @keyframes drift {
      from { transform: translate(0, 0) scale(1); }
      to   { transform: translate(50px, 35px) scale(1.08); }
    }

    /* ── floating petals ── */
    .petals { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
    .petal {
      position: absolute;
      opacity: 0;
      animation: fall linear infinite;
    }
    @keyframes fall {
      0%   { transform: translateY(-60px) rotate(0deg);   opacity: 0; }
      10%  { opacity: .55; }
      90%  { opacity: .25; }
      100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
    }

    /* ── nav ── */
    nav {
      position: fixed; top: 0; width: 100%; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 36px;
      backdrop-filter: blur(18px) saturate(1.4);
      background: var(--glass-bg);
      border-bottom: 1px solid var(--glass-border);
    }
    .logo {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.75rem; font-weight: 600; letter-spacing: .04em;
      color: var(--white);
      position: relative;
    }
    .logo span { color: var(--text-accent); font-style: italic; }
    .logo::after {
      content: '✦';
      font-size: .6rem; color: rgba(255,255,255,.6);
      position: absolute; top: 4px; right: -16px;
    }
    .nav-login {
      font-size: .82rem; font-weight: 500; letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--white);
      text-decoration: none;
      padding: 9px 26px;
      border: 1.5px solid rgba(255,255,255,.55);
      border-radius: 50px;
      backdrop-filter: blur(8px);
      transition: all .3s ease;
    }
    .nav-login:hover {
      background: rgba(255,255,255,.22);
      border-color: rgba(255,255,255,.9);
      transform: translateY(-1px);
      box-shadow: 0 6px 24px rgba(0,0,0,.15);
    }

    /* ── hero ── */
    .hero {
      position: relative; z-index: 1;
      min-height: 100vh;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      text-align: center;
      padding: 120px 24px 60px;
    }

    .eyebrow {
      font-size: .72rem; letter-spacing: .28em; text-transform: uppercase;
      color: var(--text-accent); margin-bottom: 24px;
      opacity: 0; animation: fadeUp .7s .2s forwards;
      display: flex; align-items: center; gap: 12px;
    }
    .eyebrow::before, .eyebrow::after {
      content: ''; display: block; width: 30px; height: 1px;
      background: linear-gradient(90deg, transparent, var(--text-accent));
    }
    .eyebrow::after { background: linear-gradient(90deg, var(--text-accent), transparent); }

    h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(3.2rem, 9vw, 7rem);
      font-weight: 300; line-height: 1.06;
      letter-spacing: -.01em;
      color: var(--white);
      text-shadow: 0 2px 40px rgba(0,0,0,.18);
      opacity: 0; animation: fadeUp .8s .35s forwards;
    }
    h1 em {
      font-style: italic;
      background: linear-gradient(90deg, #ffd6f0, #e8c8ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .tagline {
      margin-top: 28px;
      font-size: clamp(.95rem, 2.4vw, 1.15rem);
      font-weight: 300; line-height: 1.8;
      color: var(--text-secondary); max-width: 540px;
      opacity: 0; animation: fadeUp .8s .5s forwards;
    }
    .tagline em { color: var(--text-accent); font-style: italic; }

    .cta-group {
      margin-top: 48px; display: flex; gap: 16px; flex-wrap: wrap; justify-content: center;
      opacity: 0; animation: fadeUp .8s .65s forwards;
    }
    .btn-primary {
      display: inline-block; text-decoration: none;
      padding: 16px 42px; border-radius: 50px;
      background: rgba(255, 255, 255, 0.95);
      color: #8b5cf6;
      font-size: .88rem; font-weight: 600;
      letter-spacing: .07em; text-transform: uppercase;
      box-shadow: 0 8px 32px rgba(0,0,0,.2), 0 0 0 0 rgba(255,255,255,.4);
      transition: all .35s ease;
      position: relative; overflow: hidden;
    }
    .btn-primary::after {
      content: '';
      position: absolute; inset: 0;
      background: rgba(139,92,246,.08);
      transform: translateX(-100%) skewX(-12deg);
      transition: transform .4s ease;
    }
    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 44px rgba(0,0,0,.25);
      background: #fff;
    }
    .btn-primary:hover::after { transform: translateX(120%) skewX(-12deg); }

    .btn-ghost {
      display: inline-block; text-decoration: none;
      padding: 16px 42px; border-radius: 50px;
      border: 1.5px solid rgba(255,255,255,.65);
      color: var(--white);
      font-size: .88rem; font-weight: 500;
      letter-spacing: .07em; text-transform: uppercase;
      backdrop-filter: blur(8px);
      transition: all .35s ease;
    }
    .btn-ghost:hover {
      background: rgba(255,255,255,.18);
      border-color: rgba(255,255,255,.9);
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0,0,0,.12);
    }

    /* ── decorative rings ── */
    .ring {
      position: absolute; border-radius: 50%;
      border: 1px solid rgba(255,255,255,.14);
      pointer-events: none;
      animation: pulse 6s ease-in-out infinite;
    }
    .ring:nth-child(1) { width: 340px; height: 340px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 0s; }
    .ring:nth-child(2) { width: 530px; height: 530px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 1s; }
    .ring:nth-child(3) { width: 720px; height: 720px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 2s; }
    @keyframes pulse {
      0%,100% { opacity: .18; transform: translate(-50%,-50%) scale(1); }
      50%      { opacity: .42; transform: translate(-50%,-50%) scale(1.03); }
    }

    /* ── scroll hint ── */
    .scroll-hint {
      margin-top: 70px;
      display: flex; flex-direction: column; align-items: center; gap: 8px;
      color: var(--text-muted); font-size: .72rem;
      letter-spacing: .16em; text-transform: uppercase;
      opacity: 0; animation: fadeUp .8s 1s forwards;
    }
    .scroll-hint .line {
      width: 1px; height: 48px;
      background: linear-gradient(to bottom, rgba(255,255,255,.7), transparent);
      animation: lineGrow 2s ease-in-out infinite;
    }
    @keyframes lineGrow {
      0%,100% { transform: scaleY(1); opacity: .6; }
      50%      { transform: scaleY(1.35); opacity: 1; }
    }

    /* ── features ── */
    .features {
      position: relative; z-index: 1;
      padding: 100px 24px 80px;
      display: flex; flex-direction: column; align-items: center; gap: 24px;
    }
    .section-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 300;
      color: var(--white);
      text-align: center; margin-bottom: 10px;
      text-shadow: 0 2px 20px rgba(0,0,0,.15);
    }
    .section-sub {
      text-align: center;
      color: var(--text-secondary);
      font-size: .95rem; max-width: 420px; margin-bottom: 48px;
    }
    .cards {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px; width: 100%; max-width: 960px;
    }
    .card {
      background: var(--glass-bg);
      backdrop-filter: blur(24px) saturate(1.3);
      border: 1px solid var(--glass-border);
      border-radius: 24px; padding: 36px 28px;
      transition: transform .35s ease, box-shadow .35s ease, background .35s ease;
    }
    .card:hover {
      transform: translateY(-7px);
      background: var(--glass-hover);
      box-shadow: 0 24px 56px rgba(0,0,0,.2);
    }
    .card-icon { font-size: 2rem; margin-bottom: 18px; display: block; }
    .card h3 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.42rem; font-weight: 600;
      color: var(--white);
      margin-bottom: 10px;
    }
    .card p {
      font-size: .88rem;
      color: var(--text-secondary);
      line-height: 1.75;
    }

    /* ── quote strip ── */
    .quote-strip {
      position: relative; z-index: 1;
      background: var(--glass-bg-strong);
      backdrop-filter: blur(28px) saturate(1.4);
      border: 1px solid var(--glass-border);
      border-radius: 28px;
      padding: 56px 44px;
      text-align: center; max-width: 760px;
      margin: 0 auto 80px;
      box-shadow: 0 16px 50px rgba(0,0,0,.14);
    }
    .quote-strip::before {
      content: '\201C';
      position: absolute; top: 16px; left: 32px;
      font-family: 'Cormorant Garamond', serif;
      font-size: 6rem; line-height: 1;
      color: rgba(255,255,255,.18);
      pointer-events: none;
    }
    .quote-strip blockquote {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.3rem, 3.5vw, 1.95rem);
      font-style: italic; font-weight: 300;
      color: var(--white); line-height: 1.55;
    }
    .quote-strip cite {
      display: block; margin-top: 20px;
      font-size: .78rem; letter-spacing: .14em; text-transform: uppercase;
      color: var(--text-accent); font-style: normal;
    }

    /* ── login section ── */
    .login-section {
      position: relative; z-index: 1;
      text-align: center; padding: 20px 24px 100px;
    }
    .login-section .section-sub { margin: 12px auto 36px; max-width: 360px; }
    .login-section p.note {
      font-size: .9rem; color: var(--text-secondary); margin-top: 18px;
    }
    .login-section p.note a {
      color: var(--white); font-weight: 500;
      text-decoration: underline; text-underline-offset: 3px;
    }

    /* ── footer ── */
    footer {
      position: relative; z-index: 1;
      text-align: center; padding: 28px 24px;
      border-top: 1px solid rgba(255,255,255,.18);
      background: rgba(0,0,0,.08);
      backdrop-filter: blur(12px);
    }
    footer p { font-size: .78rem; color: var(--text-muted); letter-spacing: .05em; }
    footer .heart {
      color: var(--text-accent);
      animation: heartbeat 1.4s ease-in-out infinite;
      display: inline-block;
    }
    @keyframes heartbeat {
      0%,100% { transform: scale(1); }
      14%      { transform: scale(1.28); }
      28%      { transform: scale(1); }
      42%      { transform: scale(1.16); }
    }

    /* ── base animation ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── mobile ── */
    @media (max-width: 480px) {
      nav { padding: 14px 18px; }
      .logo { font-size: 1.4rem; }
      .cta-group { flex-direction: column; align-items: center; }
      .btn-primary, .btn-ghost { width: 100%; max-width: 280px; text-align: center; }
      .ring:nth-child(3) { display: none; }
      .quote-strip { padding: 40px 24px; margin: 0 12px 60px; }
      .quote-strip::before { font-size: 4rem; }
    }
  </style>
</head>
<body>

  <!-- floating petals -->
  <div class="petals" id="petals"></div>

  <!-- nav -->
  <nav>
    <div class="logo">
      <?php echo substr($site_name, 0, 4); ?><span><?php echo substr($site_name, 4); ?></span>
    </div>
    <a href="<?php echo $login_url; ?>" class="nav-login">Login</a>
  </nav>

  <!-- hero -->
  <section class="hero">
    <div class="ring"></div>
    <div class="ring"></div>
    <div class="ring"></div>

    <p class="eyebrow">Find your person</p>
    <h1>Love that feels like<br><em>coming home.</em></h1>
    <p class="tagline">
      <?php echo $site_name; ?> is where depth meets desire. We don't match you by swipes —
      we connect you by silence, by resonance, by the quiet things that make you, <em>you</em>.
    </p>
    <div class="cta-group">
      <a href="<?php echo $login_url; ?>" class="btn-primary">Begin Your Journey</a>
      <a href="#discover" class="btn-ghost">Discover More</a>
    </div>
    <div class="scroll-hint">
      <span>Scroll</span>
      <span class="line"></span>
    </div>
  </section>

  <!-- features -->
  <section class="features" id="discover">
    <h2 class="section-title">Crafted for real connection</h2>
    <p class="section-sub">Every feature on <?php echo $site_name; ?> is built around one truth: love isn't loud.</p>

    <?php
    $features = [
      ["icon" => "🌸", "title" => "Soul Matching",            "desc" => "Our algorithm listens to the language of your values, not just your photos. Meet someone who gets your silence as much as your words."],
      ["icon" => "🔒", "title" => "Safe &amp; Private",       "desc" => "Your story belongs to you. Soulence protects every message, every moment — with end-to-end security you can trust."],
      ["icon" => "✦",  "title" => "Intentional Profiles",     "desc" => "No endless swiping. Curated daily matches that respect your time and honor the weight of who you are looking for."],
      ["icon" => "💬", "title" => "Meaningful Conversations",  "desc" => "Ice-breakers designed by psychologists. Start with something real, not small talk — and let the rest unfold naturally."],
    ];
    ?>

    <div class="cards">
      <?php foreach ($features as $feature): ?>
        <div class="card">
          <span class="card-icon"><?php echo $feature['icon']; ?></span>
          <h3><?php echo $feature['title']; ?></h3>
          <p><?php echo $feature['desc']; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- quote strip -->
  <div class="quote-strip">
    <blockquote>
      "The right person won't complete your sentences — they'll sit comfortably in the space between them."
    </blockquote>
    <cite>— The <?php echo $site_name; ?> Philosophy</cite>
  </div>

  <!-- login CTA -->
  <section class="login-section">
    <h2 class="section-title">Ready to feel it?</h2>
    <p class="section-sub">
      Thousands of quiet souls are already waiting. Your story starts with one small step.
    </p>
    <a href="<?php echo $login_url; ?>" class="btn-primary" style="font-size:.95rem; padding: 18px 54px;">
      Login to <?php echo $site_name; ?>
    </a>
    <p class="note">
      New here?
      <a href="<?php echo $login_url; ?>">Create your free account &rarr;</a>
    </p>
  </section>

  <!-- footer -->
  <footer>
    <p>
      &copy; <?php echo $current_year; ?> <?php echo $site_name; ?>.
      Made with <span class="heart">&#9829;</span> for every soul still searching.<br>
      All rights reserved. | Privacy Policy | Terms of Service
    </p>
  </footer>

  <script>
    // generate floating petals
    const container = document.getElementById('petals');
    const colors = [
      'rgba(255,182,193,0.6)',
      'rgba(216,180,254,0.5)',
      'rgba(147,197,253,0.5)',
      'rgba(255,110,196,0.4)',
      'rgba(255,255,255,0.35)',
      'rgba(196,181,253,0.5)'
    ];
    for (let i = 0; i < 26; i++) {
      const p = document.createElement('div');
      p.className = 'petal';
      const size = Math.random() * 14 + 7;
      p.style.cssText = `
        width:${size}px; height:${size}px;
        background:${colors[Math.floor(Math.random() * colors.length)]};
        left:${Math.random() * 100}%;
        animation-duration:${Math.random() * 14 + 10}s;
        animation-delay:${Math.random() * 16}s;
        border-radius:${Math.random() > 0.5 ? '50% 0 50% 0' : '50%'};
        opacity:0;
      `;
      container.appendChild(p);
    }

    // card reveal on scroll
    const cards = document.querySelectorAll('.card');
    const io = new IntersectionObserver(entries => {
      entries.forEach((e, i) => {
        if (e.isIntersecting) {
          setTimeout(() => {
            e.target.style.transition = 'opacity .6s ease, transform .6s ease';
            e.target.style.opacity = '1';
            e.target.style.transform = 'translateY(0)';
          }, i * 120);
          io.unobserve(e.target);
        }
      });
    }, { threshold: .15 });
    cards.forEach(c => {
      c.style.opacity = '0';
      c.style.transform = 'translateY(24px)';
      io.observe(c);
    });
  </script>

</body>
</html>