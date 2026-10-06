<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Benefits & Demographic SQL Analytics Endpoint (Officer Only)
 * Executes financial and demographic aggregations with SUM, AVG, and GROUP BY
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

$pdo = getDB();

try {
    // -------------------------------------------------------------------------
    // REPORT 4: Benefits Distributed by Scheme
    // Demonstrates 4-table JOIN with SUM, AVG, and COALESCE
    // -------------------------------------------------------------------------
    $sql4 = "
        SELECT 
            s.Scheme_ID,
            s.Scheme_Name,
            d.Department_Name,
            COUNT(b.Benefit_ID) AS Beneficiary_Count,
            COALESCE(SUM(b.Benefit_Amount), 0.00) AS Total_Benefits_Amount,
            COALESCE(AVG(b.Benefit_Amount), 0.00) AS Average_Benefit_Amount
        FROM Scheme s
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
        LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
        LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
        GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
        ORDER BY Total_Benefits_Amount DESC
    ";
    $stmt4 = $pdo->query($sql4);
    $report4 = $stmt4->fetchAll();

    // -------------------------------------------------------------------------
    // REPORT 5: Benefits Distributed by Month
    // Demonstrates DATE_FORMAT grouping and financial timeline metrics
    // -------------------------------------------------------------------------
    $sql5 = "
        SELECT 
            DATE_FORMAT(b.Benefit_Date, '%Y-%m') AS Disbursement_Month,
            COUNT(b.Benefit_ID) AS Total_Disbursements,
            SUM(b.Benefit_Amount) AS Monthly_Disbursed_Amount
        FROM Benefits b
        WHERE b.Payment_Status IN ('Paid', 'Processing')
        GROUP BY DATE_FORMAT(b.Benefit_Date, '%Y-%m')
        ORDER BY Disbursement_Month ASC
    ";
    $stmt5 = $pdo->query($sql5);
    $report5 = $stmt5->fetchAll();

    // -------------------------------------------------------------------------
    // REPORT 6: Citizen Category Distribution
    // Demographic inclusion analysis using COUNT and percentage share
    // -------------------------------------------------------------------------
    $sql6 = "
        SELECT 
            COALESCE(Category, 'General') AS Social_Category,
            COUNT(Citizen_ID) AS Citizen_Count,
            ROUND(COUNT(Citizen_ID) * 100.0 / (SELECT COUNT(*) FROM Citizen), 1) AS Percentage_Share
        FROM Citizen
        GROUP BY Social_Category
        ORDER BY Citizen_Count DESC
    ";
    $stmt6 = $pdo->query($sql6);
    $report6 = $stmt6->fetchAll();

    // Overall summary metrics
    $totalDisbursed = 0.0;
    foreach ($report4 as $r) {
        $totalDisbursed += (float)$r['Total_Benefits_Amount'];
    }

    jsonResponse(true, [
        'benefits_by_scheme'            => $report4,
        'benefits_by_month'             => $report5,
        'citizen_category_distribution' => $report6,
        'overall_total_disbursed'       => $totalDisbursed
    ], 'Financial and demographic analytics loaded.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error generating financial analytics: ' . $e->getMessage(), 500);
}
