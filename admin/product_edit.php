<?php
/**
 * admin/product_edit.php — Add or edit a product
 *
 * GET ?id=0  → blank form to add a new product
 * GET ?id=N  → form pre-filled with existing product data
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$db        = getDB();
$productId = (int)($_GET['id'] ?? 0);
$isNew     = $productId === 0;
$product   = null;
$variants  = [];

if (!$isNew) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: products.php');
        exit;
    }

    $vStmt = $db->prepare(
        'SELECT * FROM product_variants
         WHERE product_id = :pid ORDER BY sort_order ASC'
    );
    $vStmt->execute([':pid' => $productId]);
    $variants = $vStmt->fetchAll();
}

$pageTitle = $isNew
    ? 'Add New Design'
    : 'Edit: ' . htmlspecialchars($product['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> — Injili Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
.edit-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 1.5rem;
  align-items: start;
}
.image-upload-area {
  border: 2px dashed var(--stone);
  border-radius: 4px;
  padding: 2rem 1.25rem;
  text-align: center;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  position: relative;
}
.image-upload-area:hover {
  border-color: var(--gold);
  background: rgba(201,138,65,.03);
}
.image-upload-area input[type=file] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}
.img-preview {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 3px;
  margin-bottom: .75rem;
  display: none;
}
.img-preview.show { display: block; }
.upload-icon { font-size: 2rem; color: var(--stone); margin-bottom: .6rem; }
.upload-hint {
  font-size: .78rem;
  color: var(--muted);
  line-height: 1.6;
}
.color-row {
  display: grid;
  grid-template-columns: 1fr 52px 36px;
  gap: .5rem;
  align-items: center;
  margin-bottom: .5rem;
}
.swatch-input {
  width: 100%;
  height: 38px;
  border: 1px solid var(--stone);
  border-radius: 2px;
  padding: 2px;
  cursor: pointer;
  background: var(--white);
}
.btn-rm {
  background: none;
  border: 1px solid var(--stone);
  color: #c0392b;
  width: 36px;
  height: 38px;
  border-radius: 2px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .8rem;
  transition: all .2s;
}
.btn-rm:hover { background:#c0392b; color:var(--white); border-color:#c0392b; }
.btn-add-color {
  width: 100%;
  background: none;
  border: 1px dashed var(--stone);
  color: var(--muted);
  padding: .6rem;
  border-radius: 2px;
  cursor: pointer;
  font-size: .78rem;
  font-family: var(--fb);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  margin-top: .4rem;
  transition: all .2s;
}
.btn-add-color:hover { border-color:var(--navy); color:var(--navy); }
.save-bar {
  position: sticky;
  bottom: 0;
  background: var(--white);
  border-top: 1px solid var(--stone);
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1.5rem;
}
.save-msg { font-size: .82rem; }
.save-msg.ok  { color: #1e8449; }
.save-msg.err { color: #c0392b; }
.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem;
}
@media(max-width:768px) {
  .edit-grid { grid-template-columns: 1fr; }
  .form-row-2 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="admin-main">

  <div class="admin-topbar">
    <div>
      <a href="products.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> All Products
      </a>
      <h1 class="page-title"><?= $pageTitle ?></h1>
    </div>
  </div>

  <form id="pform" enctype="multipart/form-data">
    <input type="hidden" name="product_id"
           value="<?= $productId ?>">
    <input type="hidden" name="existing_image"
           id="existing-image"
           value="<?= htmlspecialchars($product['image_path'] ?? '') ?>">

    <div class="edit-grid">

      <!-- ── LEFT: product details ── -->
      <div>
        <div class="admin-card">
          <div class="card-head">
            <h2 class="card-title">Design Details</h2>
          </div>
          <div style="padding:1.25rem 1.5rem">

            <div class="form-row-2">
              <div class="form-group">
                <label class="form-label">Design Name *</label>
                <input class="form-input" type="text" name="name"
                       required placeholder="e.g. In The Beginning"
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Scripture Verse *</label>
                <input class="form-input" type="text" name="verse"
                       required placeholder="e.g. Genesis 1:1"
                       value="<?= htmlspecialchars($product['verse'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label class="form-label">Price (KSh) *</label>
                <input class="form-input" type="number" name="price_ksh"
                       required min="1" placeholder="3800"
                       value="<?= htmlspecialchars($product['price_ksh'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Badge Label</label>
                <input class="form-input" type="text" name="tag"
                       placeholder="e.g. New Drop, Bestseller, Limited Edition"
                       value="<?= htmlspecialchars($product['tag'] ?? 'New Design') ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-input" name="description" rows="3"
                        placeholder="Short product description shown in the detail panel..."
                        ><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label class="form-label">Material</label>
                <input class="form-input" type="text" name="material"
                       placeholder="e.g. 240gsm ring-spun cotton"
                       value="<?= htmlspecialchars($product['material'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Fit</label>
                <input class="form-input" type="text" name="fit"
                       placeholder="e.g. Relaxed unisex fit. Runs true to size."
                       value="<?= htmlspecialchars($product['fit'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Visibility in Store</label>
              <select class="form-input" name="is_active">
                <option value="1"
                  <?= ($product['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>
                  Active — visible to customers
                </option>
                <option value="0"
                  <?= ($product['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>
                  Hidden — not visible in store
                </option>
              </select>
            </div>

          </div>
        </div>

        <!-- Colour variants -->
        <div class="admin-card">
          <div class="card-head">
            <h2 class="card-title">Colour Variants</h2>
            <span style="font-size:.72rem;color:var(--muted)">
              All sizes S / M / L / XL / XXL per colour
            </span>
          </div>
          <div style="padding:1.25rem 1.5rem">

            <!-- Column headers -->
            <div style="display:grid;grid-template-columns:1fr 52px 36px;
                        gap:.5rem;margin-bottom:.35rem">
              <span style="font-size:.62rem;letter-spacing:.12em;
                           text-transform:uppercase;color:var(--muted)">
                Colour Name
              </span>
              <span style="font-size:.62rem;letter-spacing:.12em;
                           text-transform:uppercase;color:var(--muted)">
                Hex
              </span>
              <span></span>
            </div>

            <div id="colors-wrap">
              <?php if (!empty($variants)): ?>
                <?php foreach ($variants as $i => $v): ?>
                <div class="color-row">
                  <input class="form-input" type="text"
                         data-cn="<?= $i ?>"
                         placeholder="e.g. Black"
                         value="<?= htmlspecialchars($v['color_name']) ?>">
                  <input class="swatch-input" type="color"
                         data-ch="<?= $i ?>"
                         value="<?= htmlspecialchars($v['color_hex']) ?>">
                  <button type="button" class="btn-rm"
                          onclick="rmColor(this)">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="color-row">
                  <input class="form-input" type="text"
                         data-cn="<?= $i ?>"
                         placeholder="e.g. Black, White, Olive">
                  <input class="swatch-input" type="color"
                         data-ch="<?= $i ?>"
                         value="#1C2329">
                  <button type="button" class="btn-rm"
                          onclick="rmColor(this)">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
                <?php endfor; ?>
              <?php endif; ?>
            </div>

            <button type="button" class="btn-add-color"
                    onclick="addColor()">
              <i class="fa-solid fa-plus"></i> Add Colour
            </button>

          </div>
        </div>
      </div><!-- end left -->

      <!-- ── RIGHT: image upload ── -->
      <div>
        <div class="admin-card">
          <div class="card-head">
            <h2 class="card-title">Product Photo</h2>
          </div>
          <div style="padding:1.25rem">

            <?php if (!empty($product['image_path'])): ?>
              <img src="<?= htmlspecialchars($product['image_path']) ?>"
                   alt="Current photo"
                   id="img-preview"
                   class="img-preview show">
              <p style="font-size:.72rem;color:var(--muted);
                        text-align:center;margin-bottom:.75rem">
                Current photo. Upload a new file to replace it.
              </p>
            <?php else: ?>
              <img src="" alt="" id="img-preview" class="img-preview">
            <?php endif; ?>

            <div class="image-upload-area">
              <input type="file" name="image"
                     accept="image/jpeg,image/png,image/webp"
                     onchange="previewImg(this)">
              <div id="upload-hint-block">
                <div class="upload-icon">
                  <i class="fa-regular fa-image"></i>
                </div>
                <div class="upload-hint">
                  <strong>Click to upload</strong> or drag and drop<br>
                  JPG · PNG · WebP &nbsp;|&nbsp; Max 5MB<br><br>
                  <span style="color:var(--gold)">
                    Best results: square T-shirt photo on a clean
                    background or on a model
                  </span>
                </div>
              </div>
            </div>

            <div style="margin-top:1rem;padding:.85rem;
                        background:var(--cream);border-radius:3px;
                        font-size:.75rem;color:var(--muted);line-height:1.6">
              <i class="fa-solid fa-circle-info"
                 style="color:var(--gold);margin-right:.3rem"></i>
              Without a photo the store shows a CSS design illustration
              until a real photo is uploaded.
            </div>

          </div>
        </div>
      </div><!-- end right -->

    </div><!-- end edit-grid -->

    <!-- Sticky save bar -->
    <div class="admin-card save-bar">
      <span class="save-msg" id="save-msg"></span>
      <div style="display:flex;gap:.75rem;align-items:center">
        <a href="products.php" class="btn-ghost-sm">Cancel</a>
        <button type="submit" class="btn-primary-sm" id="save-btn">
          <i class="fa-solid fa-floppy-disk"></i>
          <?= $isNew ? 'Add Design' : 'Save Changes' ?>
        </button>
      </div>
    </div>

  </form>
</main>

<script>
/* Image preview before upload */
function previewImg(input) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function (e) {
    var p = document.getElementById('img-preview');
    p.src = e.target.result;
    p.classList.add('show');
    document.getElementById('upload-hint-block').style.opacity = '.4';
  };
  reader.readAsDataURL(input.files[0]);
}

