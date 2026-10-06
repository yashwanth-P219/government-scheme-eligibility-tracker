<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Scheme Status Toggle Endpoint (Active / Inactive)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST to toggle scheme status.', 405);
}

$input = getRequestData();
$schemeId = isset($input['scheme_id']) ? (int)$input['scheme_id'] : 0;
$newStatus = in_array($input['status'] ?? '', ['Active', 'Inactive']) ? $input['status'] : null;

if ($schemeId <= 0) {
    jsonResponse(false, null, 'Valid Scheme ID is required.', 400);
}

$pdo = getDB();

try {
    if (!$newStatus) {
        // Toggle current status
        $stmtCheck = $pdo->prepare("SELECT Status FROM Scheme WHERE Scheme_ID = :id LIMIT 1");
        $stmtCheck->execute([':id' => $schemeId]);
        $row = $stmtCheck->fetch();
        if (!$row) {
            jsonResponse(false, null, 'Scheme not found.', 404);
        }
        $newStatus = ($row['Status'] === 'Active') ? 'Inactive' : 'Active';
    }

    $stmt = $pdo->prepare("UPDATE Scheme SET Status = :status WHERE Scheme_ID = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $schemeId]);

    jsonResponse(true, ['new_status' => $newStatus], "Scheme status updated to {$newStatus}.");

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
