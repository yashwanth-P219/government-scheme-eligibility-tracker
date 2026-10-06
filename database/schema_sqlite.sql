-- ==============================================================================
-- DATABASE MANAGEMENT SYSTEM (DBMS) COURSE-BASED PROJECT
-- Project Title: Smart Government Scheme Eligibility and Benefit Tracker
-- Academic Context: B.Tech. II Year I Semester – AIML & B Section
-- Relational Database DDL Schema (Standard SQL / SQLite Compatible)
-- Pure SQL execution creating 8 normalized tables, keys, constraints, & indexes
-- ==============================================================================

PRAGMA foreign_keys = ON;

-- 1. DROP EXISTING TABLES (Reverse Dependency Order)
DROP TABLE IF EXISTS Benefits;
DROP TABLE IF EXISTS Approval;
DROP TABLE IF EXISTS Application;
DROP TABLE IF EXISTS Eligibility_Criteria;
DROP TABLE IF EXISTS Scheme;
DROP TABLE IF EXISTS Officer;
DROP TABLE IF EXISTS Department;
DROP TABLE IF EXISTS Citizen;

-- ==============================================================================
-- 2. TABLE: Citizen (3NF Normalized)
-- Stores demographic, socioeconomic, and educational attributes.
-- ==============================================================================
CREATE TABLE Citizen (
    Citizen_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL,
    Email TEXT NOT NULL UNIQUE,
    Password_Hash TEXT NOT NULL,
    Age INTEGER NULL CHECK (Age IS NULL OR (Age >= 0 AND Age <= 125)),
    Gender TEXT NULL,
    Category TEXT NULL,
    Income REAL NULL CHECK (Income IS NULL OR Income >= 0),
    Occupation TEXT NULL,
    Education TEXT NULL,
    Address TEXT NULL,
    District TEXT NULL,
    State TEXT NULL,
    Phone TEXT NULL,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================================================
-- 3. TABLE: Department
-- Government ministries and sponsoring authorities.
-- ==============================================================================
CREATE TABLE Department (
    Department_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Department_Name TEXT NOT NULL UNIQUE,
    Description TEXT NULL
);

-- ==============================================================================
-- 4. TABLE: Officer
-- Department authorities who review applications and sanction benefits.
-- ==============================================================================
CREATE TABLE Officer (
    Officer_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Department_ID INTEGER NOT NULL REFERENCES Department(Department_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Name TEXT NOT NULL,
    Email TEXT NOT NULL UNIQUE,
    Password_Hash TEXT NOT NULL,
    Designation TEXT NOT NULL,
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================================================
-- 5. TABLE: Scheme
-- Official welfare programs launched by departments.
-- ==============================================================================
CREATE TABLE Scheme (
    Scheme_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Department_ID INTEGER NOT NULL REFERENCES Department(Department_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Scheme_Name TEXT NOT NULL,
    Description TEXT NOT NULL,
    Benefit_Type TEXT NOT NULL,
    Benefit_Description TEXT NOT NULL,
    Last_Date DATE NULL,
    Status TEXT DEFAULT 'Active' CHECK (Status IN ('Active', 'Inactive')),
    Created_At DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================================================
-- 6. TABLE: Eligibility_Criteria
-- Defines qualifying requirements for each welfare scheme.
-- NULL values signify condition is not applicable (universal match).
-- ==============================================================================
CREATE TABLE Eligibility_Criteria (
    Criteria_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Scheme_ID INTEGER NOT NULL REFERENCES Scheme(Scheme_ID) ON UPDATE CASCADE ON DELETE CASCADE,
    Min_Age INTEGER NULL,
    Max_Age INTEGER NULL,
    Income_Limit REAL NULL CHECK (Income_Limit IS NULL OR Income_Limit >= 0),
    Category TEXT NULL,
    Gender TEXT NULL,
    Occupation TEXT NULL,
    Education TEXT NULL,
    State TEXT NULL,
    District TEXT NULL,
    CHECK ((Min_Age IS NULL OR Max_Age IS NULL) OR (Max_Age >= Min_Age))
);

-- ==============================================================================
-- 7. TABLE: Application
-- Citizen enrollment submissions with duplicate prevention constraint.
-- ==============================================================================
CREATE TABLE Application (
    Application_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Citizen_ID INTEGER NOT NULL REFERENCES Citizen(Citizen_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Scheme_ID INTEGER NOT NULL REFERENCES Scheme(Scheme_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Application_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status TEXT DEFAULT 'Pending' CHECK (Status IN ('Pending', 'Under Review', 'Approved', 'Rejected')),
    Remarks TEXT NULL,
    UNIQUE (Citizen_ID, Scheme_ID)
);

-- ==============================================================================
-- 8. TABLE: Approval
-- Formal adjudication record rendered by a designated officer.
-- Exactly 1:1 relationship with Application (Application_ID is UNIQUE).
-- ==============================================================================
CREATE TABLE Approval (
    Approval_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Application_ID INTEGER NOT NULL UNIQUE REFERENCES Application(Application_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Officer_ID INTEGER NOT NULL REFERENCES Officer(Officer_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Approval_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    Decision TEXT NOT NULL CHECK (Decision IN ('Approved', 'Rejected')),
    Remarks TEXT NULL
);

-- ==============================================================================
-- 9. TABLE: Benefits
-- Financial Direct Benefit Transfer (DBT) grants linked to an approval.
-- ==============================================================================
CREATE TABLE Benefits (
    Benefit_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Approval_ID INTEGER NOT NULL REFERENCES Approval(Approval_ID) ON UPDATE CASCADE ON DELETE CASCADE,
    Benefit_Amount REAL NOT NULL CHECK (Benefit_Amount >= 0),
    Benefit_Date DATE NOT NULL,
    Payment_Status TEXT DEFAULT 'Pending' CHECK (Payment_Status IN ('Pending', 'Processing', 'Paid', 'Failed')),
    Transaction_Reference TEXT UNIQUE NULL
);

-- ==============================================================================
-- 10. INDEXES FOR QUERY OPTIMIZATION
-- ==============================================================================
CREATE INDEX idx_citizen_loc ON Citizen(State, District);
CREATE INDEX idx_citizen_cat ON Citizen(Category);
CREATE INDEX idx_scheme_dept ON Scheme(Department_ID, Status);
CREATE INDEX idx_crit_scheme ON Eligibility_Criteria(Scheme_ID);
CREATE INDEX idx_app_cit ON Application(Citizen_ID, Status);
CREATE INDEX idx_app_sch ON Application(Scheme_ID, Status);
CREATE INDEX idx_appr_off ON Approval(Officer_ID);
CREATE INDEX idx_ben_stat ON Benefits(Payment_Status, Benefit_Date);