/* Add a new colour row */
function addColor() {
  var wrap = document.getElementById('colors-wrap');
  var idx  = wrap.querySelectorAll('.color-row').length;
  var row  = document.createElement('div');
  row.className = 'color-row';
  row.innerHTML =
    '<input class="form-input" type="text" data-cn="' + idx +
    '" placeholder="e.g. Black, White, Olive">' +
    '<input class="swatch-input" type="color" data-ch="' + idx +
    '" value="#1C2329">' +
    '<button type="button" class="btn-rm" onclick="rmColor(this)">' +
    '<i class="fa-solid fa-xmark"></i></button>';
  wrap.appendChild(row);
}

/* Remove a colour row — minimum 1 must remain */
function rmColor(btn) {
  if (document.querySelectorAll('.color-row').length <= 1) {
    alert('A product must have at least one colour variant.');
    return;
  }
  btn.closest('.color-row').remove();
}

/* Collect all colour rows into a JSON string for the API */
function collectColors() {
  var rows   = document.querySelectorAll('.color-row');
  var result = [];
  rows.forEach(function (row) {
    var name = row.querySelector('[data-cn]').value.trim();
    var hex  = row.querySelector('[data-ch]').value.trim();
    if (name) result.push({ name: name, hex: hex });
  });
  return JSON.stringify(result);
}

