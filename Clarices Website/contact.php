<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — Injili Apparel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --navy:#1C2329;--gold:#C98A41;--gold-dk:#a87232;
  --cream:#F8F5F0;--stone:#EDEDE9;--white:#FFFFFF;--muted:#888882;
  --fd:'Cormorant Garamond',Georgia,serif;
  --fb:'DM Sans',system-ui,sans-serif;
}
body{background:var(--cream);color:var(--navy);font-family:var(--fb);
     font-weight:300;min-height:100vh;display:flex;flex-direction:column}
a{text-decoration:none;color:inherit}

/* ── Navbar ── */
.nav{position:sticky;top:0;z-index:100;background:var(--navy);
     border-bottom:1px solid rgba(201,138,65,.12)}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 2rem;height:64px;
           display:flex;align-items:center;justify-content:space-between}
.logo-word{font-family:var(--fd);font-size:1.4rem;font-weight:500;
           color:var(--white);letter-spacing:.05em}
.logo-sub{font-size:.52rem;letter-spacing:.35em;text-transform:uppercase;
          color:var(--gold);display:block;margin-top:2px}
.nav-back{font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;
          color:rgba(255,255,255,.6);display:flex;align-items:center;
          gap:.4rem;transition:color .2s}
.nav-back:hover{color:var(--gold)}

/* ── Hero ── */
.page-hero{background:var(--navy);padding:4rem 2rem;text-align:center}
.page-eyebrow{font-size:.7rem;letter-spacing:.35em;text-transform:uppercase;
              color:var(--gold);display:block;margin-bottom:1rem}
.page-title{font-family:var(--fd);font-size:clamp(2.5rem,5vw,4rem);
            font-weight:300;color:var(--white);margin-bottom:.75rem}
.page-sub{font-size:.9rem;color:rgba(255,255,255,.5);line-height:1.7}

/* ── Content ── */
.page-content{flex:1;max-width:900px;margin:0 auto;
              padding:4rem 2rem;width:100%}
.contact-grid{display:grid;grid-template-columns:1fr 1fr;
              gap:2rem;margin-bottom:3rem}
.contact-card{background:var(--white);border:1px solid var(--stone);
              border-radius:4px;padding:2rem;text-align:center}
.contact-icon{font-size:1.5rem;color:var(--gold);margin-bottom:1rem;
              display:block}
.contact-label{font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;
               color:var(--muted);margin-bottom:.5rem;display:block}
.contact-value{font-size:.95rem;color:var(--navy);font-weight:400;
               line-height:1.6}
.contact-value a{color:var(--gold);transition:color .2s}
.contact-value a:hover{color:var(--gold-dk)}

.section-title{font-family:var(--fd);font-size:1.6rem;font-weight:300;
               color:var(--navy);margin-bottom:1.5rem}
.info-block{background:var(--white);border:1px solid var(--stone);
            border-radius:4px;padding:2rem;margin-bottom:1.5rem}
.info-row{display:flex;justify-content:space-between;align-items:flex-start;
          padding:.75rem 0;border-bottom:1px solid var(--stone);
          font-size:.88rem;gap:1rem}
.info-row:last-child{border-bottom:none}
.info-label{color:var(--muted);flex-shrink:0;min-width:120px}
.info-val{color:var(--navy);text-align:right;line-height:1.5}

.wa-btn{display:inline-flex;align-items:center;gap:.6rem;
        background:#25D366;color:var(--white);padding:1rem 2rem;
        border-radius:2px;font-size:.78rem;letter-spacing:.12em;
        text-transform:uppercase;font-weight:500;
        transition:all .3s;font-family:var(--fb);margin-top:1.5rem}
