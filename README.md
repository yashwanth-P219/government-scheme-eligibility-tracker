# Smart Government Scheme Eligibility and Benefit Tracker

**A Complete Academic Database Management System (DBMS) Project**  
*Academic Context:* B.Tech. II Year I Semester – AIML and B Section  
*Subject:* Database Management System (DBMS)  
*Academic Year:* 2026-27  
*Architecture:* **100% Client-Side SQLite running via WebAssembly (`sql.js`) with IndexedDB Persistence**  
*Backend:* **ZERO Backend Required (No PHP, No Node.js, No Express, No MySQL, No XAMPP, No Firebase)**

---

## 1. Project Overview & Problem Statement

### 1.1 The Challenge
Modern state and national governments administer numerous welfare initiatives tailored for students, smallholder farmers, women micro-entrepreneurs, senior citizens, unemployed youth, and economically weaker sections (EWS). However, significant information asymmetry and administrative friction exist:
- Qualified citizens miss out on life-changing welfare programs because they are unaware of which schemes they are eligible for.
- Citizens face fragmented departmental portals and manual qualification rules.
- Welfare officers struggle with manual auditing of applicant demographics, lack unified tools to sanction benefits atomically via ACID transactions, and cannot easily track turnaround times.

### 1.2 The Proposed Solution
The **Smart Government Scheme Eligibility and Benefit Tracker** is a client-side, relational-database-driven web application built entirely using **HTML5, CSS3, Vanilla JavaScript (ES6+), and SQLite compiled to WebAssembly (`sql.js`)**.
1. **Dynamic SQL Eligibility Evaluation Engine**: Evaluates citizen attributes (Age, Income, Social Category, Gender, Occupation, Qualification, State, District) directly against relational rules in the `Eligibility_Criteria` table. Conditions where a column is `NULL` in the database are dynamically interpreted as universal (not restricted).
2. **Multi-Table ACID Transaction Approval**: Employs SQLite transactions (`BEGIN TRANSACTION; ... COMMIT; / ROLLBACK;`) during application sanctioning to atomically update application status, log formal officer adjudication, and provision Direct Benefit Transfer (DBT) financial disbursement records.
3. **8 Academic SQL Analytics Reports**: Computes real-time analytical reports using native SQL aggregations (`COUNT()`, `SUM()`, `AVG()`, `MIN()`, `MAX()`, `GROUP BY`, `HAVING`, `JOIN`, and Subqueries).

---

## 2. Technology Stack & Framework Restriction Adherence

Per strict academic project restrictions, **zero third-party web frameworks, ORMs, or backend runtime environments** are used.

| Component | Technology | Implementation & Role |
| :--- | :--- | :--- |
| **Frontend UI** | **HTML5 (Semantic)** | Accessible forms, dashboards, tables, and modal dialogs |
| **Styling** | **Custom CSS3** | Custom design system (Indian National Portal palette: Navy `#1e3a8a`, Saffron `#ff9933`, Green `#10b981`), zero Bootstrap/Tailwind |
| **Client Scripting**| **Vanilla JavaScript (ES6+)** | State management, DOM manipulation, custom SVG/Canvas charts, zero frameworks |
| **Relational Database** | **SQLite WebAssembly (`sql.js`)** | In-browser relational engine executing raw SQL DDL, DML, and transactions |
| **Persistence** | **IndexedDB API** | Automatically persists SQLite binary (`.sqlite`) between browser sessions |
| **Security & Auth** | **Web Crypto API (SHA-256 + Salt)** | Cryptographic password hashing and `sessionStorage` demonstration login |
| **Static Serving** | **VS Code Live Server or Python Static Server** | Serves static assets (`.html`, `.js`, `.css`, `.wasm`) with zero backend code |

---

## 3. Browser-Only Architecture & Relational Data Flow

