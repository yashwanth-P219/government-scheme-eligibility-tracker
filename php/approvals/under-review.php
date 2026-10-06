<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Transition Application to Under Review Status
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST.', 405);
}

$input = getRequestData();
$appId = isset($input['application_id']) ? (int)$input['application_id'] : 0;
$remarks = trim($input['remarks'] ?? 'Document verification and field inquiry initiated.');

if ($appId <= 0) {
    jsonResponse(false, null, 'Valid Application ID is required.', 400);
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("
        UPDATE Application 
        SET Status = 'Under Review', Remarks = :remarks 
        WHERE Application_ID = :app_id
    ");
    $stmt->execute([':remarks' => $remarks, ':app_id' => $appId]);

    jsonResponse(true, ['application_id' => $appId, 'status' => 'Under Review'], "Application #{$appId} marked Under Review.");

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
