<?php
/**
 * api/delivery/track.php — Get Pickup Mtaani tracking status
 * GET /api/delivery/track.php?ref=PM-2025-000001
 * Returns: { success, tracking }
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/pickupmtaani.php';
header('Content-Type: application/json');
$ref = trim($_GET['ref'] ?? '');
if (!$ref) {
    echo json_encode(['success' => false, 'message' => 'ref is required']);
    exit;
}
$tracking = PickupMtaaniService::getTrackingStatus($ref);
echo json_encode(['success' => true, 'tracking' => $tracking]);