```text
       CITIZEN / WELFARE OFFICER
                    │
                    ▼
     Vanilla HTML5 / CSS3 / JavaScript (ES6+)
                    │
         [Client Database Service]
          (js/database.js & js/db-init.js)
                    │
                    ▼
       SQLite WebAssembly (sql.js)
  ├── 8 Normalized 3NF Tables
  ├── Foreign Key Constraints & Check Constraints
  ├── Dynamic SQL Eligibility Evaluation Engine
  ├── ACID Transaction Manager (BEGIN / COMMIT / ROLLBACK)
  └── 8 SQL Analytical Reports & Aggregations
                    │
          [Binary Uint8Array Export]
                    │
                    ▼
         IndexedDB Local Storage
   (government_scheme_tracker.sqlite)
                    ▲
                    │
       [Export / Import .sqlite File]
```

---

## 4. Database Design & Third Normal Form (3NF) Schema

The relational database `government_scheme_tracker.sqlite` contains 8 normalized tables designed in Third Normal Form (3NF) with `PRAGMA foreign_keys = ON;`.

### 4.1 Table: `Citizen`
Stores citizen demographic, socioeconomic, and educational attributes.
- `Citizen_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Name` (TEXT NOT NULL)
- `Email` (TEXT UNIQUE NOT NULL)
- `Password_Hash` (TEXT NOT NULL) — Hashed with Web Crypto API
- `Age` (INTEGER NULL CHECK (Age IS NULL OR (Age >= 0 AND Age <= 125)))
- `Gender` (TEXT NULL)
- `Category` (TEXT NULL) — General, OBC, SC, ST, EWS
- `Income` (REAL NULL CHECK (Income IS NULL OR Income >= 0))
- `Occupation` (TEXT NULL) — Student, Farmer, Artisan, Self-Employed, etc.
- `Education` (TEXT NULL) — Below 10th, 10th Pass, 12th Pass, Graduate, Post Graduate
- `Address` (TEXT NULL)
- `District` (TEXT NULL)
- `State` (TEXT NULL)
- `Phone` (TEXT NULL)
- `Created_At` (TEXT DEFAULT CURRENT_TIMESTAMP)

### 4.2 Table: `Department`
Represents ministries and government departments.
- `Department_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Department_Name` (TEXT NOT NULL UNIQUE)
- `Description` (TEXT NULL)

### 4.3 Table: `Officer`
Government departmental authorities who review applications and sanction benefits.
- `Officer_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Department_ID` (INTEGER NOT NULL REFERENCES Department(Department_ID))
- `Name` (TEXT NOT NULL)
- `Email` (TEXT UNIQUE NOT NULL)
- `Password_Hash` (TEXT NOT NULL)
- `Designation` (TEXT NOT NULL)
- `Created_At` (TEXT DEFAULT CURRENT_TIMESTAMP)

### 4.4 Table: `Scheme`
Official welfare programs with benefit types and application deadlines.
- `Scheme_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Department_ID` (INTEGER NOT NULL REFERENCES Department(Department_ID))
- `Scheme_Name` (TEXT NOT NULL)
- `Description` (TEXT NOT NULL)
- `Benefit_Type` (TEXT NOT NULL) — Monetary, Subsidy, Skill Training, Pension
- `Benefit_Description` (TEXT NOT NULL)
- `Last_Date` (TEXT NULL)
- `Status` (TEXT DEFAULT 'Active' CHECK (Status IN ('Active', 'Inactive')))
- `Created_At` (TEXT DEFAULT CURRENT_TIMESTAMP)

### 4.5 Table: `Eligibility_Criteria`
Defines qualifying requirements for each welfare scheme. `NULL` indicates universal eligibility (no restriction).
- `Criteria_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Scheme_ID` (INTEGER NOT NULL REFERENCES Scheme(Scheme_ID) ON DELETE CASCADE)
- `Min_Age` (INTEGER NULL)
- `Max_Age` (INTEGER NULL)
- `Income_Limit` (REAL NULL CHECK (Income_Limit IS NULL OR Income_Limit >= 0))
- `Category` (TEXT NULL)
- `Gender` (TEXT NULL)
- `Occupation` (TEXT NULL)
- `Education` (TEXT NULL)
- `State` (TEXT NULL)
- `District` (TEXT NULL)

