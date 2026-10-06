<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Application Analytics Endpoint (Officer Only)
 * Executes complex SQL analytical queries with JOINS, AGGREGATES, and GROUP BY
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireOfficer(true);

$pdo = getDB();

try {
    // -------------------------------------------------------------------------
    // REPORT 1: Applications by Scheme
    // Uses LEFT JOIN and GROUP BY so schemes with 0 applications still appear
    // -------------------------------------------------------------------------
    $sql1 = "
        SELECT 
            s.Scheme_ID,
            s.Scheme_Name,
            d.Department_Name,
            COUNT(a.Application_ID) AS Total_Applications
        FROM Scheme s
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
        GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
        ORDER BY Total_Applications DESC
    ";
    $stmt1 = $pdo->query($sql1);
    $report1 = $stmt1->fetchAll();

    // -------------------------------------------------------------------------
    // REPORT 2: Applications by Status
    // Demonstrates Subquery calculation of exact percentage share
    // -------------------------------------------------------------------------
    $sql2 = "
        SELECT 
            Status,
            COUNT(*) AS Total_Count,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM Application), 1) AS Percentage
        FROM Application
        GROUP BY Status
        ORDER BY Total_Count DESC
    ";
    $stmt2 = $pdo->query($sql2);
    $report2 = $stmt2->fetchAll();

    // -------------------------------------------------------------------------
    // REPORT 3: Approved Applications by Department
    // Demonstrates 3-way INNER JOIN and WHERE filter
    // -------------------------------------------------------------------------
    $sql3 = "
        SELECT 
            d.Department_ID,
            d.Department_Name,
            COUNT(a.Application_ID) AS Approved_Count
        FROM Department d
        INNER JOIN Scheme s ON d.Department_ID = s.Department_ID
        INNER JOIN Application a ON s.Scheme_ID = a.Scheme_ID
        WHERE a.Status = 'Approved'
        GROUP BY d.Department_ID, d.Department_Name
        ORDER BY Approved_Count DESC
    ";
    $stmt3 = $pdo->query($sql3);
    $report3 = $stmt3->fetchAll();

    // -------------------------------------------------------------------------
    // REPORT 7: Scheme Utilization Rate (Approved vs Applied)
    // Demonstrates conditional SUM aggregation and NULLIF division guard
    // -------------------------------------------------------------------------
    $sql7 = "
        SELECT 
            s.Scheme_ID,
            s.Scheme_Name,
            COUNT(a.Application_ID) AS Total_Applied,
            SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) AS Total_Approved,
            ROUND(
                (SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) * 100.0) / 
                NULLIF(COUNT(a.Application_ID), 0), 
                1
            ) AS Utilization_Rate_Pct
        FROM Scheme s
        LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
        GROUP BY s.Scheme_ID, s.Scheme_Name
        ORDER BY Total_Applied DESC
    ";
    $stmt7 = $pdo->query($sql7);
    $report7 = $stmt7->fetchAll();

    // -------------------------------------------------------------------------
    // REPORT 8: Pending Applications Summary & Age Analysis
    // Uses DATEDIFF() to calculate pending turnaround backlog
    // -------------------------------------------------------------------------
    $sql8 = "
        SELECT 
            a.Application_ID,
            c.Name AS Applicant_Name,
            c.Email AS Applicant_Email,
            s.Scheme_Name,
            d.Department_Name,
            a.Application_Date,
            a.Status,
            DATEDIFF(CURDATE(), a.Application_Date) AS Days_Pending
        FROM Application a
        INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
        INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
        INNER JOIN Department d ON s.Department_ID = d.Department_ID
        WHERE a.Status IN ('Pending', 'Under Review')
        ORDER BY Days_Pending DESC
    ";
    $stmt8 = $pdo->query($sql8);
    $report8 = $stmt8->fetchAll();

    jsonResponse(true, [
        'applications_by_scheme'        => $report1,
        'applications_by_status'        => $report2,
        'approved_by_department'        => $report3,
        'scheme_utilization_rates'      => $report7,
        'pending_applications_analysis' => $report8
    ], 'Application SQL analytics generated successfully.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error generating application analytics: ' . $e->getMessage(), 500);
}
