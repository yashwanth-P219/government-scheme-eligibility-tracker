-- ==============================================================================
-- DATABASE MANAGEMENT SYSTEM (DBMS) COURSE-BASED PROJECT
-- Project Title: Smart Government Scheme Eligibility and Benefit Tracker
-- File: database/seed_sqlite.sql
-- Pure SQL DML Seed Script (Comprehensive Fictional Demo Data)
-- Default Demo Password for Citizens: Citizen@123
-- Default Demo Password for Officers: Officer@123
-- ==============================================================================

-- 1. SEED: Department (4 Ministries / Administrative Bodies)
INSERT INTO Department (Department_ID, Department_Name, Description) VALUES
(1, 'Department of Agriculture & Farmers Welfare', 'Formulates policies and programs for agrarian sustainability, crop security, and farmer income support.'),
(2, 'Department of Higher Education & Skill Development', 'Promotes post-secondary academic access, technical certifications, merit scholarships, and youth employability.'),
(3, 'Department of Women & Child Development', 'Drives empowerment, institutional finance, entrepreneurship, and maternal/child welfare initiatives.'),
(4, 'Department of Social Justice & Empowerment', 'Administers social safety nets, pensions for senior citizens, and inclusive welfare for marginalized communities.');

-- 2. SEED: Officer (5 Departmental Authorities)
-- Default Password: Officer@123
-- Hash: 4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0
INSERT INTO Officer (Officer_ID, Department_ID, Name, Email, Password_Hash, Designation, Created_At) VALUES
(1, 1, 'Dr. Anand Verma', 'officer.agri@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Joint Director (Agronomy)', '2026-01-10 09:30:00'),
(2, 2, 'Meenakshi Sundaram', 'officer.edu@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Deputy Secretary (Scholarships)', '2026-01-10 10:00:00'),
(3, 3, 'Rajeshwari Nair', 'officer.welfare@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Senior Welfare Commissioner', '2026-01-12 11:15:00'),
(4, 4, 'Vikramaditya Singh', 'officer.social@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Assistant Director (Social Defense)', '2026-01-15 14:00:00'),
(5, 2, 'Sunita Deshmukh', 'officer.skills@gov.in', '4eaf252115c02cba64f2e74e44c4c87c9bc9262e567aca6ce35fb641f88ed8c0', 'Project Director (Skill Missions)', '2026-01-18 16:30:00');

-- 3. SEED: Citizen (10 Demographically Diverse Citizens)
-- Default Password: Citizen@123
-- Hash: 42febb9ecda9790abba738e99faa38d2338088b25426c5a40ad07e9f0dab3443
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

-- 4. SEED: Scheme (8 Welfare Programs)
INSERT INTO Scheme (Scheme_ID, Department_ID, Scheme_Name, Description, Benefit_Type, Benefit_Description, Last_Date, Status, Created_At) VALUES
(1, 1, 'PM Kisan Samman & Soil Security Yojana', 'Provides annual income support to landholding farmer families and free periodic soil health testing cards.', 'Financial Assistance', 'Rs. 6,000 direct benefit transfer per annum in three equal installments of Rs. 2,000.', '2027-03-31', 'Active', '2026-01-20 09:00:00'),
(2, 2, 'National Merit-cum-Means Post-Matric Scholarship', 'Financial scholarship aimed at arresting dropout rates among meritorious college students from low-income families.', 'Scholarship', 'Rs. 25,000 per academic year credited directly to accredited college fee accounts.', '2026-12-31', 'Active', '2026-01-20 09:30:00'),
(3, 3, 'Mahila Udyami Capital Subsidy & Incubation Scheme', 'Encourages women micro-entrepreneurs to establish manufacturing, handloom, and service enterprises.', 'Subsidy', '35% capital subsidy up to Rs. 2,00,000 on term loans plus free 1-year mentor incubation.', '2027-06-30', 'Active', '2026-01-22 10:00:00'),
(4, 2, 'Pradhan Mantri Yuva Skill & Apprenticeship Mission', 'Provides industry-relevant vocational training, soft skills enhancement, and recognized apprenticeship placement.', 'Skill Training', 'Free 6-month NSQF technical certification plus Rs. 8,000 monthly apprenticeship stipend.', '2026-11-30', 'Active', '2026-01-25 11:00:00'),
(5, 4, 'Senior Citizen Healthcare & Nutritional Security Yojana', 'Provides monthly social security pension and comprehensive geriatric healthcare support for elderly citizens.', 'Pension & Healthcare', 'Monthly direct pension of Rs. 3,500 plus cashless medicines at empanelled primary health centers.', '2027-12-31', 'Active', '2026-01-28 14:00:00'),
(6, 2, 'Economically Weaker Section (EWS) Higher Education Grant', 'Targeted grants for students from low-income households pursuing professional degree courses.', 'Financial Grant', 'One-time scholarship grant of Rs. 50,000 for university tuition and learning hardware.', '2026-10-31', 'Active', '2026-02-01 10:30:00'),
(7, 4, 'Rural Handloom & Traditional Artisan Support Mission', 'Assistance for weavers and indigenous craftspersons to procure modernized tools and raw materials.', 'Equipment Subsidy', 'Toolkit grant worth Rs. 15,000 plus working capital subsidy of Rs. 20,000 at zero interest.', '2027-04-30', 'Active', '2026-02-05 12:00:00'),
(8, 1, 'Small Farmer Solar Pump & Micro-Irrigation Subsidy', 'Assists marginal farmers in adopting solar powered water pumps and water-saving drip irrigation kits.', 'Subsidy', '70% capital equipment subsidy up to Rs. 85,000 on subsidized solar agricultural pumps.', '2026-12-15', 'Active', '2026-02-08 15:00:00');