### 4.6 Table: `Application`
Citizen welfare enrollments with composite duplicate application prevention constraint.
- `Application_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Citizen_ID` (INTEGER NOT NULL REFERENCES Citizen(Citizen_ID))
- `Scheme_ID` (INTEGER NOT NULL REFERENCES Scheme(Scheme_ID))
- `Application_Date` (TEXT DEFAULT CURRENT_TIMESTAMP)
- `Status` (TEXT DEFAULT 'Pending' CHECK (Status IN ('Pending', 'Under Review', 'Approved', 'Rejected')))
- `Remarks` (TEXT NULL)
- `UNIQUE (Citizen_ID, Scheme_ID)` — **Enforces duplicate submission prevention in SQLite**

### 4.7 Table: `Approval`
Formal adjudication orders logged by welfare officers (1:1 with Application).
- `Approval_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Application_ID` (INTEGER NOT NULL UNIQUE REFERENCES Application(Application_ID))
- `Officer_ID` (INTEGER NOT NULL REFERENCES Officer(Officer_ID))
- `Approval_Date` (TEXT DEFAULT CURRENT_TIMESTAMP)
- `Decision` (TEXT NOT NULL CHECK (Decision IN ('Approved', 'Rejected')))
- `Remarks` (TEXT NULL)

### 4.8 Table: `Benefits`
Direct Benefit Transfer (DBT) financial disbursement ledger.
- `Benefit_ID` (INTEGER PRIMARY KEY AUTOINCREMENT)
- `Approval_ID` (INTEGER NOT NULL UNIQUE REFERENCES Approval(Approval_ID) ON DELETE CASCADE)
- `Benefit_Amount` (REAL NOT NULL CHECK (Benefit_Amount >= 0))
- `Benefit_Date` (TEXT NOT NULL)
- `Payment_Status` (TEXT DEFAULT 'Pending' CHECK (Payment_Status IN ('Pending', 'Processing', 'Paid', 'Failed')))
- `Transaction_Reference` (TEXT UNIQUE NULL)

---

## 5. Seed Demonstration Data

The project seeds fictional demo data idempotently into SQLite on first startup:
- **10 Citizens**: Diverse across age, gender, caste category, income brackets, and rural/urban occupations.
- **3 Departments**: Agriculture & Farmers Welfare, Higher Education & Skill Development, Social Justice & Empowerment.
- **5 Officers**: Assigned to respective departments.
- **8 Welfare Schemes**:
  1. `Student Scholarship Assistance Scheme`
  2. `Women Entrepreneurship Support Scheme`
  3. `Farmer Direct Income Support Scheme`
  4. `Senior Citizen Welfare Scheme`
  5. `Youth Skill Development Scheme`
  6. `Economically Weaker Student Support Scheme`
  7. `Rural Employment Assistance Scheme`
  8. `Small Farmer Equipment Support Scheme`
- **12 Applications**, **8 Approvals**, and **7 Benefit Transactions**.

---

## 6. Demonstration Credentials

You can test both portal roles using the credentials below (or use the 1-click **"Fill"** buttons on the login page):

| Role | Email Address | Password | Demonstration Highlights |
| :--- | :--- | :--- | :--- |
| **Citizen (Student)** | `rahul.sharma@example.com` | `Citizen@123` | • 22 yrs, General, ₹1.2L income.<br>• Dynamic SQL eligibility matches Student Scholarship & Youth Skills schemes.<br>• View application timeline & DBT benefit records. |
| **Citizen (Farmer)** | `ramesh.kumar@example.com` | `Citizen@123` | • 46 yrs, Farmer, OBC.<br>• Qualifies for Farmer Income Support & Equipment Subsidy. |
| **Citizen (Woman Entrepreneur)** | `priya.patel@example.com` | `Citizen@123` | • 28 yrs, Female, Self-Employed.<br>• Sanctioned capital subsidy of ₹1,50,000 for handloom enterprise. |
| **Officer (Agriculture)** | `officer.agri@gov.in` | `Officer@123` | • Scrutinize applications for agricultural schemes.<br>• Execute ACID Transaction Approval.<br>• Access 8 SQL Analytical Reports. |
| **Officer (Education)** | `officer.edu@gov.in` | `Officer@123` | • Manage scholarship schemes and youth vocational training. |
| **Officer (Social Justice)**| `officer.social@gov.in` | `Officer@123` | • Manage old-age pensions and rural employment schemes. |

