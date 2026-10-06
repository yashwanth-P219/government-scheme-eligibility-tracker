<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Scheme Creation Endpoint (Government Officer Only)
 * Executes atomic transaction creating Scheme + Eligibility Criteria records
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST to create a scheme.', 405);
}

$input = getRequestData();

// Scheme basic attributes
$schemeName         = trim($input['scheme_name'] ?? '');
$departmentId       = !empty($input['department_id']) ? (int)$input['department_id'] : ($_SESSION['department_id'] ?? 1);
$description        = trim($input['description'] ?? '');
$benefitType        = trim($input['benefit_type'] ?? 'Financial Assistance');
$benefitDescription = trim($input['benefit_description'] ?? '');
$lastDate           = !empty($input['last_date']) ? trim($input['last_date']) : null;
$status             = in_array($input['status'] ?? 'Active', ['Active', 'Inactive']) ? $input['status'] : 'Active';

// Eligibility criteria attributes (null if blank or empty)
$minAge      = isset($input['min_age']) && $input['min_age'] !== '' ? (int)$input['min_age'] : null;
$maxAge      = isset($input['max_age']) && $input['max_age'] !== '' ? (int)$input['max_age'] : null;
$incomeLimit = isset($input['income_limit']) && $input['income_limit'] !== '' ? (float)$input['income_limit'] : null;
$category    = !empty($input['category']) && $input['category'] !== 'All' ? trim($input['category']) : null;
$gender      = !empty($input['gender']) && $input['gender'] !== 'All' ? trim($input['gender']) : null;
$occupation  = !empty($input['occupation']) && $input['occupation'] !== 'All' ? trim($input['occupation']) : null;
$education   = !empty($input['education']) && $input['education'] !== 'All' ? trim($input['education']) : null;
$state       = !empty($input['state']) && $input['state'] !== 'All' ? trim($input['state']) : null;
$district    = !empty($input['district']) && $input['district'] !== 'All' ? trim($input['district']) : null;

// Validation
if (empty($schemeName) || strlen($schemeName) < 3) {
    jsonResponse(false, null, 'Scheme Name is required and must be at least 3 characters long.', 400);
}
if (empty($description)) {
    jsonResponse(false, null, 'Scheme Description is required.', 400);
}
if (empty($benefitDescription)) {
    jsonResponse(false, null, 'Benefit Description is required.', 400);
}
if ($minAge !== null && $maxAge !== null && $maxAge < $minAge) {
    jsonResponse(false, null, 'Maximum Age cannot be less than Minimum Age.', 400);
}

$pdo = getDB();

try {
    // BEGIN TRANSACTION
    $pdo->beginTransaction();

    // 1. Insert into Scheme table
    $sqlScheme = "
        INSERT INTO Scheme (
            Department_ID, Scheme_Name, Description, Benefit_Type,
            Benefit_Description, Last_Date, Status
        ) VALUES (
            :dept_id, :name, :description, :benefit_type,
            :benefit_description, :last_date, :status
        )
    ";
    $stmtScheme = $pdo->prepare($sqlScheme);
    $stmtScheme->execute([
        ':dept_id'             => $departmentId,
        ':name'                => $schemeName,
        ':description'         => $description,
        ':benefit_type'        => $benefitType,
        ':benefit_description' => $benefitDescription,
        ':last_date'           => $lastDate,
        ':status'              => $status
    ]);
    $newSchemeId = (int)$pdo->lastInsertId();

    // 2. Insert into Eligibility_Criteria table
    $sqlCriteria = "
        INSERT INTO Eligibility_Criteria (
            Scheme_ID, Min_Age, Max_Age, Income_Limit, Category,
            Gender, Occupation, Education, State, District
        ) VALUES (
            :scheme_id, :min_age, :max_age, :income_limit, :category,
            :gender, :occupation, :education, :state, :district
        )
    ";
    $stmtCriteria = $pdo->prepare($sqlCriteria);
    $stmtCriteria->execute([
        ':scheme_id'    => $newSchemeId,
        ':min_age'      => $minAge,
        ':max_age'      => $maxAge,
        ':income_limit' => $incomeLimit,
        ':category'     => $category,
        ':gender'       => $gender,
        ':occupation'   => $occupation,
        ':education'    => $education,
        ':state'        => $state,
        ':district'     => $district
    ]);

    // COMMIT TRANSACTION
    $pdo->commit();

    jsonResponse(true, [
        'scheme_id' => $newSchemeId,
        'scheme_name' => $schemeName
    ], 'Welfare scheme and eligibility criteria successfully registered in the database!', 201);

} catch (Exception $e) {
    // ROLLBACK ON ERROR
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, null, 'Transaction failed while creating scheme: ' . $e->getMessage(), 500);
}