-- 5. SEED: Eligibility_Criteria (Associated Criteria for All 8 Schemes)
INSERT INTO Eligibility_Criteria (Criteria_ID, Scheme_ID, Min_Age, Max_Age, Income_Limit, Category, Gender, Occupation, Education, State, District) VALUES
(1, 1, 18, 75, 300000.00, NULL, NULL, 'Farmer', NULL, NULL, NULL),
(2, 2, 17, 28, 250000.00, NULL, NULL, 'Student', '12th Pass', NULL, NULL),
(3, 3, 21, 55, 500000.00, NULL, 'Female', NULL, NULL, NULL, NULL),
(4, 4, 18, 30, 400000.00, NULL, NULL, NULL, '10th Pass', NULL, NULL),
(5, 5, 60, 120, 200000.00, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 6, 17, 25, 180000.00, 'EWS', NULL, 'Student', '12th Pass', NULL, NULL),
(7, 7, 18, 65, 220000.00, NULL, NULL, 'Artisan', NULL, NULL, NULL),
(8, 8, 21, 70, 250000.00, NULL, NULL, 'Farmer', NULL, NULL, NULL);

-- 6. SEED: Application (12 Fictional Citizen Submissions)
INSERT INTO Application (Application_ID, Citizen_ID, Scheme_ID, Application_Date, Status, Remarks) VALUES
(1, 1, 2, '2026-02-10 10:00:00', 'Approved', 'Valid college admission receipt and family income certificate verified.'),
(2, 2, 3, '2026-02-11 11:30:00', 'Approved', 'Business project report on organic handicrafts sanctioned.'),
(3, 3, 1, '2026-02-12 09:15:00', 'Approved', 'Landholding record verified through state Bhulekh portal.'),
(4, 3, 8, '2026-02-14 14:00:00', 'Under Review', 'Field inspection scheduled for borewell location.'),
(5, 4, 5, '2026-02-15 10:45:00', 'Approved', 'Aadhaar age authenticated. Eligible for elderly pension.'),
(6, 5, 2, '2026-02-16 12:00:00', 'Approved', 'High merit percentile and verified EWS revenue certificate.'),
(7, 5, 6, '2026-02-17 15:30:00', 'Approved', 'Sanctioned for technical engineering textbook grant.'),
(8, 6, 7, '2026-02-18 11:10:00', 'Approved', 'Traditional handloom artisan registry verified.'),
(9, 6, 3, '2026-02-19 16:20:00', 'Under Review', 'Awaiting micro-enterprise registration certificate.'),
(10, 7, 1, '2026-02-20 09:50:00', 'Pending', 'Application submitted. Document verification in queue.'),
(11, 9, 4, '2026-02-21 14:15:00', 'Under Review', 'Enrolled for screening round in Electrical Trade batch.'),
(12, 8, 2, '2026-02-22 13:00:00', 'Rejected', 'Annual household income Rs. 3,10,000 exceeds threshold limit of Rs. 2,50,000.');

-- 7. SEED: Approval (Adjudications by Designated Officers)
INSERT INTO Approval (Approval_ID, Application_ID, Officer_ID, Approval_Date, Decision, Remarks) VALUES
(1, 1, 2, '2026-02-15 14:00:00', 'Approved', 'Scholarship granted for Academic Year 2026-27.'),
(2, 2, 3, '2026-02-16 11:20:00', 'Approved', 'Capital subsidy sanctioned under Mahila Udyami Yojana.'),
(3, 3, 1, '2026-02-17 10:10:00', 'Approved', 'Direct farmer income support sanctioned.'),
(4, 5, 4, '2026-02-20 15:00:00', 'Approved', 'Senior citizen nutritional pension approved.'),
(5, 6, 2, '2026-02-21 16:30:00', 'Approved', 'Merit post-matric scholarship sanctioned.'),
(6, 7, 2, '2026-02-22 10:45:00', 'Approved', 'EWS Higher Education Grant sanctioned.'),
(7, 8, 4, '2026-02-24 12:15:00', 'Approved', 'Artisan modernization toolkit sanctioned.'),
(8, 12, 2, '2026-02-25 17:00:00', 'Rejected', 'Application does not meet the specified income threshold criteria.');

-- 8. SEED: Benefits (Sanctioned Financial Disbursements)
INSERT INTO Benefits (Benefit_ID, Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference) VALUES
(1, 1, 25000.00, '2026-02-18', 'Paid', 'TXN-DBT-20260218-88219'),
(2, 2, 70000.00, '2026-02-20', 'Paid', 'TXN-SUBSIDY-20260220-41032'),
(3, 3, 2000.00,  '2026-02-22', 'Paid', 'TXN-KISAN-20260222-19402'),
(4, 4, 3500.00,  '2026-02-25', 'Processing', 'TXN-PENSION-20260225-50211'),
(5, 5, 25000.00, '2026-02-26', 'Processing', 'TXN-SCHOLAR-20260226-72819'),
(6, 6, 50000.00, '2026-02-28', 'Pending', NULL),
(7, 7, 15000.00, '2026-03-01', 'Pending', NULL);
