<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Application Dossier Detail Endpoint
 * Shows Application + Citizen + Scheme + Criteria + Approval + Benefit
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

if (!isAuthenticated()) {
    jsonResponse(false, null, 'Authentication required.', 401);
}

$appId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($appId <= 0) {
    jsonResponse(false, null, 'A valid Application ID is required.', 400);
}

$pdo = getDB();
$currentUser = getCurrentUser();

try {
    $sql = "
        SELECT 
            a.Application_ID,
            a.Citizen_ID,
            c.Name AS Citizen_Name,
            c.Email AS Citizen_Email,
            c.Phone AS Citizen_Phone,
            c.Age AS Citizen_Age,
            c.Gender AS Citizen_Gender,
            c.Category AS Citizen_Category,
            c.Income AS Citizen_Income,
            c.Occupation AS Citizen_Occupation,
            c.Education AS Citizen_Education,
            c.Address AS Citizen_Address,
            c.District AS Citizen_District,
            c.State AS Citizen_State,
            c.Created_At AS Citizen_Registration_Date,
            a.Scheme_ID,
            s.Scheme_Name,
            s.Description AS Scheme_Description,
            s.Benefit_Type,
            s.Benefit_Description,
            s.Last_Date AS Scheme_Deadline,
            s.Status AS Scheme_Status,
            d.Department_ID,
            d.Department_Name,
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
            a.Application_Date,
            a.Status,
            a.Remarks AS Citizen_Remarks,
            ap.Approval_ID,
            ap.Officer_ID,
            ap.Approval_Date,
            ap.Decision,
            ap.Remarks AS Officer_Remarks,
            o.Name AS Officer_Name,
            o.Designation AS Officer_Designation,
            o.Email AS Officer_Email,
            b.Benefit_ID,
            b.Benefit_Amount,
            b.Benefit_Date,
            b.Payment_Status,
            b.Transaction_Reference
        FROM Application a
        INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
        INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
        LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
        LEFT JOIN Officer o ON ap.Officer_ID = o.Officer_ID
        LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
        WHERE a.Application_ID = :app_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':app_id' => $appId]);
    $dossier = $stmt->fetch();

    if (!$dossier) {
        jsonResponse(false, null, 'Application dossier not found.', 404);
    }

    // Role-based boundary: Citizen cannot view applications belonging to other citizens
    if (isCitizen() && (int)$dossier['Citizen_ID'] !== (int)$currentUser['id']) {
        jsonResponse(false, null, 'Access denied: You are not authorized to view this application dossier.', 403);
    }

    jsonResponse(true, $dossier, 'Application dossier retrieved successfully.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
