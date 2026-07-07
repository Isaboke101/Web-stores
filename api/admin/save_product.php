<?php
/**
 * api/admin/save_product.php — Create or update a product
 *
 * POST multipart/form-data
 * Fields: product_id (0 = new), name, verse, tag, description,
 *         material, fit, price_ksh, is_active, colors (JSON string),
 *         image (optional file upload)
 *
 * Returns: { success, message, product_id }
 *
 * Admin session required. All DB queries use PDO prepared statements.
 */

session_name('injili_admin');
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

$db        = getDB();
$productId = (int)($_POST['product_id'] ?? 0);
$isNew     = $productId === 0;

/* ── Validate required fields ─────────────────────────────────── */
$name     = trim($_POST['name']        ?? '');
$verse    = trim($_POST['verse']       ?? '');
$tag      = trim($_POST['tag']         ?? 'New Design');
$desc     = trim($_POST['description'] ?? '');
$material = trim($_POST['material']    ?? '');
$fit      = trim($_POST['fit']         ?? '');
$price    = (int)($_POST['price_ksh']  ?? 0);
$isActive = (int)($_POST['is_active']  ?? 1);

if (!$name || !$verse || $price <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Design name, verse and price are all required'
    ]);
    exit;
}

/* ── Handle image upload ───────────────────────────────────────── */
/* Start with the existing image path so edits without a new
   photo upload do not wipe out the current image */
$imagePath = $_POST['existing_image'] ?? null;

if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file    = $_FILES['image'];
    $maxSize = 5 * 1024 * 1024; /* 5 MB */
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    /* Validate mime type from the file itself, not the filename extension */
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowed)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Please upload JPG, PNG or WebP only.'
        ]);
        exit;
    }

    if ($file['size'] > $maxSize) {
        echo json_encode([
            'success' => false,
            'message' => 'Image is too large. Maximum size is 5MB.'
        ]);
        exit;
    }

    /* Build a unique filename so new uploads never overwrite old ones */
    $ext = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg'
    };

    $filename  = 'product_' . ($productId ?: 'new') . '_' . time() . '.' . $ext;
    $uploadDir = dirname(__DIR__, 2) . '/uploads/products/';
    $destPath  = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Upload failed. Please check that the uploads/products/ folder exists.'
        ]);
        exit;
    }

    /* Store the web-accessible relative path */
    $imagePath = '/injili/uploads/products/' . $filename;
}

/* ── Insert or update the product row ─────────────────────────── */
if ($isNew) {
    /* Place the new product at the end of the sort order */
    $maxOrder = (int)$db->query(
        'SELECT COALESCE(MAX(sort_order), 0) FROM products'
    )->fetchColumn();

    $stmt = $db->prepare(
        'INSERT INTO products
           (name, verse, tag, description, material, fit,
            price_ksh, graphic_html, image_path, tee_class,
            bg_color, is_active, sort_order)
         VALUES
           (:name, :verse, :tag, :desc, :mat, :fit,
            :price, "", :img, "tee-black",
            "#1C2329", :active, :sort)'
    );
    $stmt->execute([
        ':name'   => $name,
        ':verse'  => $verse,
        ':tag'    => $tag,
        ':desc'   => $desc,
        ':mat'    => $material,
        ':fit'    => $fit,
        ':price'  => $price,
        ':img'    => $imagePath,
        ':active' => $isActive,
        ':sort'   => $maxOrder + 1,
    ]);
    $productId = (int)$db->lastInsertId();

} else {
    /* Update existing product. Only include image_path in the
       SET clause when a new image was actually uploaded. */
    $imgClause = $imagePath ? ', image_path = :img' : '';

    $stmt = $db->prepare(
        "UPDATE products
         SET name      = :name,
             verse     = :verse,
             tag       = :tag,
             description = :desc,
             material  = :mat,
             fit       = :fit,
             price_ksh = :price,
             is_active = :active
             {$imgClause}
         WHERE id = :id"
    );

    $params = [
        ':name'   => $name,
        ':verse'  => $verse,
        ':tag'    => $tag,
        ':desc'   => $desc,
        ':mat'    => $material,
        ':fit'    => $fit,
        ':price'  => $price,
        ':active' => $isActive,
        ':id'     => $productId,
    ];
    if ($imagePath) $params[':img'] = $imagePath;

    $stmt->execute($params);
}

/* ── Save colour variants ──────────────────────────────────────────
   We never DELETE variants because they are referenced by order_items
   via a foreign key. Instead we:
     1. Deactivate all existing variants for this product
     2. For each submitted colour, update an existing matching variant
        (matched by color_name) or insert a brand new one
     3. Anything not resubmitted stays deactivated — order history intact
─────────────────────────────────────────────────────────────────── */
$colors = json_decode($_POST['colors'] ?? '[]', true) ?? [];

if (!empty($colors)) {

    /* Step 1 — deactivate all current variants for this product.
       We will reactivate the ones that come back in the submitted list. */
    $db->prepare('UPDATE product_variants SET is_active = 0 WHERE product_id = :pid')
       ->execute([':pid' => $productId]);

    /* Step 2 — fetch existing variants so we can match by name */
    $existStmt = $db->prepare(
        'SELECT id, color_name FROM product_variants WHERE product_id = :pid'
    );
    $existStmt->execute([':pid' => $productId]);
    $existing = $existStmt->fetchAll();

    /* Build a lookup: lowercase color_name => variant id */
    $existingMap = [];
    foreach ($existing as $ex) {
        $existingMap[strtolower(trim($ex['color_name']))] = (int)$ex['id'];
    }

    /* Prepared statements for update and insert paths */
    $updateStmt = $db->prepare(
        'UPDATE product_variants
         SET color_name = :cname,
             color_hex  = :chex,
             sizes      = :sizes,
             is_active  = 1,
             sort_order = :sort
         WHERE id = :id'
    );

    $insertStmt = $db->prepare(
        'INSERT INTO product_variants
           (product_id, color_name, color_hex, sizes, is_active, sort_order)
         VALUES
           (:pid, :cname, :chex, :sizes, 1, :sort)'
    );

    foreach ($colors as $i => $color) {
        $cname = trim($color['name'] ?? '');
        $chex  = trim($color['hex']  ?? '#000000');
        if (!$cname) continue;

        $key = strtolower($cname);

        if (isset($existingMap[$key])) {
            /* Colour already exists — update and reactivate it */
            $updateStmt->execute([
                ':cname' => $cname,
                ':chex'  => $chex,
                ':sizes' => '["S","M","L","XL","XXL"]',
                ':sort'  => $i + 1,
                ':id'    => $existingMap[$key],
            ]);
        } else {
            /* Brand new colour — insert a fresh row */
            $insertStmt->execute([
                ':pid'   => $productId,
                ':cname' => $cname,
                ':chex'  => $chex,
                ':sizes' => '["S","M","L","XL","XXL"]',
                ':sort'  => $i + 1,
            ]);
        }
    }
}

echo json_encode([
    'success'    => true,
    'message'    => $isNew ? 'Product added successfully' : 'Product updated successfully',
    'product_id' => $productId,
]);