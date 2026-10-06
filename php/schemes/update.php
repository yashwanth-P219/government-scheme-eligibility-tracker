<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Scheme and Criteria Update Endpoint (Government Officer Only)
 * Executes atomic transaction updating both Scheme and Eligibility_Criteria
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST to update scheme.', 405);
}

$input = getRequestData();
$schemeId = isset($input['scheme_id']) ? (int)$input['scheme_id'] : 0;

if ($schemeId <= 0) {
    jsonResponse(false, null, 'Valid Scheme ID is required.', 400);
}

$schemeName         = trim($input['scheme_name'] ?? '');
$departmentId       = !empty($input['department_id']) ? (int)$input['department_id'] : 1;
$description        = trim($input['description'] ?? '');
$benefitType        = trim($input['benefit_type'] ?? '');
$benefitDescription = trim($input['benefit_description'] ?? '');
$lastDate           = !empty($input['last_date']) ? trim($input['last_date']) : null;
$status             = in_array($input['status'] ?? 'Active', ['Active', 'Inactive']) ? $input['status'] : 'Active';

$minAge      = isset($input['min_age']) && $input['min_age'] !== '' ? (int)$input['min_age'] : null;
$maxAge      = isset($input['max_age']) && $input['max_age'] !== '' ? (int)$input['max_age'] : null;
$incomeLimit = isset($input['income_limit']) && $input['income_limit'] !== '' ? (float)$input['income_limit'] : null;
$category    = !empty($input['category']) && $input['category'] !== 'All' ? trim($input['category']) : null;
$gender      = !empty($input['gender']) && $input['gender'] !== 'All' ? trim($input['gender']) : null;
$occupation  = !empty($input['occupation']) && $input['occupation'] !== 'All' ? trim($input['occupation']) : null;
$education   = !empty($input['education']) && $input['education'] !== 'All' ? trim($input['education']) : null;
$state       = !empty($input['state']) && $input['state'] !== 'All' ? trim($input['state']) : null;
$district    = !empty($input['district']) && $input['district'] !== 'All' ? trim($input['district']) : null;

if (empty($schemeName) || empty($description) || empty($benefitDescription)) {
    jsonResponse(false, null, 'Scheme Name, Description, and Benefit Description are mandatory.', 400);
}

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // 1. Update Scheme Table
    $stmtScheme = $pdo->prepare("
        UPDATE Scheme SET
            Department_ID = :dept_id,
            Scheme_Name = :name,
            Description = :description,
            Benefit_Type = :benefit_type,
            Benefit_Description = :benefit_desc,
            Last_Date = :last_date,
            Status = :status
        WHERE Scheme_ID = :scheme_id
    ");
    $stmtScheme->execute([
        ':dept_id'      => $departmentId,
        ':name'         => $schemeName,
        ':description'  => $description,
        ':benefit_type' => $benefitType,
        ':benefit_desc' => $benefitDescription,
        ':last_date'    => $lastDate,
        ':status'       => $status,
        ':scheme_id'    => $schemeId
    ]);

    // 2. Check if Criteria record exists for this scheme, then update or insert
    $stmtCheck = $pdo->prepare("SELECT Criteria_ID FROM Eligibility_Criteria WHERE Scheme_ID = :scheme_id LIMIT 1");
    $stmtCheck->execute([':scheme_id' => $schemeId]);
    $criteria = $stmtCheck->fetch();

    if ($criteria) {
        $stmtCriteria = $pdo->prepare("
            UPDATE Eligibility_Criteria SET
                Min_Age = :min_age,
                Max_Age = :max_age,
                Income_Limit = :income_limit,
                Category = :category,
                Gender = :gender,
                Occupation = :occupation,
                Education = :education,
                State = :state,
                District = :district
            WHERE Scheme_ID = :scheme_id
        ");
        $stmtCriteria->execute([
            ':min_age'      => $minAge,
            ':max_age'      => $maxAge,
            ':income_limit' => $incomeLimit,
            ':category'     => $category,
            ':gender'       => $gender,
            ':occupation'   => $occupation,
            ':education'    => $education,
            ':state'        => $state,
            ':district'     => $district,
            ':scheme_id'    => $schemeId
        ]);
    } else {
        $stmtCriteria = $pdo->prepare("
            INSERT INTO Eligibility_Criteria (
                Scheme_ID, Min_Age, Max_Age, Income_Limit, Category,
                Gender, Occupation, Education, State, District
            ) VALUES (
                :scheme_id, :min_age, :max_age, :income_limit, :category,
                :gender, :occupation, :education, :state, :district
            )
        ");
        $stmtCriteria->execute([
            ':scheme_id'    => $schemeId,
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
    }

    $pdo->commit();
    jsonResponse(true, null, 'Scheme and eligibility criteria updated successfully!');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, null, 'Error updating scheme: ' . $e->getMessage(), 500);
}
