<?php
/**
 * api/products/list.php — Return all active products with their variants
 *
 * GET /api/products/list.php
 * Returns: { success, products: [...] }
 *
 * This endpoint replaces the hardcoded `products` array in the frontend JS.
 * The JS will call this on page load and use the response to render the
 * product grid and product detail panel dynamically.
 *
 * Each product includes its variant (colour) options so the frontend
 * has everything it needs to render colour swatches and size selections.
 */

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$db = getDB();

/* Fetch all active products ordered by sort_order */
$productStmt = $db->prepare(
    'SELECT id, name, verse, tag, description, material, fit,
            price_ksh, tee_class, bg_color, graphic_html, image_path
     FROM products
     WHERE is_active = 1
     ORDER BY sort_order ASC, id ASC'
);
$productStmt->execute();
$products = $productStmt->fetchAll();

/* For each product, fetch its colour variants */
foreach ($products as &$product) {
    $variantStmt = $db->prepare(
        'SELECT id, color_name, color_hex, sizes
         FROM product_variants
         WHERE product_id = :pid AND is_active = 1
         ORDER BY sort_order ASC'
    );
    $variantStmt->execute([':pid' => $product['id']]);
    $variants = $variantStmt->fetchAll();

    /* Decode the JSON sizes array stored in each variant */
    foreach ($variants as &$v) {
        $v['sizes'] = json_decode($v['sizes'], true) ?? [];
    }
    unset($v);

    $product['variants'] = $variants;

    /* Build flat arrays of colours and colour hex values to match the
       shape that the existing frontend JS expects (colors / colorVals) */
    $product['colors']    = array_column($variants, 'color_name');
    $product['colorVals'] = array_column($variants, 'color_hex');

    /* Cast ID to integer so JSON doesn't wrap it in quotes */
    $product['id']        = (int) $product['id'];
    $product['price_ksh'] = (int) $product['price_ksh'];
}
unset($product);

echo json_encode([
    'success'  => true,
    'products' => $products,
]);
