<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shipping & Returns — Injili Apparel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
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
.page-hero{background:var(--navy);padding:4rem 2rem;text-align:center}
.page-eyebrow{font-size:.7rem;letter-spacing:.35em;text-transform:uppercase;
              color:var(--gold);display:block;margin-bottom:1rem}
.page-title{font-family:var(--fd);font-size:clamp(2.5rem,5vw,4rem);
            font-weight:300;color:var(--white);margin-bottom:.75rem}
.page-sub{font-size:.9rem;color:rgba(255,255,255,.5);line-height:1.7}
.page-content{flex:1;max-width:860px;margin:0 auto;
              padding:4rem 2rem;width:100%}
.section-title{font-family:var(--fd);font-size:1.6rem;font-weight:300;
               color:var(--navy);margin-bottom:1.25rem}
.placeholder-note{background:rgba(201,138,65,.08);
                  border:1px solid rgba(201,138,65,.25);
                  border-radius:3px;padding:1rem 1.25rem;
                  font-size:.8rem;color:var(--gold-dk);
                  margin-bottom:2rem;line-height:1.6}
.placeholder-note i{margin-right:.4rem}
.info-block{background:var(--white);border:1px solid var(--stone);
            border-radius:4px;padding:1.75rem 2rem;margin-bottom:2rem}
.info-row{display:flex;align-items:flex-start;gap:1.5rem;
          padding:.8rem 0;border-bottom:1px solid var(--stone);
          font-size:.88rem}
.info-row:last-child{border-bottom:none}
.info-label{color:var(--muted);flex-shrink:0;min-width:140px;
            font-size:.78rem;letter-spacing:.05em;padding-top:2px}
.info-val{color:var(--navy);line-height:1.6}
.info-val strong{font-weight:500}
.delivery-cards{display:grid;grid-template-columns:repeat(3,1fr);
                gap:1rem;margin-bottom:2.5rem}
.delivery-card{background:var(--white);border:1px solid var(--stone);
               border-radius:4px;padding:1.5rem;text-align:center}
.delivery-card-icon{font-size:1.4rem;color:var(--gold);
                    margin-bottom:.75rem;display:block}
.delivery-card-name{font-size:.82rem;font-weight:500;color:var(--navy);
                    margin-bottom:.35rem}
.delivery-card-desc{font-size:.75rem;color:var(--muted);
                    line-height:1.5;margin-bottom:.5rem}
