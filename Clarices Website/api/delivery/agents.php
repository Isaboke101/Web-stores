<?php
/**
 * api/delivery/agents.php — List Pickup Mtaani agents
 * GET /api/delivery/agents.php?city=Nairobi
 * Returns: { success, agents: [...] }
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/pickupmtaani.php';
header('Content-Type: application/json');
$city   = trim($_GET['city'] ?? '');
$agents = PickupMtaaniService::listAgents($city);
echo json_encode(['success' => true, 'agents' => $agents]);