---

## 7. How to Run the Application

The application requires **zero installations** (no PHP, no Apache, no Node.js, no npm, no MySQL).

### Method A: 1-Click Launch (Recommended)
1. Open the project folder in Windows File Explorer:
   ```text
   C:\Users\YASHWANTH\Desktop\projects\dbms\
   ```
2. Double-click the file named **`run.bat`**.
3. It launches a local static file server and automatically opens:
   ```text
   http://localhost:5000/index.html
   ```

### Method B: VS Code Live Server
1. Open the project folder in **Visual Studio Code**.
2. Right-click on `smart-government-scheme-tracker/index.html`.
3. Select **"Open with Live Server"**.

---

## 8. Database Persistence, Backup & Restore

Since there is no server-side database, SQLite runs in WebAssembly memory and is automatically synced with the browser's **IndexedDB**:
1. **Auto-Save**: Any mutation (`INSERT`, `UPDATE`, `DELETE`, or `COMMIT TRANSACTION`) automatically serializes the database binary (`Uint8Array`) into IndexedDB.
2. **Session Persistence**: Refreshing the browser or returning to the portal loads the saved database state from IndexedDB.
3. **Export `.sqlite`**: Click the **"💾 Export .sqlite"** button in the top toolbar to download the current database file to your computer.
4. **Import `.sqlite`**: Click **"📂 Import .sqlite"** in the top toolbar to restore any valid SQLite database backup.
5. **Reset Demo Data**: Click **"🔄 Reset Demo Data"** to restore the 8 tables and seed data to the initial state.

---

## 9. DBMS Concepts Demonstrated (Viva Reference)

### 9.1 Multi-Table ACID Transaction
When an officer approves an application, an atomic transaction executes in SQLite WASM:
```sql
BEGIN TRANSACTION;

-- Step 1: Update Application State
UPDATE Application 
SET Status = 'Approved', Remarks = 'Credentials verified.' 
WHERE Application_ID = 10 AND Status = 'Pending';

-- Step 2: Insert Approval Adjudication Order
INSERT INTO Approval (Application_ID, Officer_ID, Approval_Date, Decision, Remarks)
VALUES (10, 1, DATETIME('now'), 'Approved', 'All criteria satisfied.');

-- Step 3: Insert DBT Benefit Ledger Record
INSERT INTO Benefits (Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference)
VALUES (last_insert_rowid(), 25000.00, DATE('now'), 'Pending', 'TXN-DBT-2026-99120');

COMMIT;
-- In case of failure: ROLLBACK;
```

### 9.2 Dynamic SQL Eligibility Query with NULL Handling
In SQL three-valued logic, `NULL` represents an unrestricted condition:
```sql
SELECT s.Scheme_ID, s.Scheme_Name, d.Department_Name
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
WHERE s.Status = 'Active'
  AND (e.Min_Age IS NULL OR :age >= e.Min_Age)
  AND (e.Max_Age IS NULL OR :age <= e.Max_Age)
  AND (e.Income_Limit IS NULL OR :income <= e.Income_Limit)
  AND (e.Category IS NULL OR LOWER(:cat) = LOWER(e.Category))
  AND (e.Occupation IS NULL OR LOWER(:occ) = LOWER(e.Occupation));
```

### 9.3 The 8 Analytical SQL Queries
- **Report 1:** `COUNT(a.Application_ID)` GROUP BY `Scheme_ID` (Applications by Scheme).
- **Report 2:** `COUNT(*)` and Percentage GROUP BY `Status` (Status Donut breakdown).
- **Report 3:** `COUNT(a.Application_ID)` GROUP BY `Department_ID` with filter `Status = 'Approved'`.
- **Report 4:** `COUNT(b.Benefit_ID)`, `SUM(b.Benefit_Amount)`, `AVG(b.Benefit_Amount)` GROUP BY `Scheme_ID`.
- **Report 5:** `STRFTIME('%Y-%m', Benefit_Date)` with `SUM(Benefit_Amount)` (Monthly Financials).
- **Report 6:** `COUNT(Citizen_ID)` GROUP BY `Category` (Social demographic distribution).
- **Report 7:** Conditional `SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END)` / `COUNT(*)` (Utilization Rate).
- **Report 8:** `ROUND(JULIANDAY('now') - JULIANDAY(Application_Date), 1)` (Turnaround backlog queue).

