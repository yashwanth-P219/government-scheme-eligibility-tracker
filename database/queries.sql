-- ==============================================================================
-- DATABASE MANAGEMENT SYSTEM (DBMS) COURSE-BASED PROJECT
-- Project Title: Smart Government Scheme Eligibility and Benefit Tracker
-- Academic Context: B.Tech. II Year I Semester – AIML & B Section
-- Relational Database SQL Demonstration Script (SQLite WebAssembly Compatible)
-- ==============================================================================

-- ==============================================================================
-- 1. BASIC CRUD DEMONSTRATIONS
-- ==============================================================================

-- 1.1 INSERT a new citizen
INSERT INTO Citizen (Name, Email, Password_Hash, Age, Gender, Category, Income, Occupation, Education, District, State, Phone)
VALUES ('Kavita Rao', 'kavita.rao@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 29, 'Female', 'OBC', 190000.00, 'Self-Employed', 'Graduate', 'Mysuru', 'Karnataka', '9845012345');

-- 1.2 SELECT active schemes sorted by name
SELECT Scheme_ID, Scheme_Name, Benefit_Type, Last_Date, Status
FROM Scheme
WHERE Status = 'Active'
ORDER BY Scheme_Name ASC;

-- 1.3 UPDATE citizen profile
UPDATE Citizen
SET Income = 175000.00, Occupation = 'Artisan', District = 'Varanasi'
WHERE Citizen_ID = 3;

-- 1.4 DELETE an obsolete application (if still Pending and not approved)
DELETE FROM Application
WHERE Application_ID = 999 AND Status = 'Pending';


-- ==============================================================================
-- 2. RELATIONAL JOINS DEMONSTRATION
-- ==============================================================================

-- 2.1 INNER JOIN: Application details with Citizen and Scheme names
SELECT 
    a.Application_ID,
    c.Name AS Citizen_Name,
    c.Category AS Citizen_Category,
    c.Income AS Citizen_Income,
    s.Scheme_Name,
    d.Department_Name,
    a.Application_Date,
    a.Status
FROM Application a
INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
INNER JOIN Department d ON s.Department_ID = d.Department_ID
ORDER BY a.Application_Date DESC;

-- 2.2 LEFT JOIN: Schemes with count of applications and total benefits
SELECT 
    s.Scheme_ID,
    s.Scheme_Name,
    d.Department_Name,
    COUNT(DISTINCT a.Application_ID) AS Total_Applications,
    COALESCE(SUM(b.Benefit_Amount), 0.0) AS Total_Disbursed
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
ORDER BY Total_Applications DESC;


-- ==============================================================================
-- 3. AGGREGATE FUNCTIONS, GROUP BY & HAVING
-- ==============================================================================

-- 3.1 Aggregate metrics: Count, Sum, Avg, Min, Max of benefits
SELECT 
    COUNT(Benefit_ID) AS Total_Grants_Count,
    ROUND(SUM(Benefit_Amount), 2) AS Total_Amount_Disbursed,
    ROUND(AVG(Benefit_Amount), 2) AS Average_Grant_Size,
    ROUND(MIN(Benefit_Amount), 2) AS Minimum_Grant_Size,
    ROUND(MAX(Benefit_Amount), 2) AS Maximum_Grant_Size
FROM Benefits
WHERE Payment_Status IN ('Paid', 'Processing');

-- 3.2 GROUP BY with HAVING: Departments with 2 or more approved applications
SELECT 
    d.Department_ID,
    d.Department_Name,
    COUNT(a.Application_ID) AS Approved_Count
FROM Department d
INNER JOIN Scheme s ON d.Department_ID = s.Department_ID
INNER JOIN Application a ON s.Scheme_ID = a.Scheme_ID
WHERE a.Status = 'Approved'
GROUP BY d.Department_ID, d.Department_Name
HAVING COUNT(a.Application_ID) >= 2
ORDER BY Approved_Count DESC;


-- ==============================================================================
-- 4. SUBQUERIES DEMONSTRATION
-- ==============================================================================

-- 4.1 Subquery with IN: Citizens who have received sanctioned benefits
SELECT Citizen_ID, Name, Email, Category, Occupation
FROM Citizen
WHERE Citizen_ID IN (
    SELECT a.Citizen_ID
    FROM Application a
    INNER JOIN Approval ap ON a.Application_ID = ap.Application_ID
    INNER JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
    WHERE ap.Decision = 'Approved'
);

-- 4.2 Correlated Subquery: Schemes whose application count is above average
SELECT s.Scheme_ID, s.Scheme_Name
FROM Scheme s
WHERE (
    SELECT COUNT(*) 
    FROM Application a 
    WHERE a.Scheme_ID = s.Scheme_ID
) > (
    SELECT AVG(scheme_app_count) 
    FROM (
        SELECT COUNT(*) AS scheme_app_count 
        FROM Application 
        GROUP BY Scheme_ID
    )
);


-- ==============================================================================
-- 5. CORE DYNAMIC ELIGIBILITY EVALUATION QUERY (SQL Engine Core)
-- Evaluates citizen profile against criteria where NULL represents universal match
-- ==============================================================================
SELECT 
    s.Scheme_ID,
    s.Scheme_Name,
    d.Department_Name,
    s.Benefit_Type,
    s.Benefit_Description,
    s.Last_Date,
    e.Min_Age,
    e.Max_Age,
    e.Income_Limit,
    e.Category AS Required_Category,
    e.Gender AS Required_Gender,
    e.Occupation AS Required_Occupation,
    e.Education AS Required_Education
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
WHERE s.Status = 'Active'
  AND (s.Last_Date IS NULL OR s.Last_Date >= DATE('now'))
  AND (e.Min_Age IS NULL OR e.Min_Age <= 22)                  -- Citizen Age: 22
  AND (e.Max_Age IS NULL OR e.Max_Age >= 22)
  AND (e.Income_Limit IS NULL OR e.Income_Limit >= 120000.00) -- Citizen Income: 120,000
  AND (e.Category IS NULL OR e.Category = 'General')          -- Citizen Category: General
  AND (e.Gender IS NULL OR e.Gender = 'Male')                 -- Citizen Gender: Male
  AND (e.Occupation IS NULL OR e.Occupation = 'Student')      -- Citizen Occupation: Student
  AND (e.Education IS NULL OR e.Education = '12th Pass')      -- Citizen Education: 12th Pass
  AND (e.State IS NULL OR e.State = 'Maharashtra')            -- Citizen State: Maharashtra
  AND (e.District IS NULL OR e.District = 'Pune');            -- Citizen District: Pune


-- ==============================================================================
-- 6. MULTI-TABLE ACID TRANSACTION (Application Sanctioning)
-- Atomic execution: Application Update + Approval Log + DBT Benefit Provision
-- ==============================================================================
BEGIN TRANSACTION;

-- Step 1: Update Application Status
UPDATE Application 
SET Status = 'Approved', 
    Remarks = 'All eligibility parameters and income certificates verified by Department.' 
WHERE Application_ID = 10 AND Status = 'Pending';

-- Step 2: Insert Formal Approval Order
INSERT INTO Approval (Application_ID, Officer_ID, Approval_Date, Decision, Remarks)
VALUES (10, 2, DATETIME('now'), 'Approved', 'Socio-economic credentials verified. Merit grant sanctioned.');

-- Step 3: Insert DBT Benefit Ledger Record
INSERT INTO Benefits (Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference)
VALUES (
    last_insert_rowid(), 
    25000.00, 
    DATE('now'), 
    'Pending', 
    'TXN-DBT-' || STRFTIME('%Y', 'now') || '-' || ABS(RANDOM() % 90000 + 10000)
);

COMMIT;
-- In case of failure: ROLLBACK;


-- ==============================================================================
-- 7. THE 8 ACADEMIC SQL ANALYTICAL REPORTS
-- ==============================================================================

-- Report 1: Applications by Scheme
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

-- Report 2: Applications by Status
SELECT 
    Status,
    COUNT(Application_ID) AS Count,
    ROUND(COUNT(Application_ID) * 100.0 / (SELECT COUNT(*) FROM Application), 1) AS Percentage
FROM Application
GROUP BY Status;

-- Report 3: Approved Applications by Department
SELECT 
    d.Department_ID,
    d.Department_Name,
    COUNT(a.Application_ID) AS Approved_Count
FROM Department d
INNER JOIN Scheme s ON d.Department_ID = s.Department_ID
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID AND a.Status = 'Approved'
GROUP BY d.Department_ID, d.Department_Name
ORDER BY Approved_Count DESC;

-- Report 4: Benefits Distributed by Scheme
SELECT 
    s.Scheme_ID,
    s.Scheme_Name,
    d.Department_Name,
    COUNT(b.Benefit_ID) AS Beneficiary_Count,
    COALESCE(SUM(b.Benefit_Amount), 0.0) AS Total_Amount,
    COALESCE(ROUND(AVG(b.Benefit_Amount), 2), 0.0) AS Average_Amount
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
ORDER BY Total_Amount DESC;

-- Report 5: Benefits Distributed by Month
SELECT 
    STRFTIME('%Y-%m', Benefit_Date) AS Month_Year,
    COUNT(Benefit_ID) AS Total_Disbursements,
    ROUND(SUM(Benefit_Amount), 2) AS Monthly_Total
FROM Benefits
WHERE Payment_Status IN ('Paid', 'Processing')
GROUP BY Month_Year
ORDER BY Month_Year ASC;

-- Report 6: Citizen Category Distribution
SELECT 
    Category,
    COUNT(Citizen_ID) AS Citizen_Count,
    ROUND(COUNT(Citizen_ID) * 100.0 / (SELECT COUNT(*) FROM Citizen), 1) AS Percentage
FROM Citizen
GROUP BY Category
ORDER BY Citizen_Count DESC;

-- Report 7: Scheme Utilization (Applications vs Approved Ratio)
SELECT 
    s.Scheme_ID,
    s.Scheme_Name,
    COUNT(a.Application_ID) AS Total_Applied,
    SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) AS Total_Approved,
    CASE 
        WHEN COUNT(a.Application_ID) = 0 THEN 0.0
        ELSE ROUND(SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) * 100.0 / COUNT(a.Application_ID), 1)
    END AS Approval_Rate_Pct
FROM Scheme s
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
GROUP BY s.Scheme_ID, s.Scheme_Name
ORDER BY Total_Applied DESC;

-- Report 8: Pending Applications Queue
SELECT 
    a.Application_ID,
    c.Name AS Citizen_Name,
    c.Category,
    s.Scheme_Name,
    d.Department_Name,
    a.Application_Date,
    ROUND(JULIANDAY('now') - JULIANDAY(a.Application_Date), 1) AS Days_Pending
FROM Application a
INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
INNER JOIN Department d ON s.Department_ID = d.Department_ID
WHERE a.Status = 'Pending'
ORDER BY a.Application_Date ASC;
