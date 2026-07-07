<?php
/**
 * admin/products.php — Product catalogue management
 * Includes photo thumbnail, Edit button, and Add New Design button.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$db = getDB();

/* Handle show / hide toggle */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['product_id'])) {
    $pid    = (int)$_POST['product_id'];
    $toggle = $_POST['toggle'] ?? '';
    if ($toggle === 'activate') {
        $db->prepare('UPDATE products SET is_active = 1 WHERE id = :id')
           ->execute([':id' => $pid]);
    } elseif ($toggle === 'deactivate') {
        $db->prepare('UPDATE products SET is_active = 0 WHERE id = :id')
           ->execute([':id' => $pid]);
    }
    header('Location: products.php');
    exit;
}

$stmt = $db->query(
    'SELECT p.*, COUNT(pv.id) AS variant_count
     FROM products p
     LEFT JOIN product_variants pv ON pv.product_id = p.id
     GROUP BY p.id
     ORDER BY p.sort_order, p.id'
);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — Injili Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="admin-main">

  <div class="admin-topbar">
    <div>
      <h1 class="page-title">Products</h1>
      <span class="page-subtitle">
        <?= count($products) ?> design<?= count($products) !== 1 ? 's' : '' ?>
        in catalogue
      </span>
    </div>
    <a href="product_edit.php?id=0" class="btn-primary-sm">
      <i class="fa-solid fa-plus"></i> Add New Design
    </a>
  </div>

  <div class="admin-card">
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width:56px">Photo</th>
            <th>Design</th>
            <th>Verse</th>
            <th>Price</th>
            <th>Colours</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <?php if (!empty($p['image_path'])): ?>
                <img src="<?= htmlspecialchars($p['image_path']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     style="width:44px;height:44px;object-fit:cover;
                            border-radius:3px;border:1px solid var(--stone)">
              <?php else: ?>
                <div style="width:44px;height:44px;border-radius:3px;
                            background:var(--navy);display:flex;
                            align-items:center;justify-content:center;
                            font-size:.55rem;color:var(--gold);
                            letter-spacing:.05em;text-align:center;
                            line-height:1.3">
                  CSS<br>art
                </div>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= htmlspecialchars($p['name']) ?></strong>
              <div style="font-size:.72rem;color:var(--muted)">
                <?= htmlspecialchars($p['tag']) ?>
              </div>
            </td>
            <td style="font-size:.82rem;color:var(--muted)">
              <?= htmlspecialchars($p['verse']) ?>
            </td>
            <td>KSh <?= number_format($p['price_ksh']) ?></td>
            <td>
              <?= $p['variant_count'] ?>
              colour<?= $p['variant_count'] != 1 ? 's' : '' ?>
            </td>
            <td>
              <?php if ($p['is_active']): ?>
                <span class="status-pill status-received">Active</span>
              <?php else: ?>
                <span class="status-pill"
                      style="background:#f3f4f6;color:#888">Hidden</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <a href="product_edit.php?id=<?= $p['id'] ?>"
                   class="btn-ghost-sm">
                  <i class="fa-regular fa-pen-to-square"></i> Edit
                </a>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="product_id"
                         value="<?= $p['id'] ?>">
                  <?php if ($p['is_active']): ?>
                    <button class="btn-ghost-sm" name="toggle"
                            value="deactivate">
                      <i class="fa-regular fa-eye-slash"></i> Hide
                    </button>
                  <?php else: ?>
                    <button class="btn-primary-sm" name="toggle"
                            value="activate">
                      <i class="fa-regular fa-eye"></i> Show
                    </button>
                  <?php endif; ?>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
</body>
</html>