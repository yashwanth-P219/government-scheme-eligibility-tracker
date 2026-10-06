-- ==============================================================================
-- DATABASE MANAGEMENT SYSTEM (DBMS) COURSE-BASED PROJECT
-- Project Title: Smart Government Scheme Eligibility and Benefit Tracker
-- File: database/queries_sqlite.sql
-- Academic Demonstration of SQL Relational Queries, Aggregations, Joins,
-- Subqueries, Analytical Reports, and Multi-Table ACID Transactions.
-- ==============================================================================

-- 1. INNER JOIN: Schemes with their Sponsoring Department
SELECT 
    s.Scheme_ID,
    s.Scheme_Name,
    d.Department_Name,
    s.Benefit_Type,
    s.Last_Date,
    s.Status
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
ORDER BY s.Scheme_ID ASC;

-- 2. MULTI-TABLE JOIN: Complete Application Dossier
SELECT 
    a.Application_ID,
    c.Name AS Citizen_Name,
    c.Email AS Citizen_Email,
    c.Category AS Citizen_Category,
    c.Income AS Citizen_Income,
    s.Scheme_Name,
    d.Department_Name,
    a.Application_Date,
    a.Status AS Application_Status,
    o.Name AS Approving_Officer,
    ap.Decision AS Officer_Decision,
    b.Benefit_Amount,
    b.Payment_Status,
    b.Transaction_Reference
FROM Application a
INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
INNER JOIN Department d ON s.Department_ID = d.Department_ID
LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
LEFT JOIN Officer o ON ap.Officer_ID = o.Officer_ID
LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
ORDER BY a.Application_ID DESC;

-- 3. AGGREGATION & GROUP BY with HAVING: Departments with 2+ Schemes
SELECT 
    d.Department_Name,
    COUNT(s.Scheme_ID) AS Active_Scheme_Count
FROM Department d
INNER JOIN Scheme s ON d.Department_ID = s.Department_ID
WHERE s.Status = 'Active'
GROUP BY d.Department_ID, d.Department_Name
HAVING COUNT(s.Scheme_ID) >= 2
ORDER BY Active_Scheme_Count DESC;

-- 4. SCALAR SUBQUERY: Citizens earning below the average registered income
SELECT 
    Citizen_ID, Name, Occupation, Income
FROM Citizen
WHERE Income < (SELECT AVG(Income) FROM Citizen)
ORDER BY Income ASC;

-- 5. SUBQUERY WITH IN: Citizens with at least one Approved application
SELECT 
    Citizen_ID, Name, Email, District, State
FROM Citizen
WHERE Citizen_ID IN (
    SELECT DISTINCT Citizen_ID 
    FROM Application 
    WHERE Status = 'Approved'
);

-- 6. CORE ELIGIBILITY EVALUATION ENGINE QUERY (Demonstrated for Citizen #1)
SELECT 
    s.Scheme_ID,
    s.Scheme_Name,
    d.Department_Name,
    s.Benefit_Type,
    s.Benefit_Description,
    s.Last_Date
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
WHERE s.Status = 'Active'
  AND (s.Last_Date IS NULL OR date(s.Last_Date) >= date('now'))
  AND (e.Min_Age IS NULL OR e.Min_Age <= 22)
  AND (e.Max_Age IS NULL OR e.Max_Age >= 22)
  AND (e.Income_Limit IS NULL OR e.Income_Limit >= 120000.0)
  AND (e.Gender IS NULL OR e.Gender = 'All' OR e.Gender = 'Male')
  AND (e.Category IS NULL OR e.Category = 'All' OR e.Category = 'General')
  AND (e.Occupation IS NULL OR e.Occupation = 'All' OR e.Occupation = 'Student')
  AND (e.Education IS NULL OR e.Education = 'All' OR e.Education = '12th Pass');

-- 7. REPORT 1: Applications by Scheme
SELECT 
    s.Scheme_ID,
    s.Scheme_Name,
    d.Department_Name,
    COUNT(a.Application_ID) AS Total_Applications
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
ORDER BY Total_Applications DESC;

-- 8. REPORT 2: Applications by Status
SELECT 
    Status,
    COUNT(*) AS Total_Count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM Application), 1) AS Percentage
FROM Application
GROUP BY Status
ORDER BY Total_Count DESC;

-- 9. REPORT 4: Benefits Distributed by Scheme (SUM & AVG)
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
ORDER BY Total_Benefits_Amount DESC;

-- 10. MULTI-TABLE ACID TRANSACTION DEMONSTRATION
BEGIN TRANSACTION;

UPDATE Application 
SET Status = 'Approved', Remarks = 'Application verified and sanctioned by Departmental Officer.' 
WHERE Application_ID = 10;

INSERT INTO Approval (Application_ID, Officer_ID, Approval_Date, Decision, Remarks)
VALUES (10, 1, datetime('now'), 'Approved', 'Criteria satisfied.');

INSERT INTO Benefits (Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference)
VALUES (last_insert_rowid(), 2000.00, date('now'), 'Processing', 'TXN-KISAN-2026-AUTO-991');

COMMIT;
