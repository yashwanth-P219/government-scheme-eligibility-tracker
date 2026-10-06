<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * ACID Transaction Endpoint: Application Approval and Benefit Sanction
 * Implements BEGIN TRANSACTION -> [Update Application -> Insert Approval -> Insert Benefits] -> COMMIT / ROLLBACK
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST to approve application.', 405);
}

$input = getRequestData();
$appId = isset($input['application_id']) ? (int)$input['application_id'] : 0;
$officerId = (int)$_SESSION['user_id'];
$remarks = trim($input['remarks'] ?? 'Application verified and sanctioned by competent departmental authority.');
$benefitAmount = isset($input['benefit_amount']) ? (float)$input['benefit_amount'] : 0.0;
$paymentStatus = in_array($input['payment_status'] ?? 'Processing', ['Pending', 'Processing', 'Paid', 'Failed']) 
    ? $input['payment_status'] 
    : 'Processing';
$txnRef = !empty($input['transaction_reference']) ? trim($input['transaction_reference']) : null;

if ($appId <= 0) {
    jsonResponse(false, null, 'A valid Application ID is required for adjudication.', 400);
}

// Generate unique transaction reference if payment is marked Processing or Paid and none provided
if (empty($txnRef) && in_array($paymentStatus, ['Processing', 'Paid'])) {
    $txnRef = 'TXN-DBT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

$pdo = getDB();

try {
    // 1. Pre-check: Ensure Application exists and is not already decided
    $stmtCheck = $pdo->prepare("SELECT Application_ID, Status, Citizen_ID, Scheme_ID FROM Application WHERE Application_ID = :app_id LIMIT 1");
    $stmtCheck->execute([':app_id' => $appId]);
    $app = $stmtCheck->fetch();

    if (!$app) {
        jsonResponse(false, null, 'Application record not found.', 404);
    }

    if ($app['Status'] === 'Approved') {
        jsonResponse(false, null, 'This application is already Approved.', 409);
    }

    // =========================================================================
    // START ATOMIC SQL TRANSACTION
    // =========================================================================
    $pdo->beginTransaction();

    // STEP 1: Update Application record status and remarks
    $stmtApp = $pdo->prepare("
        UPDATE Application 
        SET Status = 'Approved', Remarks = :remarks 
        WHERE Application_ID = :app_id
    ");
    $stmtApp->execute([
        ':remarks' => $remarks,
        ':app_id'  => $appId
    ]);

    // STEP 2: Insert formal Approval adjudication record
    // Handle existing approval if application was previously marked Rejected or re-evaluated
    $stmtApprovalCheck = $pdo->prepare("SELECT Approval_ID FROM Approval WHERE Application_ID = :app_id LIMIT 1");
    $stmtApprovalCheck->execute([':app_id' => $appId]);
    $existingApproval = $stmtApprovalCheck->fetch();

    if ($existingApproval) {
        $approvalId = (int)$existingApproval['Approval_ID'];
        $stmtAppr = $pdo->prepare("
            UPDATE Approval SET
                Officer_ID = :officer_id,
                Approval_Date = NOW(),
                Decision = 'Approved',
                Remarks = :remarks
            WHERE Approval_ID = :approval_id
        ");
        $stmtAppr->execute([
            ':officer_id'   => $officerId,
            ':remarks'      => $remarks,
            ':approval_id'  => $approvalId
        ]);
    } else {
        $stmtAppr = $pdo->prepare("
            INSERT INTO Approval (
                Application_ID, Officer_ID, Approval_Date, Decision, Remarks
            ) VALUES (
                :app_id, :officer_id, NOW(), 'Approved', :remarks
            )
        ");
        $stmtAppr->execute([
            ':app_id'     => $appId,
            ':officer_id' => $officerId,
            ':remarks'    => $remarks
        ]);
        $approvalId = (int)$pdo->lastInsertId();
    }

    // STEP 3: Create or update Benefit record if amount is sanctioned
    $benefitId = null;
    if ($benefitAmount >= 0) {
        $stmtBenCheck = $pdo->prepare("SELECT Benefit_ID FROM Benefits WHERE Approval_ID = :approval_id LIMIT 1");
        $stmtBenCheck->execute([':approval_id' => $approvalId]);
        $existingBen = $stmtBenCheck->fetch();

        if ($existingBen) {
            $stmtBen = $pdo->prepare("
                UPDATE Benefits SET
                    Benefit_Amount = :amount,
                    Benefit_Date = CURDATE(),
                    Payment_Status = :status,
                    Transaction_Reference = :txn_ref
                WHERE Benefit_ID = :benefit_id
            ");
            $stmtBen->execute([
                ':amount'     => $benefitAmount,
                ':status'     => $paymentStatus,
                ':txn_ref'    => $txnRef,
                ':benefit_id' => $existingBen['Benefit_ID']
            ]);
            $benefitId = (int)$existingBen['Benefit_ID'];
        } else {
            $stmtBen = $pdo->prepare("
                INSERT INTO Benefits (
                    Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference
                ) VALUES (
                    :approval_id, :amount, CURDATE(), :status, :txn_ref
                )
            ");
            $stmtBen->execute([
                ':approval_id' => $approvalId,
                ':amount'      => $benefitAmount,
                ':status'      => $paymentStatus,
                ':txn_ref'     => $txnRef
            ]);
            $benefitId = (int)$pdo->lastInsertId();
        }
    }

    // =========================================================================
    // COMMIT TRANSACTION - All operations succeeded
    // =========================================================================
    $pdo->commit();

    jsonResponse(true, [
        'application_id'        => $appId,
        'approval_id'           => $approvalId,
        'benefit_id'            => $benefitId,
        'decision'              => 'Approved',
        'benefit_amount'        => $benefitAmount,
        'payment_status'        => $paymentStatus,
        'transaction_reference' => $txnRef
    ], "Application #{$appId} successfully Approved and benefit recorded via ACID Transaction.");

} catch (Exception $e) {
    // =========================================================================
    // ROLLBACK TRANSACTION - Undo any partial updates
    // =========================================================================
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, null, 'Transaction aborted and rolled back due to error: ' . $e->getMessage(), 500);
}