.wa-btn:hover{background:#1ebe5d;transform:translateY(-2px)}

.placeholder-note{background:rgba(201,138,65,.08);
                  border:1px solid rgba(201,138,65,.25);
                  border-radius:3px;padding:1rem 1.25rem;
                  font-size:.8rem;color:var(--gold-dk);
                  margin-bottom:2rem;line-height:1.6}
.placeholder-note i{margin-right:.4rem}

/* ── Footer ── */
footer{background:var(--navy);padding:2rem;text-align:center}
footer p{font-size:.75rem;color:rgba(255,255,255,.3)}

@media(max-width:600px){
  .contact-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a href="index.html" class="logo">
      <span class="logo-word">injili</span>
      <span class="logo-sub">Apparel</span>
    </a>
    <a href="index.html" class="nav-back">
      <i class="fa-solid fa-arrow-left"></i> Back to Store
    </a>
  </div>
</nav>

<div class="page-hero">
  <span class="page-eyebrow">Get in Touch</span>
  <h1 class="page-title">Contact Us</h1>
  <p class="page-sub">
    We are a small, personal brand and we love hearing from you.<br>
    Reach out — we will get back to you as soon as we can.
  </p>
</div>

<div class="page-content">

  <div class="placeholder-note">
    <i class="fa-solid fa-circle-info"></i>
    Contact details to be updated once confirmed with Clarice.
    All links and numbers below are placeholders.
  </div>

  <!-- Contact cards -->
  <div class="contact-grid">
    <div class="contact-card">
      <i class="fa-brands fa-whatsapp contact-icon"></i>
      <span class="contact-label">WhatsApp</span>
      <div class="contact-value">
        <a href="https://wa.me/254716341540?text=Hi%20Injili!%20I%20have%20a%20question%20about..."
           target="_blank">+254 716 341 540</a><br>
        <small style="color:var(--muted);font-size:.78rem">
          Fastest way to reach us
        </small>
      </div>
    </div>
    <div class="contact-card">
      <i class="fa-regular fa-envelope contact-icon"></i>
      <span class="contact-label">Email</span>
      <div class="contact-value">
        <a href="mailto:hello@injiliapparel.com">
          hello@injiliapparel.com
        </a><br>
        <small style="color:var(--muted);font-size:.78rem">
          We reply within 24 hours
        </small>
      </div>
    </div>
    <div class="contact-card">
      <i class="fa-brands fa-instagram contact-icon"></i>
      <span class="contact-label">Instagram</span>
      <div class="contact-value">
        <a href="https://instagram.com/injiliapparel"
           target="_blank">@injiliapparel</a><br>
        <small style="color:var(--muted);font-size:.78rem">
          DMs open
        </small>
      </div>
    </div>
    <div class="contact-card">
      <i class="fa-solid fa-location-dot contact-icon"></i>
      <span class="contact-label">Location</span>
      <div class="contact-value">
        Nairobi, Kenya<br>
        <small style="color:var(--muted);font-size:.78rem">
          Orders delivered nationwide
        </small>
      </div>
    </div>
  </div>

  <!-- Business hours -->
  <h2 class="section-title">Business Hours</h2>
  <div class="info-block">
    <div class="info-row">
      <span class="info-label">Monday – Friday</span>
      <span class="info-val">9:00 AM – 6:00 PM</span>
    </div>
    <div class="info-row">
      <span class="info-label">Saturday</span>
      <span class="info-val">10:00 AM – 4:00 PM</span>
    </div>
    <div class="info-row">
      <span class="info-label">Sunday</span>
      <span class="info-val">Closed</span>
    </div>
    <div class="info-row">
      <span class="info-label">Response time</span>
      <span class="info-val">Usually within a few hours on WhatsApp</span>
    </div>
  </div>

  <!-- WhatsApp CTA -->
  <div style="text-align:center;padding:2rem 0">
    <h2 class="section-title" style="margin-bottom:.5rem">
      Quickest way to reach us
    </h2>
    <p style="font-size:.88rem;color:var(--muted);margin-bottom:.25rem">
      Tap below to start a WhatsApp conversation with us directly.
    </p>
    <a href="https://wa.me/254716341540?text=Hi%20Injili!%20I%20have%20a%20question%20about%20my%20order."
       class="wa-btn" target="_blank">
      <i class="fa-brands fa-whatsapp"></i>
      Chat on WhatsApp
    </a>
  </div>

</div>

<footer>
  <p>© 2025 Injili Apparel · Nairobi, Kenya · Faith — Purpose — Style</p>
</footer>

</body>
</html>