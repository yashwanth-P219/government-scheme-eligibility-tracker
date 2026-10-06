-- ==============================================================================
-- DATABASE MANAGEMENT SYSTEM (DBMS) COURSE-BASED PROJECT
-- Project Title: Smart Government Scheme Eligibility and Benefit Tracker
-- Academic Context: B.Tech. II Year I Semester – AIML & B Section
-- Relational Database DML Seed Data Script (SQLite WebAssembly)
-- Demo Passwords:
--   Citizen: Citizen@123 (Hash: 42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443)
--   Officer: Officer@123 (Hash: 4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0)
-- ==============================================================================

-- 1. DEPARTMENTS (3 Core Government Ministries)
INSERT INTO Department (Department_ID, Department_Name, Description) VALUES
(1, 'Department of Agriculture & Farmers Welfare', 'Formulates agrarian policies, crop support, agricultural equipment subsidies, and income support.'),
(2, 'Department of Higher Education & Skill Development', 'Promotes post-secondary academic access, technical certifications, merit scholarships, and youth employability.'),
(3, 'Department of Social Justice & Empowerment', 'Administers social safety nets, pensions for senior citizens, and inclusive welfare for marginalized communities.');

-- 2. OFFICERS (5 Departmental Authorities)
INSERT INTO Officer (Officer_ID, Department_ID, Name, Email, Password_Hash, Designation, Created_At) VALUES
(1, 1, 'Dr. Anand Verma', 'officer.agri@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Joint Director (Agronomy)', '2026-01-10 09:30:00'),
(2, 2, 'Meenakshi Sundaram', 'officer.edu@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Deputy Secretary (Scholarships)', '2026-01-10 10:00:00'),
(3, 3, 'Rajeshwari Nair', 'officer.welfare@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Senior Welfare Commissioner', '2026-01-12 11:15:00'),
(4, 3, 'Vikramaditya Singh', 'officer.social@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Assistant Director (Social Defense)', '2026-01-15 14:00:00'),
(5, 2, 'Sunita Deshmukh', 'officer.skills@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Project Director (Skill Missions)', '2026-01-18 16:30:00');

-- 3. CITIZENS (10 Demographically Diverse Citizens)
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

-- 4. SCHEMES (The 8 Specific Required Academic Schemes)
INSERT INTO Scheme (Scheme_ID, Department_ID, Scheme_Name, Description, Benefit_Type, Benefit_Description, Last_Date, Status, Created_At) VALUES
(1, 2, 'Student Scholarship Assistance Scheme', 'Financial assistance and fee support aimed at meritorious low-income students pursuing higher education.', 'Monetary', 'Rs. 25,000 per academic year credited directly to student bank accounts.', '2027-03-31', 'Active', '2026-01-20 09:00:00'),
(2, 3, 'Women Entrepreneurship Support Scheme', 'Provides capital subsidy and seed funding for women establishing micro and cottage enterprises.', 'Monetary', '35% capital subsidy up to Rs. 1,50,000 on approved enterprise term loans.', '2027-06-30', 'Active', '2026-01-20 09:30:00'),
(3, 1, 'Farmer Direct Income Support Scheme', 'Guaranteed direct cash benefit transfer to smallholder agricultural families for agrarian sustenance.', 'Monetary', 'Rs. 10,000 per annum paid in two equal installments directly via DBT.', '2027-12-31', 'Active', '2026-01-22 10:00:00'),
(4, 3, 'Senior Citizen Welfare Scheme', 'Direct monthly old-age welfare pension and subsidized geriatric healthcare assistance for senior citizens.', 'Monetary', 'Monthly pension of Rs. 3,000 credited directly to verified bank accounts.', '2027-12-31', 'Active', '2026-01-25 11:00:00'),
(5, 2, 'Youth Skill Development Scheme', 'Market-aligned vocational skill training and apprenticeship placement support for unemployed youth.', 'Skill Training', 'Free 6-month NSQF technical certification plus Rs. 6,000 monthly apprenticeship stipend.', '2026-11-30', 'Active', '2026-01-28 14:00:00'),
(6, 2, 'Economically Weaker Student Support Scheme', 'One-time educational grant and textbook allowance for students from economically weaker sections.', 'Monetary', 'One-time scholarship grant of Rs. 40,000 for college tuition and study hardware.', '2026-10-31', 'Active', '2026-02-01 10:30:00'),
(7, 3, 'Rural Employment Assistance Scheme', 'Guaranteed wage employment and rural community livelihood assistance for vulnerable village households.', 'Monetary', 'Direct wage credit of Rs. 350 per day for 100 days of guaranteed rural community work.', '2027-05-31', 'Active', '2026-02-05 12:00:00'),
(8, 1, 'Small Farmer Equipment Support Scheme', 'Subsidized purchase of modern agricultural machinery, power tillers, and drip irrigation units.', 'Subsidy', '50% equipment subsidy up to Rs. 50,000 on approved agricultural tools and implements.', '2026-12-15', 'Active', '2026-02-08 15:00:00');

