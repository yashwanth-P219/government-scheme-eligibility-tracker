/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/db-init.js
 */

(function () {
    'use strict';

    // 1. EMBEDDED DDL SCHEMA SCRIPT
    window.GOV_DB_SCHEMA = `
PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS Benefits;
DROP TABLE IF EXISTS Approval;
DROP TABLE IF EXISTS Application;
DROP TABLE IF EXISTS Eligibility_Criteria;
DROP TABLE IF EXISTS Scheme;
DROP TABLE IF EXISTS Officer;
DROP TABLE IF EXISTS Department;
DROP TABLE IF EXISTS Citizen;

CREATE TABLE Citizen (
    Citizen_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL,
    Email TEXT UNIQUE NOT NULL,
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
    Created_At TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Department (
    Department_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Department_Name TEXT NOT NULL UNIQUE,
    Description TEXT NULL
);

CREATE TABLE Officer (
    Officer_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Department_ID INTEGER NOT NULL REFERENCES Department(Department_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Name TEXT NOT NULL,
    Email TEXT UNIQUE NOT NULL,
    Password_Hash TEXT NOT NULL,
    Designation TEXT NOT NULL,
    Created_At TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Scheme (
    Scheme_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Department_ID INTEGER NOT NULL REFERENCES Department(Department_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Scheme_Name TEXT NOT NULL,
    Description TEXT NOT NULL,
    Benefit_Type TEXT NOT NULL,
    Benefit_Description TEXT NOT NULL,
    Last_Date TEXT NULL,
    Status TEXT DEFAULT 'Active' CHECK (Status IN ('Active', 'Inactive')),
    Created_At TEXT DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE Application (
    Application_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Citizen_ID INTEGER NOT NULL REFERENCES Citizen(Citizen_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Scheme_ID INTEGER NOT NULL REFERENCES Scheme(Scheme_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Application_Date TEXT DEFAULT CURRENT_TIMESTAMP,
    Status TEXT DEFAULT 'Pending' CHECK (Status IN ('Pending', 'Under Review', 'Approved', 'Rejected')),
    Remarks TEXT NULL,
    UNIQUE (Citizen_ID, Scheme_ID)
);

CREATE TABLE Approval (
    Approval_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Application_ID INTEGER NOT NULL UNIQUE REFERENCES Application(Application_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Officer_ID INTEGER NOT NULL REFERENCES Officer(Officer_ID) ON UPDATE CASCADE ON DELETE RESTRICT,
    Approval_Date TEXT DEFAULT CURRENT_TIMESTAMP,
    Decision TEXT NOT NULL CHECK (Decision IN ('Approved', 'Rejected')),
    Remarks TEXT NULL
);

CREATE TABLE Benefits (
    Benefit_ID INTEGER PRIMARY KEY AUTOINCREMENT,
    Approval_ID INTEGER NOT NULL UNIQUE REFERENCES Approval(Approval_ID) ON UPDATE CASCADE ON DELETE CASCADE,
    Benefit_Amount REAL NOT NULL CHECK (Benefit_Amount >= 0),
    Benefit_Date TEXT NOT NULL,
    Payment_Status TEXT DEFAULT 'Pending' CHECK (Payment_Status IN ('Pending', 'Processing', 'Paid', 'Failed')),
    Transaction_Reference TEXT UNIQUE NULL
);

CREATE INDEX IF NOT EXISTS idx_citizen_loc ON Citizen(State, District);
CREATE INDEX IF NOT EXISTS idx_citizen_cat ON Citizen(Category);
CREATE INDEX IF NOT EXISTS idx_scheme_dept ON Scheme(Department_ID, Status);
CREATE INDEX IF NOT EXISTS idx_crit_scheme ON Eligibility_Criteria(Scheme_ID);
CREATE INDEX IF NOT EXISTS idx_app_cit ON Application(Citizen_ID, Status);
CREATE INDEX IF NOT EXISTS idx_app_sch ON Application(Scheme_ID, Status);
CREATE INDEX IF NOT EXISTS idx_appr_off ON Approval(Officer_ID);
CREATE INDEX IF NOT EXISTS idx_ben_stat ON Benefits(Payment_Status, Benefit_Date);
`;

    // 2. EMBEDDED DML SEED SCRIPT
    window.GOV_DB_SEED = `
INSERT INTO Department (Department_ID, Department_Name, Description) VALUES
(1, 'Department of Agriculture & Farmers Welfare', 'Formulates agrarian policies, crop support, agricultural equipment subsidies, and income support.'),
(2, 'Department of Higher Education & Skill Development', 'Promotes post-secondary academic access, technical certifications, merit scholarships, and youth employability.'),
(3, 'Department of Social Justice & Empowerment', 'Administers social safety nets, pensions for senior citizens, and inclusive welfare for marginalized communities.');

INSERT INTO Officer (Officer_ID, Department_ID, Name, Email, Password_Hash, Designation, Created_At) VALUES
(1, 1, 'Dr. Anand Verma', 'officer.agri@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Joint Director (Agronomy)', '2026-01-10 09:30:00'),
(2, 2, 'Meenakshi Sundaram', 'officer.edu@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Deputy Secretary (Scholarships)', '2026-01-10 10:00:00'),
(3, 3, 'Rajeshwari Nair', 'officer.welfare@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Senior Welfare Commissioner', '2026-01-12 11:15:00'),
(4, 3, 'Vikramaditya Singh', 'officer.social@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Assistant Director (Social Defense)', '2026-01-15 14:00:00'),
(5, 2, 'Sunita Deshmukh', 'officer.skills@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Project Director (Skill Missions)', '2026-01-18 16:30:00');

INSERT INTO Citizen (Citizen_ID, Name, Email, Password_Hash, Age, Gender, Category, Income, Occupation, Education, Address, District, State, Phone, Created_At) VALUES
(1, 'Rahul Sharma', 'rahul.sharma@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 22, 'Male', 'General', 120000.00, 'Student', '12th Pass', 'Flat 402, Shivajinagar', 'Pune', 'Maharashtra', '9823011223', '2026-02-01 10:15:00'),
(2, 'Priya Patel', 'priya.patel@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 28, 'Female', 'OBC', 280000.00, 'Self-Employed', 'Graduate', '12 Navrangpura Society', 'Ahmedabad', 'Gujarat', '9824022334', '2026-02-02 11:30:00'),
(3, 'Ramesh Kumar', 'ramesh.kumar@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 46, 'Male', 'OBC', 180000.00, 'Farmer', '10th Pass', 'Village Chiraigaon, Post Kashi', 'Varanasi', 'Uttar Pradesh', '9839033445', '2026-02-03 09:45:00'),
(4, 'Sunita Devi', 'sunita.devi@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 64, 'Female', 'SC', 85000.00, 'Retired', 'Below 10th', 'H.No 108, Kankarbagh', 'Patna', 'Bihar', '9835044556', '2026-02-04 14:20:00'),
(5, 'Amit Verma', 'amit.verma@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 23, 'Male', 'EWS', 140000.00, 'Student', '12th Pass', 'Block C, Arera Colony', 'Bhopal', 'Madhya Pradesh', '9826055667', '2026-02-05 16:00:00'),
(6, 'Lakshmi Bai', 'lakshmi.bai@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 35, 'Female', 'ST', 95000.00, 'Artisan', 'Below 10th', 'Gram Bagru, Sanganer', 'Jaipur', 'Rajasthan', '9829066778', '2026-02-06 12:10:00'),
(7, 'Suresh Reddy', 'suresh.reddy@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 53, 'Male', 'General', 220000.00, 'Farmer', '12th Pass', 'Main Road, Tenali', 'Guntur', 'Andhra Pradesh', '9848077889', '2026-02-07 10:05:00'),
(8, 'Ananya Sen', 'ananya.sen@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 21, 'Female', 'General', 310000.00, 'Student', 'Graduate', 'Salt Lake Sector 1', 'Kolkata', 'West Bengal', '9830088990', '2026-02-08 15:40:00'),
(9, 'Mohammed Irfan', 'irfan.m@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 26, 'Male', 'OBC', 160000.00, 'Unemployed', '10th Pass', 'Old City, Charminar', 'Hyderabad', 'Telangana', '9849099001', '2026-02-09 13:25:00'),
(10, 'Deepa Joshi', 'deepa.joshi@example.com', '42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443', 43, 'Female', 'General', 250000.00, 'Self-Employed', 'Post Graduate', '8th Cross, Malleshwaram', 'Bengaluru', 'Karnataka', '9880100112', '2026-02-10 11:50:00');

INSERT INTO Scheme (Scheme_ID, Department_ID, Scheme_Name, Description, Benefit_Type, Benefit_Description, Last_Date, Status, Created_At) VALUES
(1, 2, 'Student Scholarship Assistance Scheme', 'Financial assistance and fee support aimed at meritorious low-income students pursuing higher education.', 'Monetary', 'Rs. 25,000 per academic year credited directly to student bank accounts.', '2027-03-31', 'Active', '2026-01-20 09:00:00'),
(2, 3, 'Women Entrepreneurship Support Scheme', 'Provides capital subsidy and seed funding for women establishing micro and cottage enterprises.', 'Monetary', '35% capital subsidy up to Rs. 1,50,000 on approved enterprise term loans.', '2027-06-30', 'Active', '2026-01-20 09:30:00'),
(3, 1, 'Farmer Direct Income Support Scheme', 'Guaranteed direct cash benefit transfer to smallholder agricultural families for agrarian sustenance.', 'Monetary', 'Rs. 10,000 per annum paid in two equal installments directly via DBT.', '2027-12-31', 'Active', '2026-01-22 10:00:00'),
(4, 3, 'Senior Citizen Welfare Scheme', 'Direct monthly old-age welfare pension and subsidized geriatric healthcare assistance for senior citizens.', 'Monetary', 'Monthly pension of Rs. 3,000 credited directly to verified bank accounts.', '2027-12-31', 'Active', '2026-01-25 11:00:00'),
(5, 2, 'Youth Skill Development Scheme', 'Market-aligned vocational skill training and apprenticeship placement support for unemployed youth.', 'Skill Training', 'Free 6-month NSQF technical certification plus Rs. 6,000 monthly apprenticeship stipend.', '2026-11-30', 'Active', '2026-01-28 14:00:00'),
(6, 2, 'Economically Weaker Student Support Scheme', 'One-time educational grant and textbook allowance for students from economically weaker sections.', 'Monetary', 'One-time scholarship grant of Rs. 40,000 for college tuition and study hardware.', '2026-10-31', 'Active', '2026-02-01 10:30:00'),
(7, 3, 'Rural Employment Assistance Scheme', 'Guaranteed wage employment and rural community livelihood assistance for vulnerable village households.', 'Monetary', 'Direct wage credit of Rs. 350 per day for 100 days of guaranteed rural community work.', '2027-05-31', 'Active', '2026-02-05 12:00:00'),
(8, 1, 'Small Farmer Equipment Support Scheme', 'Subsidized purchase of modern agricultural machinery, power tillers, and drip irrigation units.', 'Subsidy', '50% equipment subsidy up to Rs. 50,000 on approved agricultural tools and implements.', '2026-12-15', 'Active', '2026-02-08 15:00:00');

INSERT INTO Eligibility_Criteria (Criteria_ID, Scheme_ID, Min_Age, Max_Age, Income_Limit, Category, Gender, Occupation, Education, State, District) VALUES
(1, 1, 17, 28, 250000.00, NULL, NULL, 'Student', '12th Pass', NULL, NULL),
(2, 2, 21, 55, 500000.00, NULL, 'Female', NULL, NULL, NULL, NULL),
(3, 3, 18, 75, 300000.00, NULL, NULL, 'Farmer', NULL, NULL, NULL),
(4, 4, 60, 120, 200000.00, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 5, 18, 30, 350000.00, NULL, NULL, NULL, '10th Pass', NULL, NULL),
(6, 6, 17, 25, 180000.00, 'EWS', NULL, 'Student', '12th Pass', NULL, NULL),
(7, 7, 18, 65, 150000.00, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 8, 18, 70, 250000.00, NULL, NULL, 'Farmer', NULL, NULL, NULL);

INSERT INTO Application (Application_ID, Citizen_ID, Scheme_ID, Application_Date, Status, Remarks) VALUES
(1, 1, 1, '2026-02-10 11:00:00', 'Approved', 'Valid 12th marksheet, bona fide student certificate and income certificate verified.'),
(2, 2, 2, '2026-02-11 14:30:00', 'Approved', 'Udyam registration and viable micro-enterprise project proposal vetted.'),
(3, 3, 3, '2026-02-12 10:15:00', 'Approved', 'Land records (Khasra/Khatauni) and bank account authenticated.'),
(4, 4, 4, '2026-02-13 16:45:00', 'Approved', 'Aadhaar age proof (64 years) and income certificate verified.'),
(5, 5, 6, '2026-02-14 09:30:00', 'Approved', 'EWS certificate and university admission letter verified.'),
(6, 6, 7, '2026-02-15 12:00:00', 'Approved', 'Rural job card and local artisan documentation verified.'),
(7, 7, 8, '2026-02-16 15:20:00', 'Approved', 'Small farmer certificate and quotation for power tiller validated.'),
(8, 1, 5, '2026-02-18 11:30:00', 'Approved', 'Youth skill apprenticeship application cleared.'),
(9, 9, 5, '2026-02-20 13:45:00', 'Under Review', 'Demographic parameters satisfied; awaiting candidate trade selection counseling.'),
(10, 8, 1, '2026-02-22 10:00:00', 'Pending', 'Initial application submitted; income certificate pending verification.'),
(11, 10, 2, '2026-02-24 16:00:00', 'Pending', 'Application submitted; business plan under initial scrutiny by district office.'),
(12, 3, 8, '2026-02-25 14:10:00', 'Rejected', 'Application exceeds equipment subsidy ceiling for current financial cycle.');

INSERT INTO Approval (Approval_ID, Application_ID, Officer_ID, Approval_Date, Decision, Remarks) VALUES
(1, 1, 2, '2026-02-12 11:30:00', 'Approved', 'All academic and low-income criteria satisfied. Merit scholarship sanctioned.'),
(2, 2, 3, '2026-02-14 15:00:00', 'Approved', 'Eligible woman entrepreneur. Capital subsidy sanctioned for handloom manufacturing.'),
(3, 3, 1, '2026-02-15 10:45:00', 'Approved', 'Smallholder farmer credentials validated. Direct income support cleared.'),
(4, 4, 4, '2026-02-16 17:00:00', 'Approved', 'Senior citizen age (64) validated. Monthly social security pension approved.'),
(5, 5, 2, '2026-02-18 10:00:00', 'Approved', 'EWS category criteria satisfied. Higher education grant approved.'),
(6, 6, 3, '2026-02-19 14:30:00', 'Approved', 'Rural employment livelihood guarantee approved for artisanal worker.'),
(7, 7, 1, '2026-02-20 16:00:00', 'Approved', 'Agrarian machinery equipment subsidy sanctioned.'),
(8, 12, 1, '2026-02-26 15:30:00', 'Rejected', 'Exceeds equipment subsidy ceiling for the current financial cycle.');

INSERT INTO Benefits (Benefit_ID, Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference) VALUES
(1, 1, 25000.00, '2026-02-13', 'Paid', 'TXN-DBT-2026-90211'),
(2, 2, 150000.00, '2026-02-16', 'Paid', 'TXN-DBT-2026-90212'),
(3, 3, 10000.00, '2026-02-17', 'Paid', 'TXN-DBT-2026-90213'),
(4, 4, 3000.00, '2026-02-18', 'Paid', 'TXN-DBT-2026-90214'),
(5, 5, 40000.00, '2026-02-20', 'Processing', 'TXN-DBT-2026-90215'),
(6, 6, 35000.00, '2026-02-22', 'Pending', 'TXN-DBT-2026-90216'),
(7, 7, 50000.00, '2026-02-24', 'Processing', 'TXN-DBT-2026-90217');
`;

    // 3. INJECT UNIVERSAL DATABASE STATUS & BACKUP TOOLBAR (Hidden by default for real-time production look)
    function injectDatabaseToolbar() {
        if (document.getElementById('gov-db-toolbar')) return;

        // Only display if explicitly requested via ?dev=true or on the dedicated db-studio page
        const showDev = window.location.search.includes('dev=true') || window.location.pathname.includes('db-studio.html');
        if (!showDev) return;

        const isSub = window.location.pathname.includes('/citizen/') || window.location.pathname.includes('/officer/');
        const studioHref = isSub ? '../db-studio.html' : 'db-studio.html';

        const bar = document.createElement('div');
        bar.id = 'gov-db-toolbar';
        bar.style.cssText = `
            background: #0f2744;
            color: #ffffff;
            font-size: 0.78rem;
            padding: 6px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #ff9933;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            font-family: inherit;
            position: sticky;
            top: 0;
            z-index: 10000;
            flex-wrap: wrap;
            gap: 8px;
        `;

        bar.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: #28a745; box-shadow: 0 0 6px #28a745;"></span>
                <strong>System Engine:</strong>
                <span style="background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 4px; font-family: monospace;">SQLite WASM + IndexedDB</span>
                <span id="gov-db-stats" style="color: #cbd5e1;">(Initializing...)</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <a id="gov-btn-studio-db" href="${studioHref}" style="background: #4338ca; color: white; border: 1px solid #6366f1; padding: 3px 10px; border-radius: 4px; text-decoration: none; font-size: 0.76rem; display: flex; align-items: center; gap: 4px;">
                    ⚡ Database Studio & SQL
                </a>
                <button id="gov-btn-export-db" style="background: #1e3a8a; color: white; border: 1px solid #3b82f6; padding: 3px 10px; border-radius: 4px; cursor: pointer; font-size: 0.76rem; display: flex; align-items: center; gap: 4px;">
                    💾 Export .sqlite
                </button>
                <label for="gov-file-import-db" style="background: #065f46; color: white; border: 1px solid #10b981; padding: 3px 10px; border-radius: 4px; cursor: pointer; font-size: 0.76rem; display: flex; align-items: center; gap: 4px; margin: 0;">
                    📂 Import .sqlite
                </label>
                <input type="file" id="gov-file-import-db" accept=".sqlite,.db" style="display: none;" />
                <button id="gov-btn-reset-db" style="background: #991b1b; color: white; border: 1px solid #ef4444; padding: 3px 10px; border-radius: 4px; cursor: pointer; font-size: 0.76rem; display: flex; align-items: center; gap: 4px;">
                    🔄 Reset Demo Data
                </button>
            </div>
        `;

        document.body.prepend(bar);

        // Bind Export
        document.getElementById('gov-btn-export-db').addEventListener('click', () => {
            try {
                GovDB.exportDatabase();
                if (typeof showToast === 'function') {
                    showToast('Exported government_scheme_tracker.sqlite successfully!', 'success');
                }
            } catch (e) {
                alert('Export failed: ' + e.message);
            }
        });

        // Bind Import
        document.getElementById('gov-file-import-db').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            if (!confirm(`Are you sure you want to restore the database from '${file.name}'? Existing local changes will be replaced.`)) {
                e.target.value = '';
                return;
            }
            try {
                await GovDB.importDatabase(file);
                alert('Database restored successfully! The page will now reload.');
                window.location.reload();
            } catch (err) {
                alert('Database import failed: ' + err.message);
                e.target.value = '';
            }
        });

        // Bind Reset
        document.getElementById('gov-btn-reset-db').addEventListener('click', async () => {
            if (!confirm('This will wipe all local changes and reset the database to the 8 demo tables and seed records. Proceed?')) {
                return;
            }
            try {
                await GovDB.resetDemoData();
                alert('Database has been reset to original demo state! Reloading...');
                window.location.reload();
            } catch (e) {
                alert('Reset failed: ' + e.message);
            }
        });

        // Update stats badge
        updateStats();
    }

    function updateStats() {
        if (!GovDB.isReady()) return;
        try {
            const schemesCount = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Scheme;");
            const appsCount = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application;");
            const citizensCount = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Citizen;");
            const statsSpan = document.getElementById('gov-db-stats');
            if (statsSpan && schemesCount && appsCount && citizensCount) {
                statsSpan.textContent = `| 8 Tables | ${schemesCount.c} Schemes | ${appsCount.c} Applications | ${citizensCount.c} Citizens`;
            }
        } catch (e) {}
    }

    // Auto-init on load
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            await GovDB.initializeDatabase();
            injectDatabaseToolbar();
            updateStats();
        } catch (err) {
            console.error('[DB Init] Initialization error:', err);
        }
    });

    window.addEventListener('gov-db-ready', () => {
        injectDatabaseToolbar();
        updateStats();
    });

})();
