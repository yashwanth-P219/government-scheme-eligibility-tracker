<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Single Scheme Details Endpoint
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

$schemeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($schemeId <= 0) {
    jsonResponse(false, null, 'A valid Scheme ID is required.', 400);
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("
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
            e.District AS Req_District
        FROM Scheme s
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
        WHERE s.Scheme_ID = :scheme_id
        LIMIT 1
    ");
    $stmt->execute([':scheme_id' => $schemeId]);
    $scheme = $stmt->fetch();

    if (!$scheme) {
        jsonResponse(false, null, 'Scheme not found in database.', 404);
    }

    jsonResponse(true, $scheme, 'Scheme details retrieved.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