-- 5. ELIGIBILITY CRITERIA (Corresponding Criteria for All 8 Schemes; NULL means Universal Match)
INSERT INTO Eligibility_Criteria (Criteria_ID, Scheme_ID, Min_Age, Max_Age, Income_Limit, Category, Gender, Occupation, Education, State, District) VALUES
(1, 1, 17, 28, 250000.00, NULL, NULL, 'Student', '12th Pass', NULL, NULL),
(2, 2, 21, 55, 500000.00, NULL, 'Female', NULL, NULL, NULL, NULL),
(3, 3, 18, 75, 300000.00, NULL, NULL, 'Farmer', NULL, NULL, NULL),
(4, 4, 60, 120, 200000.00, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 5, 18, 30, 350000.00, NULL, NULL, NULL, '10th Pass', NULL, NULL),
(6, 6, 17, 25, 180000.00, 'EWS', NULL, 'Student', '12th Pass', NULL, NULL),
(7, 7, 18, 65, 150000.00, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 8, 18, 70, 250000.00, NULL, NULL, 'Farmer', NULL, NULL, NULL);

-- 6. APPLICATIONS (12 Demo Submissions from Citizens)
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

-- 7. APPROVALS (Formal Adjudication Orders Rendered by Designated Department Officers)
INSERT INTO Approval (Approval_ID, Application_ID, Officer_ID, Approval_Date, Decision, Remarks) VALUES
(1, 1, 2, '2026-02-12 11:30:00', 'Approved', 'All academic and low-income criteria satisfied. Merit scholarship sanctioned.'),
(2, 2, 3, '2026-02-14 15:00:00', 'Approved', 'Eligible woman entrepreneur. Capital subsidy sanctioned for handloom manufacturing.'),
(3, 3, 1, '2026-02-15 10:45:00', 'Approved', 'Smallholder farmer credentials validated. Direct income support cleared.'),
(4, 4, 4, '2026-02-16 17:00:00', 'Approved', 'Senior citizen age (64) validated. Monthly social security pension approved.'),
(5, 5, 2, '2026-02-18 10:00:00', 'Approved', 'EWS category criteria satisfied. Higher education grant approved.'),
(6, 6, 3, '2026-02-19 14:30:00', 'Approved', 'Rural employment livelihood guarantee approved for artisanal worker.'),
(7, 7, 1, '2026-02-20 16:00:00', 'Approved', 'Agrarian machinery equipment subsidy sanctioned.'),
(8, 12, 1, '2026-02-26 15:30:00', 'Rejected', 'Exceeds equipment subsidy ceiling for the current financial cycle.');

-- 8. BENEFITS (Direct Benefit Transfer - DBT Financial Disbursements)
INSERT INTO Benefits (Benefit_ID, Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference) VALUES
(1, 1, 25000.00, '2026-02-13', 'Paid', 'TXN-DBT-2026-90211'),
(2, 2, 150000.00, '2026-02-16', 'Paid', 'TXN-DBT-2026-90212'),
(3, 3, 10000.00, '2026-02-17', 'Paid', 'TXN-DBT-2026-90213'),
(4, 4, 3000.00, '2026-02-18', 'Paid', 'TXN-DBT-2026-90214'),
(5, 5, 40000.00, '2026-02-20', 'Processing', 'TXN-DBT-2026-90215'),
(6, 6, 35000.00, '2026-02-22', 'Pending', 'TXN-DBT-2026-90216'),
(7, 7, 50000.00, '2026-02-24', 'Processing', 'TXN-DBT-2026-90217');