---

## 10. Important Academic Limitations Notice

> [!NOTE]
> **Single-Browser Demonstration Scope**:
> This project is designed exclusively for academic demonstration in the **Database Management System (DBMS)** course.
> - Authentication and database storage are hosted inside the user's specific browser instance via WebAssembly and IndexedDB.
> - Different browsers or devices do not share a synchronized database unless an exported `.sqlite` file is transferred.
> - Client-side role checks and hashing provide demonstration-level access control, not production server-side security.

---

## 11. Project Directory Structure

```text
smart-government-scheme-tracker/
├── index.html                   # Public portal landing page
├── login.html                   # Multi-role authentication page
├── register.html                # Citizen self-registration page
├── run.bat                      # 1-click static server launcher
│
├── lib/                         # SQLite WebAssembly Library
│   ├── sql-wasm.js              # JavaScript WASM loader
│   └── sql-wasm.wasm            # Compiled SQLite WebAssembly binary (offline-ready)
│
├── database/                    # SQL Assets & Scripts
│   ├── schema.sql               # SQLite DDL: 8 tables, keys, constraints, indexes
│   ├── seed.sql                 # Pure SQL DML: 10 citizens, 8 schemes, criteria, apps
│   └── queries.sql              # Academic queries (CRUD, Joins, Aggregates, Transactions)
│
├── js/                          # Client-Side Application Modules
│   ├── database.js              # Reusable SQLite WASM & IndexedDB database service
│   ├── db-init.js               # Embedded SQL initializer & universal backup toolbar
│   ├── auth.js                  # Session guard & Web Crypto password hashing
│   ├── citizen.js               # Citizen dashboard, profile, & benefits ledger
│   ├── schemes.js               # Scheme catalog browsing, search & filters
│   ├── eligibility.js           # Dynamic SQL eligibility checker engine
│   ├── applications.js          # Application submission & dossier timeline
│   ├── officer.js               # Scheme CRUD, Review dossier, & ACID approval
│   └── reports.js               # 8 analytical SQL reports with SVG/Canvas charts
│
├── citizen/                     # Citizen Portal Views
│   ├── dashboard.html           # Citizen metrics & quick actions
│   ├── profile.html             # Profile management form
│   ├── schemes.html             # Scheme browser
│   ├── eligibility.html         # Dynamic SQL eligibility checker
│   ├── applications.html        # Citizen application history
│   ├── application-details.html # Dossier audit timeline
│   └── benefits.html            # Sanctioned DBT benefit ledger
│
├── officer/                     # Government Officer Portal Views
│   ├── dashboard.html           # Departmental queue & metrics
│   ├── schemes.html             # Scheme management table
│   ├── create-scheme.html       # Scheme & criteria creation form
│   ├── edit-scheme.html         # Scheme & criteria update form
│   ├── criteria.html            # Criteria scrutiny matrix
│   ├── applications.html        # Application queue
│   ├── review-application.html  # Review dossier & ACID approval
│   ├── benefits.html            # Financial disbursement manager
│   └── reports.html             # 8 SQL analytical reports & CSV export
│
└── css/                         # Custom CSS3 Design System
    ├── style.css                # Base portal styles & national theme variables
    ├── dashboard.css            # Sidebar, topbar, cards, tables, charts
    ├── forms.css                # Form inputs, buttons, badges, modals
    └── responsive.css           # Mobile & tablet breakpoints
```

---

## 12. Academic Declaration

This project was developed strictly for academic fulfillment of the **Database Management System (DBMS)** course in the **B.Tech. II Year I Semester (AIML and B Section)** for the academic year **2026-27**. Fictional demonstration data has been used throughout for illustrative and educational integrity.
