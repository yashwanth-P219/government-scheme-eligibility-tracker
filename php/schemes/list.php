<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Schemes Listing Endpoint
 * Supports search, department filtering, benefit type filtering, and status filtering
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

$pdo = getDB();

$search       = trim($_GET['search'] ?? '');
$deptFilter   = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : null;
$typeFilter   = trim($_GET['benefit_type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

// Non-officers can only see Active schemes
if (!isOfficer()) {
    $statusFilter = 'Active';
}

$citizenId = isCitizen() ? (int)$_SESSION['user_id'] : null;

try {
    $sql = "
        SELECT 
            s.Scheme_ID,
            s.Department_ID,
            d.Department_Name,
            s.Scheme_Name,
            s.Description,
            s.Benefit_Type,
            s.Benefit_Description,
            s.Last_Date,
            s.Status,
            s.Created_At,
            e.Criteria_ID,
            e.Min_Age,
            e.Max_Age,
            e.Income_Limit,
            e.Category AS Req_Category,
            e.Gender AS Req_Gender,
            e.Occupation AS Req_Occupation,
            e.Education AS Req_Education,
            e.State AS Req_State,
            e.District AS Req_District,
            " . ($citizenId ? "
                (SELECT a.Status FROM Application a WHERE a.Citizen_ID = :citizen_id AND a.Scheme_ID = s.Scheme_ID LIMIT 1) AS Citizen_Application_Status,
                (SELECT a.Application_ID FROM Application a WHERE a.Citizen_ID = :citizen_id2 AND a.Scheme_ID = s.Scheme_ID LIMIT 1) AS Citizen_Application_ID
            " : "NULL AS Citizen_Application_Status, NULL AS Citizen_Application_ID") . ",
            (SELECT COUNT(*) FROM Application ap WHERE ap.Scheme_ID = s.Scheme_ID) AS Total_Applicants
        FROM Scheme s
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
        WHERE 1=1
    ";

    $params = [];
    if ($citizenId) {
        $params[':citizen_id'] = $citizenId;
        $params[':citizen_id2'] = $citizenId;
    }

    if (!empty($statusFilter) && $statusFilter !== 'all') {
        $sql .= " AND s.Status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($deptFilter !== null) {
        $sql .= " AND s.Department_ID = :dept_id";
        $params[':dept_id'] = $deptFilter;
    }

    if (!empty($typeFilter)) {
        $sql .= " AND s.Benefit_Type = :benefit_type";
        $params[':benefit_type'] = $typeFilter;
    }

    if (!empty($search)) {
        $sql .= " AND (s.Scheme_Name LIKE :search OR s.Description LIKE :search2 OR d.Department_Name LIKE :search3)";
        $searchParam = '%' . $search . '%';
        $params[':search'] = $searchParam;
        $params[':search2'] = $searchParam;
        $params[':search3'] = $searchParam;
    }

    $sql .= " ORDER BY s.Status ASC, s.Scheme_ID DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schemes = $stmt->fetchAll();

    jsonResponse(true, $schemes, 'Schemes retrieved successfully.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error while listing schemes: ' . $e->getMessage(), 500);
}
