<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Applications Listing Endpoint
 * Serves Citizen dashboard tracking and Officer application queue
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
$deptFilter   = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : null;
$search       = trim($_GET['search'] ?? '');

try {
    $sql = "
        SELECT 
            a.Application_ID,
            a.Citizen_ID,
            c.Name AS Citizen_Name,
            c.Email AS Citizen_Email,
            c.Phone AS Citizen_Phone,
            c.Category AS Citizen_Category,
            c.Income AS Citizen_Income,
            c.Age AS Citizen_Age,
            c.Gender AS Citizen_Gender,
            c.Occupation AS Citizen_Occupation,
            c.Education AS Citizen_Education,
            c.District AS Citizen_District,
            c.State AS Citizen_State,
            a.Scheme_ID,
            s.Scheme_Name,
            s.Benefit_Type,
            s.Benefit_Description,
            d.Department_ID,
            d.Department_Name,
            a.Application_Date,
            a.Status,
            a.Remarks AS Application_Remarks,
            ap.Approval_ID,
            ap.Approval_Date,
            ap.Decision,
            ap.Remarks AS Officer_Remarks,
            o.Name AS Officer_Name,
            o.Designation AS Officer_Designation,
            b.Benefit_ID,
            b.Benefit_Amount,
            b.Benefit_Date,
            b.Payment_Status,
            b.Transaction_Reference
        FROM Application a
        INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
        INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
        LEFT JOIN Officer o ON ap.Officer_ID = o.Officer_ID
        LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
        WHERE 1=1
    ";

    $params = [];

    // If logged in as Citizen, enforce citizen-only boundary
    if (isCitizen()) {
        $sql .= " AND a.Citizen_ID = :cit_id";
        $params[':cit_id'] = $currentUser['id'];
    }

    if (!empty($statusFilter) && $statusFilter !== 'all') {
        $sql .= " AND a.Status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($schemeFilter !== null) {
        $sql .= " AND a.Scheme_ID = :scheme_id";
        $params[':scheme_id'] = $schemeFilter;
    }

    if ($deptFilter !== null) {
        $sql .= " AND d.Department_ID = :dept_id";
        $params[':dept_id'] = $deptFilter;
    }

    if (!empty($search)) {
        $sql .= " AND (c.Name LIKE :search OR c.Email LIKE :search2 OR s.Scheme_Name LIKE :search3 OR a.Application_ID = :search4)";
        $searchParam = '%' . $search . '%';
        $params[':search'] = $searchParam;
        $params[':search2'] = $searchParam;
        $params[':search3'] = $searchParam;
        $params[':search4'] = is_numeric($search) ? (int)$search : 0;
    }

    $sql .= " ORDER BY a.Application_ID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    jsonResponse(true, $applications, 'Applications retrieved successfully.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error while listing applications: ' . $e->getMessage(), 500);
}
