<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Manual Benefit Disbursement Entry Endpoint (Officer Only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST.', 405);
}

$input = getRequestData();
$approvalId = isset($input['approval_id']) ? (int)$input['approval_id'] : 0;
$amount     = isset($input['benefit_amount']) ? (float)$input['benefit_amount'] : 0.0;
$date       = !empty($input['benefit_date']) ? trim($input['benefit_date']) : date('Y-m-d');
$status     = in_array($input['payment_status'] ?? 'Pending', ['Pending', 'Processing', 'Paid', 'Failed']) ? $input['payment_status'] : 'Pending';
$txnRef     = !empty($input['transaction_reference']) ? trim($input['transaction_reference']) : null;

if ($approvalId <= 0 || $amount < 0) {
    jsonResponse(false, null, 'Valid Approval ID and non-negative Benefit Amount are required.', 400);
}

if (empty($txnRef) && in_array($status, ['Processing', 'Paid'])) {
    $txnRef = 'TXN-DBT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("
        INSERT INTO Benefits (
            Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference
        ) VALUES (
            :appr_id, :amount, :date, :status, :txn_ref
        )
    ");
    $stmt->execute([
        ':appr_id'  => $approvalId,
        ':amount'   => $amount,
        ':date'     => $date,
        ':status'   => $status,
        ':txn_ref'  => $txnRef
    ]);

    $benefitId = (int)$pdo->lastInsertId();

    jsonResponse(true, ['benefit_id' => $benefitId, 'transaction_reference' => $txnRef], 'Benefit disbursement record created successfully.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error creating benefit: ' . $e->getMessage(), 500);
}