.delivery-card-price{font-size:.85rem;font-weight:500;color:var(--navy)}
.delivery-card-price.free{color:#1e8449}
.policy-section{margin-bottom:2.5rem}
.policy-body{font-size:.88rem;color:var(--navy);line-height:1.85}
.policy-body p{margin-bottom:1rem}
.policy-body ul{padding-left:1.25rem;margin-bottom:1rem}
.policy-body ul li{margin-bottom:.4rem}
.policy-body strong{font-weight:500}
.contact-cta{background:var(--navy);border-radius:4px;padding:2rem;
             text-align:center;margin-top:2rem}
.contact-cta p{font-size:.88rem;color:rgba(255,255,255,.6);
               margin-bottom:1.25rem;line-height:1.6}
.cta-btn{display:inline-flex;align-items:center;gap:.5rem;
         background:var(--gold);color:var(--white);padding:.85rem 1.75rem;
         border-radius:2px;font-size:.75rem;letter-spacing:.12em;
         text-transform:uppercase;font-weight:500;font-family:var(--fb);
         transition:background .2s}
.cta-btn:hover{background:var(--gold-dk)}
footer{background:var(--navy);padding:2rem;text-align:center}
footer p{font-size:.75rem;color:rgba(255,255,255,.3)}
@media(max-width:600px){
  .delivery-cards{grid-template-columns:1fr}
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
  <span class="page-eyebrow">Delivery & Returns</span>
  <h1 class="page-title">Shipping & Returns</h1>
  <p class="page-sub">
    Everything you need to know about getting your order<br>
    and what to do if something is not right.
  </p>
</div>

<div class="page-content">

  <div class="placeholder-note">
    <i class="fa-solid fa-circle-info"></i>
    Policy details to be confirmed and updated by Clarice.
    The information below reflects the intended business model
    and should be reviewed before going live.
  </div>

  <!-- Delivery options -->
  <h2 class="section-title">Delivery Options</h2>
  <div class="delivery-cards">
    <div class="delivery-card">
      <i class="fa-solid fa-store delivery-card-icon"></i>
      <div class="delivery-card-name">Agent Pickup</div>
      <div class="delivery-card-desc">
        Collect from your nearest Pickup Mtaani agent at your convenience
      </div>
      <div class="delivery-card-price free">Free</div>
    </div>
    <div class="delivery-card">
      <i class="fa-solid fa-truck delivery-card-icon"></i>
      <div class="delivery-card-name">Doorstep Delivery</div>
      <div class="delivery-card-desc">
        Delivered to your address within Nairobi and surrounds
      </div>
      <div class="delivery-card-price">KSh 200</div>
    </div>
    <div class="delivery-card">
      <i class="fa-solid fa-box delivery-card-icon"></i>
      <div class="delivery-card-name">Nationwide</div>
      <div class="delivery-card-desc">
        Delivery outside Nairobi via Pickup Mtaani courier network
      </div>
      <div class="delivery-card-price">Calculated at checkout</div>
    </div>
  </div>

  <!-- Shipping info -->
  <h2 class="section-title">Shipping Information</h2>
  <div class="info-block">
    <div class="info-row">
      <span class="info-label">Production time</span>
      <span class="info-val">
        <strong>3 – 5 business days</strong> after payment confirmation.
        All orders are made to order — your tee is printed specifically for you.
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Delivery time</span>
      <span class="info-val">
        <strong>1 – 3 business days</strong> after dispatch,
        depending on your location.
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Free delivery</span>
      <span class="info-val">
        On all orders over <strong>KSh 5,000</strong>.
        Agent pickup is always free regardless of order value.
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Order tracking</span>
      <span class="info-val">
        You will receive an SMS and email with your tracking link
        as soon as your order is dispatched via Pickup Mtaani.
        Track at any time at
        <a href="track.php" style="color:var(--gold)">injiliapparel.com/track</a>.
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Order updates</span>
      <span class="info-val">
        We will keep you updated by SMS and email at every key stage —
        when your order is received, in production, and dispatched.
      </span>
    </div>
  </div>

  <!-- Returns policy -->
  <div class="policy-section">
    <h2 class="section-title">Returns & Exchanges</h2>
    <div class="info-block">
      <div class="policy-body">
        <p>
          Because every Injili piece is <strong>made to order</strong>
          specifically for you, we are unable to accept returns or
          exchanges for change of mind or incorrect size selection.
          We strongly recommend consulting the
          <a href="size-guide.php" style="color:var(--gold)">
            Size Guide
          </a>
          before placing your order.
        </p>
        <p>
          However, we will always make it right if something goes wrong
          on our end. We accept returns and offer full replacements in
          the following cases:
        </p>
        <ul>
          <li>The item arrived damaged or with a print defect</li>
          <li>The wrong item or size was sent</li>
          <li>The item was lost in transit</li>
        </ul>
        <p>
          <strong>To raise a return or replacement:</strong> contact us
          within <strong>7 days</strong> of receiving your order via
          WhatsApp or email with your order number and a photo of the issue.
          We will resolve it within 3 business days.
        </p>
      </div>
    </div>
  </div>

  <!-- Refunds -->
  <h2 class="section-title">Refunds</h2>
  <div class="info-block">
    <div class="info-row">
      <span class="info-label">Refund method</span>
      <span class="info-val">
        Refunds are processed back to the original M-Pesa number
        used for payment.
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Processing time</span>
      <span class="info-val">
        <strong>3 – 5 business days</strong> after the return
        is confirmed and approved.
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Cancellations</span>
      <span class="info-val">
        Orders can be cancelled within <strong>2 hours</strong>
        of placement for a full refund, before production begins.
        Contact us immediately via WhatsApp to cancel.
      </span>
    </div>
  </div>

  <!-- CTA -->
  <div class="contact-cta">
    <p>
      Have a question about your order or our shipping policy?<br>
      We are here to help.
    </p>
    <a href="https://wa.me/254716341540?text=Hi%20Injili!%20I%20have%20a%20question%20about%20shipping%20or%20returns."
       class="cta-btn" target="_blank">
      <i class="fa-brands fa-whatsapp"></i>
      Chat with Us on WhatsApp
    </a>
  </div>

</div>

<footer>
  <p>© 2025 Injili Apparel · Nairobi, Kenya · Faith — Purpose — Style</p>
</footer>

</body>
</html>