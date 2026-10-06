<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Central Database-Driven Eligibility Evaluation Engine
 * Evaluates citizen socioeconomic and demographic attributes against
 * rules in Eligibility_Criteria using prepared SQL queries.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

// May be called by logged-in citizen or with custom profile parameters
$citizenId = isCitizen() ? (int)$_SESSION['user_id'] : null;
$pdo = getDB();

// Input demographic attributes
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? getRequestData() : $_GET;

// If citizen is logged in, load profile from database as base attributes
$profile = null;
if ($citizenId) {
    $stmtProfile = $pdo->prepare("
        SELECT Age, Gender, Category, Income, Occupation, Education, District, State
        FROM Citizen
        WHERE Citizen_ID = :id
    ");
    $stmtProfile->execute([':id' => $citizenId]);
    $profile = $stmtProfile->fetch();
}

// Allow request parameters to override or supply missing citizen profile values
$age        = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : ($profile['Age'] ?? null);
$gender     = !empty($input['gender']) ? trim($input['gender']) : ($profile['Gender'] ?? null);
$category   = !empty($input['category']) ? trim($input['category']) : ($profile['Category'] ?? null);
$income     = isset($input['income']) && $input['income'] !== '' ? (float)$input['income'] : ($profile['Income'] ?? null);
$occupation = !empty($input['occupation']) ? trim($input['occupation']) : ($profile['Occupation'] ?? null);
$education  = !empty($input['education']) ? trim($input['education']) : ($profile['Education'] ?? null);
$state      = !empty($input['state']) ? trim($input['state']) : ($profile['State'] ?? null);
$district   = !empty($input['district']) ? trim($input['district']) : ($profile['District'] ?? null);

$filterEligibleOnly = isset($input['eligible_only']) && ($input['eligible_only'] === '1' || $input['eligible_only'] === 'true');

try {
    // 1. Fetch all active schemes with their eligibility criteria and department
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
            " : "NULL AS Citizen_Application_Status, NULL AS Citizen_Application_ID") . "
        FROM Scheme s
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
        WHERE s.Status = 'Active'
          AND (s.Last_Date IS NULL OR s.Last_Date >= CURDATE())
        ORDER BY s.Scheme_ID ASC
    ";

    $params = [];
    if ($citizenId) {
        $params[':citizen_id'] = $citizenId;
        $params[':citizen_id2'] = $citizenId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allSchemes = $stmt->fetchAll();

    $results = [];
    $eligibleCount = 0;

    foreach ($allSchemes as $row) {
        $reasons = [];
        $isEligible = true;

        // Age Check
        if ($row['Min_Age'] !== null) {
            if ($age === null) {
                $isEligible = false;
                $reasons[] = "Minimum age requirement is {$row['Min_Age']} years (age not set in profile)";
            } elseif ($age < $row['Min_Age']) {
                $isEligible = false;
                $reasons[] = "Applicant age ({$age}) is below minimum requirement of {$row['Min_Age']} years";
            }
        }

        if ($row['Max_Age'] !== null) {
            if ($age === null) {
                $isEligible = false;
                $reasons[] = "Maximum age requirement is {$row['Max_Age']} years (age not set in profile)";
            } elseif ($age > $row['Max_Age']) {
                $isEligible = false;
                $reasons[] = "Applicant age ({$age}) exceeds maximum permissible age of {$row['Max_Age']} years";
            }
        }

        // Income Limit Check
        if ($row['Income_Limit'] !== null) {
            if ($income === null) {
                $isEligible = false;
                $reasons[] = "Annual income threshold is ₹" . number_format($row['Income_Limit'], 2) . " (income not set in profile)";
            } elseif ($income > $row['Income_Limit']) {
                $isEligible = false;
                $reasons[] = "Annual income (₹" . number_format($income, 2) . ") exceeds threshold limit of ₹" . number_format($row['Income_Limit'], 2);
            }
        }

        // Gender Check
        if ($row['Req_Gender'] !== null && $row['Req_Gender'] !== '' && strtolower($row['Req_Gender']) !== 'all') {
            if ($gender === null || strtolower($gender) !== strtolower($row['Req_Gender'])) {
                $isEligible = false;
                $reasons[] = "Scheme restricted to {$row['Req_Gender']} applicants only (applicant: " . ($gender ?: 'Not specified') . ")";
            }
        }

        // Category Check
        if ($row['Req_Category'] !== null && $row['Req_Category'] !== '' && strtolower($row['Req_Category']) !== 'all') {
            if ($category === null || strtolower($category) !== strtolower($row['Req_Category'])) {
                $isEligible = false;
                $reasons[] = "Applicable strictly to {$row['Req_Category']} category (applicant: " . ($category ?: 'Not specified') . ")";
            }
        }

        // Occupation Check
        if ($row['Req_Occupation'] !== null && $row['Req_Occupation'] !== '' && strtolower($row['Req_Occupation']) !== 'all') {
            if ($occupation === null || strtolower($occupation) !== strtolower($row['Req_Occupation'])) {
                $isEligible = false;
                $reasons[] = "Designated for {$row['Req_Occupation']} (applicant: " . ($occupation ?: 'Not specified') . ")";
            }
        }

        // Education Check
        if ($row['Req_Education'] !== null && $row['Req_Education'] !== '' && strtolower($row['Req_Education']) !== 'all') {
            // Check matching or higher education level
            $eduRank = [
                'none' => 0,
                'below 10th' => 1,
                '10th pass' => 2,
                '12th pass' => 3,
                'diploma' => 3,
                'graduate' => 4,
                'post graduate' => 5
            ];
            $reqKey = strtolower($row['Req_Education']);
            $citKey = strtolower($education ?? '');
            
            $reqLevel = $eduRank[$reqKey] ?? 2;
            $citLevel = $eduRank[$citKey] ?? 0;

            if ($citLevel < $reqLevel) {
                $isEligible = false;
                $reasons[] = "Requires at least {$row['Req_Education']} (applicant qualification: " . ($education ?: 'Not specified') . ")";
            }
        }

        // State Check
        if ($row['Req_State'] !== null && $row['Req_State'] !== '' && strtolower($row['Req_State']) !== 'all') {
            if ($state === null || strtolower($state) !== strtolower($row['Req_State'])) {
                $isEligible = false;
                $reasons[] = "Restricted to residents of {$row['Req_State']} state";
            }
        }

        // District Check
        if ($row['Req_District'] !== null && $row['Req_District'] !== '' && strtolower($row['Req_District']) !== 'all') {
            if ($district === null || strtolower($district) !== strtolower($row['Req_District'])) {
                $isEligible = false;
                $reasons[] = "Restricted to {$row['Req_District']} district";
            }
        }

        $row['is_eligible'] = $isEligible;
        $row['ineligibility_reasons'] = $reasons;

        if ($isEligible) {
            $eligibleCount++;
        }

        if (!$filterEligibleOnly || $isEligible) {
            $results[] = $row;
        }
    }

    jsonResponse(true, [
        'evaluated_profile' => [
            'age'        => $age,
            'gender'     => $gender,
            'category'   => $category,
            'income'     => $income,
            'occupation' => $occupation,
            'education'  => $education,
            'state'      => $state,
            'district'   => $district
        ],
        'total_active_schemes' => count($allSchemes),
        'total_eligible_schemes' => $eligibleCount,
        'schemes' => $results
    ], 'Eligibility analysis executed against database criteria.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error during eligibility evaluation: ' . $e->getMessage(), 500);
}