/* Form submit — sends multipart so the image file is included */
document.getElementById('pform').addEventListener('submit', async function (e) {
  e.preventDefault();

  var btn = document.getElementById('save-btn');
  var msg = document.getElementById('save-msg');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
  msg.textContent = '';
  msg.className = 'save-msg';

  var fd = new FormData(this);
  fd.set('colors', collectColors());

  try {
    var res  = await fetch('/injili/api/admin/save_product.php', {
      method: 'POST',
      body: fd
    });
    var data = await res.json();

    if (data.success) {
      msg.textContent = '✓ ' + data.message;
      msg.className   = 'save-msg ok';
      setTimeout(function () {
        window.location.href = 'products.php';
      }, 1000);
    } else {
      msg.textContent = '✗ ' + data.message;
      msg.className   = 'save-msg err';
      btn.disabled    = false;
      btn.innerHTML   =
        '<i class="fa-solid fa-floppy-disk"></i> <?= $isNew ? "Add Design" : "Save Changes" ?>';
    }
  } catch (err) {
    msg.textContent = '✗ Network error. Please try again.';
    msg.className   = 'save-msg err';
    btn.disabled    = false;
    btn.innerHTML   =
      '<i class="fa-solid fa-floppy-disk"></i> <?= $isNew ? "Add Design" : "Save Changes" ?>';
  }
});
</script>
</body>
</html>