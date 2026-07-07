<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Size Guide — Injili Apparel</title>
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

/* Size table */
.size-table-wrap{overflow-x:auto;margin-bottom:2.5rem}
.size-table{width:100%;border-collapse:collapse;
            background:var(--white);border-radius:4px;
            overflow:hidden;font-size:.85rem}
.size-table th{background:var(--navy);color:var(--white);
               padding:.85rem 1rem;text-align:center;
               font-size:.65rem;letter-spacing:.15em;
               text-transform:uppercase;font-weight:400}
.size-table th:first-child{text-align:left}
.size-table td{padding:.8rem 1rem;border-bottom:1px solid var(--stone);
               text-align:center;color:var(--navy)}
.size-table td:first-child{text-align:left;font-weight:500}
.size-table tr:last-child td{border-bottom:none}
.size-table tr:nth-child(even) td{background:#fafaf9}
.size-highlight{color:var(--gold);font-weight:500}

/* How to measure */
.measure-grid{display:grid;grid-template-columns:1fr 1fr;
              gap:1rem;margin-bottom:2.5rem}
.measure-card{background:var(--white);border:1px solid var(--stone);
              border-radius:4px;padding:1.5rem}
.measure-icon{font-size:1.25rem;color:var(--gold);margin-bottom:.75rem;
              display:block}
.measure-name{font-size:.82rem;font-weight:500;color:var(--navy);
              margin-bottom:.4rem}
.measure-desc{font-size:.78rem;color:var(--muted);line-height:1.6}

/* Tips */
.tip-list{list-style:none;display:flex;flex-direction:column;gap:.75rem;
          margin-bottom:2.5rem}
.tip-list li{display:flex;align-items:flex-start;gap:.75rem;
             font-size:.88rem;color:var(--navy);line-height:1.6;
             padding:1rem;background:var(--white);
             border:1px solid var(--stone);border-radius:4px}
.tip-list li i{color:var(--gold);flex-shrink:0;margin-top:2px}

footer{background:var(--navy);padding:2rem;text-align:center}
footer p{font-size:.75rem;color:rgba(255,255,255,.3)}

@media(max-width:600px){
  .measure-grid{grid-template-columns:1fr}
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
  <span class="page-eyebrow">Find Your Fit</span>
  <h1 class="page-title">Size Guide</h1>
  <p class="page-sub">
    All Injili pieces are unisex with a relaxed fit.<br>
    Use the measurements below to find your perfect size.
  </p>
</div>

<div class="page-content">

  <div class="placeholder-note">
    <i class="fa-solid fa-circle-info"></i>
    Exact measurements to be confirmed and updated by Clarice
    once the final garment specifications are set.
    Values below are industry-standard estimates for reference.
  </div>

  <!-- Size chart -->
  <h2 class="section-title">Size Chart — Unisex Relaxed Fit</h2>
  <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem;
            line-height:1.7">
    All measurements are in centimetres (cm).
    When between sizes, we recommend sizing up for the relaxed look.
  </p>

  <div class="size-table-wrap">
    <table class="size-table">
      <thead>
        <tr>
          <th>Size</th>
          <th>Chest</th>
          <th>Length</th>
          <th>Sleeve</th>
          <th>Shoulder</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>S</td>
          <td>96 – 100</td>
          <td>68</td>
          <td>20</td>
          <td>43</td>
        </tr>
        <tr>
          <td>M <span class="size-highlight">★</span></td>
          <td>100 – 104</td>
          <td>71</td>
          <td>21</td>
          <td>45</td>
        </tr>
        <tr>
          <td>L <span class="size-highlight">★</span></td>
          <td>104 – 110</td>
          <td>73</td>
          <td>22</td>
          <td>47</td>
        </tr>
        <tr>
          <td>XL</td>
          <td>110 – 116</td>
          <td>75</td>
          <td>23</td>
          <td>49</td>
        </tr>
        <tr>
          <td>XXL</td>
          <td>116 – 124</td>
          <td>77</td>
          <td>24</td>
          <td>51</td>
        </tr>
      </tbody>
    </table>
  </div>
  <p style="font-size:.72rem;color:var(--muted);margin-bottom:2.5rem">
    ★ M and L are our most popular sizes.
  </p>

  <!-- How to measure -->
  <h2 class="section-title">How to Measure Yourself</h2>
  <div class="measure-grid">
    <div class="measure-card">
      <i class="fa-solid fa-arrows-left-right measure-icon"></i>
      <div class="measure-name">Chest</div>
      <div class="measure-desc">
        Measure around the fullest part of your chest,
        keeping the tape horizontal and parallel to the ground.
      </div>
    </div>
    <div class="measure-card">
      <i class="fa-solid fa-arrows-up-down measure-icon"></i>
      <div class="measure-name">Length</div>
      <div class="measure-desc">
        Measure from the highest point of the shoulder
        straight down to the hem at the bottom of the tee.
      </div>
    </div>
    <div class="measure-card">
      <i class="fa-solid fa-ruler measure-icon"></i>
      <div class="measure-name">Sleeve</div>
      <div class="measure-desc">
        Measure from the shoulder seam down to the
        edge of the sleeve opening.
      </div>
    </div>
    <div class="measure-card">
      <i class="fa-solid fa-ruler-horizontal measure-icon"></i>
      <div class="measure-name">Shoulder</div>
      <div class="measure-desc">
        Measure from shoulder seam to shoulder seam
        across the back of the garment.
      </div>
    </div>
  </div>

  <!-- Fit tips -->
  <h2 class="section-title">Fit Tips</h2>
  <ul class="tip-list">
    <li>
      <i class="fa-solid fa-check"></i>
      All Injili tees are cut with a <strong>relaxed unisex fit</strong>.
      They are designed to sit comfortably, not skin-tight.
    </li>
    <li>
      <i class="fa-solid fa-check"></i>
      If you prefer a more oversized look,
      <strong>size up by one</strong>.
    </li>
    <li>
      <i class="fa-solid fa-check"></i>
      Our tees are made from pre-shrunk cotton so
      <strong>minimal shrinkage</strong> is expected after washing.
    </li>
    <li>
      <i class="fa-solid fa-check"></i>
      Not sure? <a href="contact.php"
                   style="color:var(--gold)">Send us a message</a>
      — we are happy to help you choose the right size.
    </li>
  </ul>

</div>

<footer>
  <p>© 2025 Injili Apparel · Nairobi, Kenya · Faith — Purpose — Style</p>
</footer>

</body>
</html>