<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Benefit Payment Status Updater (Officer Only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST.', 405);
}

$input = getRequestData();
$benefitId = isset($input['benefit_id']) ? (int)$input['benefit_id'] : 0;
$status    = in_array($input['payment_status'] ?? '', ['Pending', 'Processing', 'Paid', 'Failed']) ? $input['payment_status'] : null;
$txnRef    = !empty($input['transaction_reference']) ? trim($input['transaction_reference']) : null;

if ($benefitId <= 0 || !$status) {
    jsonResponse(false, null, 'Valid Benefit ID and Payment Status are required.', 400);
}

// Generate transaction ID if status changed to Paid and none set
if (empty($txnRef) && in_array($status, ['Processing', 'Paid'])) {
    $txnRef = 'TXN-DBT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

$pdo = getDB();

try {
    $sql = "UPDATE Benefits SET Payment_Status = :status";
    $params = [':status' => $status, ':id' => $benefitId];

    if (!empty($txnRef)) {
        $sql .= ", Transaction_Reference = :txn_ref";
        $params[':txn_ref'] = $txnRef;
    }

    $sql .= " WHERE Benefit_ID = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(true, [
        'benefit_id'            => $benefitId,
        'payment_status'        => $status,
        'transaction_reference' => $txnRef
    ], "Benefit disbursement status updated to '{$status}'.");

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error updating benefit status: ' . $e->getMessage(), 500);
}
