<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Benefits Disbursement Ledger Endpoint
 * Provides citizen benefit records and officer financial management view
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

if (!isAuthenticated()) {
    jsonResponse(false, null, 'Authentication required.', 401);
}

$pdo = getDB();
$currentUser = getCurrentUser();

$statusFilter = trim($_GET['status'] ?? '');
$schemeFilter = isset($_GET['scheme_id']) && $_GET['scheme_id'] !== '' ? (int)$_GET['scheme_id'] : null;
$search       = trim($_GET['search'] ?? '');

try {
    $sql = "
        SELECT 
            b.Benefit_ID,
            b.Approval_ID,
            b.Benefit_Amount,
            b.Benefit_Date,
            b.Payment_Status,
            b.Transaction_Reference,
            ap.Approval_Date,
            ap.Decision,
            ap.Remarks AS Officer_Remarks,
            a.Application_ID,
            a.Citizen_ID,
            c.Name AS Citizen_Name,
            c.Email AS Citizen_Email,
            c.Phone AS Citizen_Phone,
            c.District AS Citizen_District,
            c.State AS Citizen_State,
            s.Scheme_ID,
            s.Scheme_Name,
            s.Benefit_Type,
            d.Department_Name,
            o.Name AS Approving_Officer
        FROM Benefits b
        INNER JOIN Approval ap ON b.Approval_ID = ap.Approval_ID
        INNER JOIN Application a ON ap.Application_ID = a.Application_ID
        INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
        INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        INNER JOIN Officer o ON ap.Officer_ID = o.Officer_ID
        WHERE 1=1
    ";

    $params = [];

    // Role restriction
    if (isCitizen()) {
        $sql .= " AND a.Citizen_ID = :citizen_id";
        $params[':citizen_id'] = $currentUser['id'];
    }

    if (!empty($statusFilter) && $statusFilter !== 'all') {
        $sql .= " AND b.Payment_Status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($schemeFilter !== null) {
        $sql .= " AND s.Scheme_ID = :scheme_id";
        $params[':scheme_id'] = $schemeFilter;
    }

    if (!empty($search)) {
        $sql .= " AND (c.Name LIKE :search OR b.Transaction_Reference LIKE :search2 OR s.Scheme_Name LIKE :search3)";
        $searchParam = '%' . $search . '%';
        $params[':search'] = $searchParam;
        $params[':search2'] = $searchParam;
        $params[':search3'] = $searchParam;
    }

    $sql .= " ORDER BY b.Benefit_Date DESC, b.Benefit_ID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $benefits = $stmt->fetchAll();

    // Summary calculations
    $totalAmount = 0.0;
    $paidAmount = 0.0;
    $pendingAmount = 0.0;
    $processingAmount = 0.0;

    foreach ($benefits as $b) {
        $amt = (float)$b['Benefit_Amount'];
        $totalAmount += $amt;
        if ($b['Payment_Status'] === 'Paid') {
            $paidAmount += $amt;
        } elseif ($b['Payment_Status'] === 'Processing') {
            $processingAmount += $amt;
        } else {
            $pendingAmount += $amt;
        }
    }

    jsonResponse(true, [
        'benefits' => $benefits,
        'summary'  => [
            'total_transactions' => count($benefits),
            'total_amount'       => $totalAmount,
            'paid_amount'        => $paidAmount,
            'processing_amount'  => $processingAmount,
            'pending_amount'     => $pendingAmount
        ]
    ], 'Benefits list retrieved successfully.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error while retrieving benefits: ' . $e->getMessage(), 500);
}
