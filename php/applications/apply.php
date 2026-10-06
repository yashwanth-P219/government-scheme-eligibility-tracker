<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Application Submission Endpoint (Citizen Only)
 * Enforces pre-application rules: Active Scheme, Deadline Validity,
 * Criteria Eligibility Check, and Duplicate Application Prevention.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireCitizen(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST to apply for a scheme.', 405);
}

$input = getRequestData();
$schemeId = isset($input['scheme_id']) ? (int)$input['scheme_id'] : 0;
$citizenId = (int)$_SESSION['user_id'];
$remarks = trim($input['remarks'] ?? 'Citizen online self-application.');

if ($schemeId <= 0) {
    jsonResponse(false, null, 'A valid Scheme ID is required to submit an application.', 400);
}

$pdo = getDB();

try {
    // 1. Fetch Scheme details and ensure Active & Deadline not passed
    $stmtScheme = $pdo->prepare("
        SELECT Scheme_ID, Scheme_Name, Status, Last_Date 
        FROM Scheme 
        WHERE Scheme_ID = :scheme_id
        LIMIT 1
    ");
    $stmtScheme->execute([':scheme_id' => $schemeId]);
    $scheme = $stmtScheme->fetch();

    if (!$scheme) {
        jsonResponse(false, null, 'The specified scheme does not exist.', 404);
    }

    if ($scheme['Status'] !== 'Active') {
        jsonResponse(false, null, 'This scheme is currently Inactive and not accepting applications.', 400);
    }

    if (!empty($scheme['Last_Date']) && strtotime($scheme['Last_Date']) < strtotime(date('Y-m-d'))) {
        jsonResponse(false, null, 'The application deadline (' . htmlspecialchars($scheme['Last_Date']) . ') for this scheme has expired.', 400);
    }

    // 2. Check for Duplicate Application
    $stmtDup = $pdo->prepare("
        SELECT Application_ID, Status, Application_Date 
        FROM Application 
        WHERE Citizen_ID = :citizen_id AND Scheme_ID = :scheme_id 
        LIMIT 1
    ");
    $stmtDup->execute([
        ':citizen_id' => $citizenId,
        ':scheme_id'  => $schemeId
    ]);
    $existingApp = $stmtDup->fetch();

    if ($existingApp) {
        jsonResponse(false, [
            'application_id'   => $existingApp['Application_ID'],
            'status'           => $existingApp['Status'],
            'application_date' => $existingApp['Application_Date']
        ], "You have already submitted an application for '{$scheme['Scheme_Name']}' (Current Status: {$existingApp['Status']}). Duplicate submissions are prevented.", 409);
    }

    // 3. Fetch Citizen Profile & Eligibility Criteria to verify eligibility
    $stmtCit = $pdo->prepare("SELECT * FROM Citizen WHERE Citizen_ID = :id LIMIT 1");
    $stmtCit->execute([':id' => $citizenId]);
    $citizen = $stmtCit->fetch();

    $stmtCrit = $pdo->prepare("SELECT * FROM Eligibility_Criteria WHERE Scheme_ID = :scheme_id LIMIT 1");
    $stmtCrit->execute([':scheme_id' => $schemeId]);
    $crit = $stmtCrit->fetch();

    if ($crit) {
        // Enforce Eligibility Checks
        if ($crit['Min_Age'] !== null && ($citizen['Age'] === null || $citizen['Age'] < $crit['Min_Age'])) {
            jsonResponse(false, null, "Ineligible: Applicant age is below the minimum requirement of {$crit['Min_Age']} years.", 422);
        }
        if ($crit['Max_Age'] !== null && ($citizen['Age'] === null || $citizen['Age'] > $crit['Max_Age'])) {
            jsonResponse(false, null, "Ineligible: Applicant age exceeds maximum permissible limit of {$crit['Max_Age']} years.", 422);
        }
        if ($crit['Income_Limit'] !== null && ($citizen['Income'] === null || $citizen['Income'] > $crit['Income_Limit'])) {
            jsonResponse(false, null, "Ineligible: Household income exceeds the scheme threshold of ₹" . number_format($crit['Income_Limit'], 2), 422);
        }
        if ($crit['Gender'] !== null && $crit['Gender'] !== '' && strtolower($crit['Gender']) !== 'all') {
            if ($citizen['Gender'] === null || strtolower($citizen['Gender']) !== strtolower($crit['Gender'])) {
                jsonResponse(false, null, "Ineligible: Scheme is restricted to {$crit['Gender']} applicants.", 422);
            }
        }
        if ($crit['Category'] !== null && $crit['Category'] !== '' && strtolower($crit['Category']) !== 'all') {
            if ($citizen['Category'] === null || strtolower($citizen['Category']) !== strtolower($crit['Category'])) {
                jsonResponse(false, null, "Ineligible: Scheme is restricted to {$crit['Category']} social category.", 422);
            }
        }
        if ($crit['Occupation'] !== null && $crit['Occupation'] !== '' && strtolower($crit['Occupation']) !== 'all') {
            if ($citizen['Occupation'] === null || strtolower($citizen['Occupation']) !== strtolower($crit['Occupation'])) {
                jsonResponse(false, null, "Ineligible: Scheme is restricted to {$crit['Occupation']} occupation.", 422);
            }
        }
    }

    // 4. Insert into Application table
    $stmtInsert = $pdo->prepare("
        INSERT INTO Application (
            Citizen_ID, Scheme_ID, Application_Date, Status, Remarks
        ) VALUES (
            :citizen_id, :scheme_id, NOW(), 'Pending', :remarks
        )
    ");
    $stmtInsert->execute([
        ':citizen_id' => $citizenId,
        ':scheme_id'  => $schemeId,
        ':remarks'    => $remarks
    ]);

    $appId = (int)$pdo->lastInsertId();

    jsonResponse(true, [
        'application_id'   => $appId,
        'scheme_name'      => $scheme['Scheme_Name'],
        'status'           => 'Pending',
        'application_date' => date('Y-m-d H:i:s')
    ], "Your application for '{$scheme['Scheme_Name']}' has been registered with Application ID #{$appId}.", 201);

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error while filing application: ' . $e->getMessage(), 500);
}
