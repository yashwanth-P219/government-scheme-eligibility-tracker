<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Application Rejection Endpoint (Officer Only)
 * Executes atomic transaction updating Application status and logging formal Approval decision
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST to reject an application.', 405);
}

$input = getRequestData();
$appId = isset($input['application_id']) ? (int)$input['application_id'] : 0;
$officerId = (int)$_SESSION['user_id'];
$remarks = trim($input['remarks'] ?? '');

if ($appId <= 0) {
    jsonResponse(false, null, 'A valid Application ID is required.', 400);
}

if (empty($remarks)) {
    jsonResponse(false, null, 'Formal rejection remarks explaining the grounds for rejection are mandatory.', 400);
}

$pdo = getDB();

try {
    $stmtCheck = $pdo->prepare("SELECT Application_ID, Status FROM Application WHERE Application_ID = :app_id LIMIT 1");
    $stmtCheck->execute([':app_id' => $appId]);
    $app = $stmtCheck->fetch();

    if (!$app) {
        jsonResponse(false, null, 'Application record not found.', 404);
    }

    $pdo->beginTransaction();

    // 1. Update Application status
    $stmtApp = $pdo->prepare("UPDATE Application SET Status = 'Rejected', Remarks = :remarks WHERE Application_ID = :app_id");
    $stmtApp->execute([
        ':remarks' => $remarks,
        ':app_id'  => $appId
    ]);

    // 2. Insert or update Approval record with 'Rejected' decision
    $stmtApprCheck = $pdo->prepare("SELECT Approval_ID FROM Approval WHERE Application_ID = :app_id LIMIT 1");
    $stmtApprCheck->execute([':app_id' => $appId]);
    $existing = $stmtApprCheck->fetch();

    if ($existing) {
        $stmtAppr = $pdo->prepare("
            UPDATE Approval SET 
                Officer_ID = :officer_id,
                Approval_Date = NOW(),
                Decision = 'Rejected',
                Remarks = :remarks
            WHERE Approval_ID = :approval_id
        ");
        $stmtAppr->execute([
            ':officer_id'   => $officerId,
            ':remarks'      => $remarks,
            ':approval_id'  => $existing['Approval_ID']
        ]);
        // Remove associated benefit if any existed
        $stmtDelBen = $pdo->prepare("DELETE FROM Benefits WHERE Approval_ID = :approval_id");
        $stmtDelBen->execute([':approval_id' => $existing['Approval_ID']]);
    } else {
        $stmtAppr = $pdo->prepare("
            INSERT INTO Approval (
                Application_ID, Officer_ID, Approval_Date, Decision, Remarks
            ) VALUES (
                :app_id, :officer_id, NOW(), 'Rejected', :remarks
            )
        ");
        $stmtAppr->execute([
            ':app_id'     => $appId,
            ':officer_id' => $officerId,
            ':remarks'    => $remarks
        ]);
    }

    $pdo->commit();

    jsonResponse(true, ['application_id' => $appId, 'status' => 'Rejected'], "Application #{$appId} has been formally Rejected.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, null, 'Error rejecting application: ' . $e->getMessage(), 500);
}
