<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Central Dashboard Metrics Aggregator
 * Computes live database statistics for Citizen and Officer Dashboards
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

if (!isAuthenticated()) {
    jsonResponse(false, null, 'Authentication required.', 401);
}

$pdo = getDB();
$currentUser = getCurrentUser();

try {
    if (isOfficer()) {
        // =====================================================================
        // OFFICER DASHBOARD AGGREGATIONS
        // =====================================================================
        
        // 1. Scheme counts
        $stmtSchemes = $pdo->query("
            SELECT 
                COUNT(*) AS Total_Schemes,
                SUM(CASE WHEN Status = 'Active' THEN 1 ELSE 0 END) AS Active_Schemes,
                SUM(CASE WHEN Status = 'Inactive' THEN 1 ELSE 0 END) AS Inactive_Schemes
            FROM Scheme
        ");
        $schemeStats = $stmtSchemes->fetch();

        // 2. Application counts by status
        $stmtApps = $pdo->query("
            SELECT 
                COUNT(*) AS Total_Applications,
                SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) AS Pending_Applications,
                SUM(CASE WHEN Status = 'Under Review' THEN 1 ELSE 0 END) AS Under_Review_Applications,
                SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) AS Approved_Applications,
                SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) AS Rejected_Applications
            FROM Application
        ");
        $appStats = $stmtApps->fetch();

        // 3. Benefits aggregation
        $stmtBenefits = $pdo->query("
            SELECT 
                COUNT(*) AS Total_Benefits_Count,
                COALESCE(SUM(Benefit_Amount), 0.00) AS Total_Benefits_Amount,
                COALESCE(SUM(CASE WHEN Payment_Status = 'Paid' THEN Benefit_Amount ELSE 0 END), 0.00) AS Paid_Benefits_Amount,
                COALESCE(SUM(CASE WHEN Payment_Status = 'Processing' THEN Benefit_Amount ELSE 0 END), 0.00) AS Processing_Benefits_Amount,
                COALESCE(SUM(CASE WHEN Payment_Status = 'Pending' THEN Benefit_Amount ELSE 0 END), 0.00) AS Pending_Benefits_Amount
            FROM Benefits
        ");
        $benefitStats = $stmtBenefits->fetch();

        // 4. Citizen count
        $stmtCit = $pdo->query("SELECT COUNT(*) AS Total_Citizens FROM Citizen");
        $citStats = $stmtCit->fetch();

        // 5. Recent 5 applications requiring review
        $stmtRecent = $pdo->query("
            SELECT 
                a.Application_ID,
                a.Citizen_ID,
                c.Name AS Citizen_Name,
                c.Email AS Citizen_Email,
                s.Scheme_Name,
                d.Department_Name,
                a.Application_Date,
                a.Status
            FROM Application a
            INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            ORDER BY a.Application_ID DESC
            LIMIT 6
        ");
        $recentApplications = $stmtRecent->fetchAll();

        jsonResponse(true, [
            'role'                 => 'OFFICER',
            'officer_name'         => $currentUser['name'],
            'designation'          => $currentUser['designation'],
            'department_name'      => $currentUser['department_name'] ?? 'Government Authority',
            'total_schemes'        => (int)($schemeStats['Total_Schemes'] ?? 0),
            'active_schemes'       => (int)($schemeStats['Active_Schemes'] ?? 0),
            'inactive_schemes'     => (int)($schemeStats['Inactive_Schemes'] ?? 0),
            'total_applications'   => (int)($appStats['Total_Applications'] ?? 0),
            'pending_applications' => (int)($appStats['Pending_Applications'] ?? 0),
            'under_review_applications' => (int)($appStats['Under_Review_Applications'] ?? 0),
            'approved_applications'     => (int)($appStats['Approved_Applications'] ?? 0),
            'rejected_applications'     => (int)($appStats['Rejected_Applications'] ?? 0),
            'total_benefits_amount'     => (float)($benefitStats['Total_Benefits_Amount'] ?? 0.0),
            'paid_benefits_amount'      => (float)($benefitStats['Paid_Benefits_Amount'] ?? 0.0),
            'total_citizens'       => (int)($citStats['Total_Citizens'] ?? 0),
            'recent_applications'  => $recentApplications
        ], 'Officer dashboard statistics loaded.');

    } else {
        // =====================================================================
        // CITIZEN DASHBOARD AGGREGATIONS
        // =====================================================================
        $citizenId = (int)$currentUser['id'];

        // 1. Total available active schemes
        $stmtTotalSchemes = $pdo->query("SELECT COUNT(*) AS Total FROM Scheme WHERE Status = 'Active' AND (Last_Date IS NULL OR Last_Date >= CURDATE())");
        $totalActiveSchemes = (int)$stmtTotalSchemes->fetch()['Total'];

        // 2. Citizen's own applications counts
        $stmtCitApps = $pdo->prepare("
            SELECT 
                COUNT(*) AS Total_Submitted,
                SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) AS Pending_Count,
                SUM(CASE WHEN Status = 'Under Review' THEN 1 ELSE 0 END) AS Review_Count,
                SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) AS Approved_Count,
                SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) AS Rejected_Count
            FROM Application
            WHERE Citizen_ID = :cid
        ");
        $stmtCitApps->execute([':cid' => $citizenId]);
        $citAppStats = $stmtCitApps->fetch();

        // 3. Citizen's total received benefits
        $stmtCitBenefits = $pdo->prepare("
            SELECT 
                COUNT(b.Benefit_ID) AS Benefit_Count,
                COALESCE(SUM(b.Benefit_Amount), 0.00) AS Total_Benefits_Sanctioned,
                COALESCE(SUM(CASE WHEN b.Payment_Status = 'Paid' THEN b.Benefit_Amount ELSE 0 END), 0.00) AS Total_Benefits_Paid
            FROM Benefits b
            INNER JOIN Approval ap ON b.Approval_ID = ap.Approval_ID
            INNER JOIN Application a ON ap.Application_ID = a.Application_ID
            WHERE a.Citizen_ID = :cid
        ");
        $stmtCitBenefits->execute([':cid' => $citizenId]);
        $citBenefitStats = $stmtCitBenefits->fetch();

        // 4. Fetch Citizen Profile to check completeness and eligibility count
        $stmtProfile = $pdo->prepare("SELECT * FROM Citizen WHERE Citizen_ID = :cid LIMIT 1");
        $stmtProfile->execute([':cid' => $citizenId]);
        $profile = $stmtProfile->fetch();

        $fields = ['Name', 'Email', 'Age', 'Gender', 'Category', 'Income', 'Occupation', 'Education', 'District', 'State', 'Phone'];
        $completed = 0;
        foreach ($fields as $f) {
            if (!empty($profile[$f]) || (isset($profile[$f]) && is_numeric($profile[$f]))) {
                $completed++;
            }
        }
        $completenessPct = round(($completed / count($fields)) * 100);

        // 5. Evaluate eligible scheme count using SQL matching query
        $stmtEligible = $pdo->prepare("
            SELECT COUNT(DISTINCT s.Scheme_ID) AS Eligible_Count
            FROM Scheme s
            INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
            WHERE s.Status = 'Active'
              AND (s.Last_Date IS NULL OR s.Last_Date >= CURDATE())
              AND (e.Min_Age IS NULL OR e.Min_Age <= :age)
              AND (e.Max_Age IS NULL OR e.Max_Age >= :age2)
              AND (e.Income_Limit IS NULL OR e.Income_Limit >= :income)
              AND (e.Gender IS NULL OR e.Gender = 'All' OR e.Gender = :gender)
              AND (e.Category IS NULL OR e.Category = 'All' OR e.Category = :category)
              AND (e.Occupation IS NULL OR e.Occupation = 'All' OR e.Occupation = :occupation)
              AND (e.Education IS NULL OR e.Education = 'All' OR e.Education = :education)
              AND (e.State IS NULL OR e.State = 'All' OR e.State = :state)
              AND (e.District IS NULL OR e.District = 'All' OR e.District = :district)
        ");
        $stmtEligible->execute([
            ':age'        => $profile['Age'] ?? 0,
            ':age2'       => $profile['Age'] ?? 999,
            ':income'     => $profile['Income'] ?? 0.0,
            ':gender'     => $profile['Gender'] ?? '',
            ':category'   => $profile['Category'] ?? '',
            ':occupation' => $profile['Occupation'] ?? '',
            ':education'  => $profile['Education'] ?? '',
            ':state'      => $profile['State'] ?? '',
            ':district'   => $profile['District'] ?? ''
        ]);
        $eligibleCount = (int)$stmtEligible->fetch()['Eligible_Count'];

        // 6. Recent applications for this citizen
        $stmtRecent = $pdo->prepare("
            SELECT 
                a.Application_ID,
                s.Scheme_Name,
                s.Benefit_Type,
                d.Department_Name,
                a.Application_Date,
                a.Status,
                a.Remarks,
                b.Benefit_Amount,
                b.Payment_Status
            FROM Application a
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
            LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
            WHERE a.Citizen_ID = :cid
            ORDER BY a.Application_ID DESC
            LIMIT 5
        ");
        $stmtRecent->execute([':cid' => $citizenId]);
        $recentApplications = $stmtRecent->fetchAll();

        jsonResponse(true, [
            'role'                      => 'CITIZEN',
            'citizen_name'              => $currentUser['name'],
            'total_available_schemes'   => $totalActiveSchemes,
            'eligible_schemes'          => $eligibleCount,
            'applications_submitted'    => (int)($citAppStats['Total_Submitted'] ?? 0),
            'pending_applications'      => (int)($citAppStats['Pending_Count'] ?? 0),
            'under_review_applications' => (int)($citAppStats['Review_Count'] ?? 0),
            'approved_applications'     => (int)($citAppStats['Approved_Count'] ?? 0),
            'rejected_applications'     => (int)($citAppStats['Rejected_Count'] ?? 0),
            'total_benefits_sanctioned' => (float)($citBenefitStats['Total_Benefits_Sanctioned'] ?? 0.0),
            'total_benefits_paid'       => (float)($citBenefitStats['Total_Benefits_Paid'] ?? 0.0),
            'profile_completeness'      => $completenessPct,
            'recent_applications'       => $recentApplications
        ], 'Citizen dashboard statistics loaded.');
    }

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error aggregating dashboard metrics: ' . $e->getMessage(), 500);
}
