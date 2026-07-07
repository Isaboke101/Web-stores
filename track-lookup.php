<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track My Order — Injili Apparel</title>
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

.page-content{flex:1;max-width:600px;margin:0 auto;
              padding:4rem 2rem;width:100%}

/* Search form */
.search-card{background:var(--white);border:1px solid var(--stone);
             border-radius:4px;padding:2.5rem;text-align:center}
.search-icon{font-size:2.5rem;color:var(--stone);margin-bottom:1.25rem}
.search-title{font-family:var(--fd);font-size:1.6rem;font-weight:300;
              color:var(--navy);margin-bottom:.5rem}
.search-sub{font-size:.85rem;color:var(--muted);margin-bottom:2rem;
            line-height:1.6}
.search-form{display:flex;gap:0}
.search-input{flex:1;padding:.9rem 1.1rem;border:1px solid var(--stone);
              border-right:none;background:var(--cream);color:var(--navy);
              font-size:.9rem;font-family:var(--fb);border-radius:2px 0 0 2px;
              outline:none;transition:border-color .2s}
.search-input:focus{border-color:var(--gold);background:var(--white)}
.search-input::placeholder{color:var(--muted)}
.search-btn{background:var(--navy);color:var(--white);border:none;
            padding:.9rem 1.5rem;font-size:.75rem;letter-spacing:.15em;
            text-transform:uppercase;font-weight:500;font-family:var(--fb);
            cursor:pointer;border-radius:0 2px 2px 0;
            white-space:nowrap;transition:background .2s}
.search-btn:hover{background:#252D35}

/* Error state */
.error-card{background:var(--white);border:1px solid var(--stone);
            border-radius:4px;padding:2.5rem;text-align:center}
.error-icon{font-size:2rem;color:#c0392b;margin-bottom:1rem;display:block}
.error-title{font-family:var(--fd);font-size:1.4rem;font-weight:300;
             color:var(--navy);margin-bottom:.5rem}
.error-msg{font-size:.85rem;color:var(--muted);margin-bottom:1.5rem;
           line-height:1.6}
.btn-try-again{display:inline-flex;align-items:center;gap:.5rem;
               background:var(--navy);color:var(--white);padding:.8rem 1.75rem;
               border-radius:2px;font-size:.75rem;letter-spacing:.12em;
               text-transform:uppercase;font-weight:500;font-family:var(--fb);
               transition:background .2s}
.btn-try-again:hover{background:#252D35}

/* Help links */
.help-row{display:flex;align-items:center;justify-content:center;
          gap:1.5rem;margin-top:2rem;flex-wrap:wrap}
.help-link{font-size:.78rem;color:var(--muted);display:flex;
           align-items:center;gap:.35rem;transition:color .2s}
.help-link:hover{color:var(--gold)}
.help-link i{font-size:.75rem}

footer{background:var(--navy);padding:2rem;text-align:center}
footer p{font-size:.75rem;color:rgba(255,255,255,.3)}
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
  <span class="page-eyebrow">Where Is My Order?</span>
  <h1 class="page-title">Track My Order</h1>
  <p class="page-sub">
    Enter your order number below to see the latest<br>
    status and delivery information for your order.
  </p>
</div>

<div class="page-content">

<?php
/* ── If an order number was submitted, redirect to track.php ──── */
$submitted = trim($_GET['order'] ?? $_POST['order'] ?? '');

if ($submitted):
    /* Sanitise — order numbers are alphanumeric with hyphens only */
    $clean = preg_replace('/[^A-Za-z0-9\-]/', '', $submitted);

    if ($clean):
        /* Redirect to the existing detailed tracking page */
        header('Location: track.php?order=' . urlencode($clean));
        exit;
    else:
?>
  <!-- Invalid characters in submitted value -->
  <div class="error-card">
    <i class="fa-solid fa-circle-exclamation error-icon"></i>
    <div class="error-title">Invalid Order Number</div>
    <p class="error-msg">
      The order number you entered contains invalid characters.<br>
      Your order number looks like this: <strong>INJ-2026-0001</strong>
    </p>
    <a href="track-lookup.php" class="btn-try-again">
      <i class="fa-solid fa-rotate-left"></i> Try Again
    </a>
  </div>

<?php
    endif;
else:
?>
  <!-- Default state — show the search form -->
  <div class="search-card">
    <div class="search-icon">
      <i class="fa-solid fa-box-open"></i>
    </div>
    <div class="search-title">Find Your Order</div>
    <p class="search-sub">
      Your order number was included in your confirmation email and SMS.
      It looks like this: <strong>INJ-2026-0001</strong>
    </p>

    <form method="GET" action="track-lookup.php" class="search-form">
      <input class="search-input"
             type="text"
             name="order"
             placeholder="e.g. INJ-2026-0001"
             autocomplete="off"
             autocapitalize="characters"
             required>
      <button class="search-btn" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i> Track
      </button>
    </form>

    <div class="help-row">
      <a href="contact.php" class="help-link">
        <i class="fa-regular fa-envelope"></i>
        Can't find your order number?
      </a>
      <a href="https://wa.me/254716341540?text=Hi%20Injili!%20I%20need%20help%20tracking%20my%20order."
         class="help-link" target="_blank">
        <i class="fa-brands fa-whatsapp"></i>
        Chat with us
      </a>
    </div>
  </div>

<?php endif; ?>

</div>

<footer>
  <p>© 2025 Injili Apparel · Nairobi, Kenya · Faith — Purpose — Style</p>
</footer>

</body>
</html>