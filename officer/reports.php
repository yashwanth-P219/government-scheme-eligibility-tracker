<?php
require_once __DIR__ . '/../php/auth/session.php';
requireOfficer(false);
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Analytics & Academic Reports – Officer Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        .sql-box {
            background-color: #0f172a;
            color: #38bdf8;
            padding: 0.85rem;
            border-radius: var(--radius-md);
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.8rem;
            line-height: 1.4;
            overflow-x: auto;
            margin-top: 0.5rem;
        }
        details summary {
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--primary);
            user-select: none;
            margin-top: 0.75rem;
        }
        details summary:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="app-shell">
    <!-- Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-emblem" style="background: linear-gradient(135deg, #2563eb, #1e3a8a);">🏛️</div>
            <div class="sidebar-title">
                <h2>Officer Portal</h2>
                <span>Administration</span>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar" style="border-color: #38bdf8;">
                <?= strtoupper(substr($currentUser['name'] ?? 'O', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="user-role" style="background: #0284c7;"><?= htmlspecialchars($currentUser['designation'] ?? 'OFFICER') ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Management Modules</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="menu-icon">📊</span> Command Center</a></li>
                <li><a href="applications.php"><span class="menu-icon">📥</span> Review Applications</a></li>
                <li><a href="schemes.php"><span class="menu-icon">📜</span> Manage Schemes</a></li>
                <li><a href="create-scheme.php"><span class="menu-icon">➕</span> Create New Scheme</a></li>
                <li><a href="criteria.php"><span class="menu-icon">⚙️</span> Eligibility Rules</a></li>
                <li><a href="benefits.php"><span class="menu-icon">💳</span> Benefit Disbursements</a></li>
                <li><a href="reports.php" class="active"><span class="menu-icon">📈</span> SQL Analytics & Reports</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <button onclick="handleLogout()" class="btn btn-danger btn-sm" style="width: 100%;">🚪 Sign Out</button>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="menu-toggle-btn">☰</button>
                <h2 class="page-heading">Database Management System (DBMS) Analytics Reports</h2>
            </div>
            <div class="topbar-right">
                <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨️ Print / Export PDF</button>
            </div>
        </header>

        <main class="app-content">
            <div class="alert alert-info">
                <strong>Academic DBMS Evaluation:</strong> All 8 reports below are computed directly from the MySQL database using real SQL aggregate functions (<code>COUNT</code>, <code>SUM</code>, <code>AVG</code>, <code>GROUP BY</code>, <code>HAVING</code>, <code>LEFT JOIN</code>, and Subqueries). Expand the query view under each report for faculty viva presentation.
            </div>

            <!-- Two-Column Grid for Reports 1 & 2 -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Report 1: Applications by Scheme -->
                <div class="card">
                    <div class="card-header">
                        <h3>Report 1: Applications by Scheme</h3>
                    </div>
                    <div id="report1Container">
                        <div class="empty-state">Loading report...</div>
                    </div>
                    <details>
                        <summary>View SQL Statement (LEFT JOIN & GROUP BY)</summary>
                        <pre class="sql-box">SELECT s.Scheme_ID, s.Scheme_Name, d.Department_Name,
       COUNT(a.Application_ID) AS Total_Applications
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
ORDER BY Total_Applications DESC;</pre>
                    </details>
                </div>

                <!-- Report 2: Applications by Status -->
                <div class="card">
                    <div class="card-header">
                        <h3>Report 2: Applications by Status</h3>
                    </div>
                    <div id="report2Container">
                        <div class="empty-state">Loading report...</div>
                    </div>
                    <details>
                        <summary>View SQL Statement (Subquery & Percentage Share)</summary>
                        <pre class="sql-box">SELECT Status, COUNT(*) AS Total_Count,
       ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM Application), 1) AS Percentage
FROM Application
GROUP BY Status
ORDER BY Total_Count DESC;</pre>
                    </details>
                </div>
            </div>

            <!-- Two-Column Grid for Reports 3 & 4 -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Report 3: Approved Applications by Department -->
                <div class="card">
                    <div class="card-header">
                        <h3>Report 3: Approved Applications by Department</h3>
                    </div>
                    <div id="report3Container">
                        <div class="empty-state">Loading report...</div>
                    </div>
                    <details>
                        <summary>View SQL Statement (Multi-Table JOIN with Filter)</summary>
                        <pre class="sql-box">SELECT d.Department_ID, d.Department_Name,
       COUNT(a.Application_ID) AS Approved_Count
FROM Department d
INNER JOIN Scheme s ON d.Department_ID = s.Department_ID
INNER JOIN Application a ON s.Scheme_ID = a.Scheme_ID
WHERE a.Status = 'Approved'
GROUP BY d.Department_ID, d.Department_Name
ORDER BY Approved_Count DESC;</pre>
                    </details>
                </div>

                <!-- Report 4: Benefits Distributed by Scheme -->
                <div class="card">
                    <div class="card-header">
                        <h3>Report 4: Benefits Distributed by Scheme</h3>
                    </div>
                    <div id="report4Container">
                        <div class="empty-state">Loading report...</div>
                    </div>
                    <details>
                        <summary>View SQL Statement (SUM & AVG Aggregations)</summary>
                        <pre class="sql-box">SELECT s.Scheme_ID, s.Scheme_Name, d.Department_Name,
       COUNT(b.Benefit_ID) AS Beneficiary_Count,
       COALESCE(SUM(b.Benefit_Amount), 0.00) AS Total_Benefits_Amount,
       COALESCE(AVG(b.Benefit_Amount), 0.00) AS Average_Benefit_Amount
FROM Scheme s
INNER JOIN Department d ON s.Department_ID = d.Department_ID
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
ORDER BY Total_Benefits_Amount DESC;</pre>
                    </details>
                </div>
            </div>

            <!-- Two-Column Grid for Reports 5 & 6 -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Report 5: Benefits Distributed by Month -->
                <div class="card">
                    <div class="card-header">
                        <h3>Report 5: Benefits Distributed by Month (Timeline)</h3>
                    </div>
                    <div id="report5Container">
                        <div class="empty-state">Loading report...</div>
                    </div>
                    <details>
                        <summary>View SQL Statement (DATE_FORMAT Grouping)</summary>
                        <pre class="sql-box">SELECT DATE_FORMAT(b.Benefit_Date, '%Y-%m') AS Disbursement_Month,
       COUNT(b.Benefit_ID) AS Total_Disbursements,
       SUM(b.Benefit_Amount) AS Monthly_Disbursed_Amount
FROM Benefits b
WHERE b.Payment_Status IN ('Paid', 'Processing')
GROUP BY DATE_FORMAT(b.Benefit_Date, '%Y-%m')
ORDER BY Disbursement_Month ASC;</pre>
                    </details>
                </div>

                <!-- Report 6: Citizen Category Distribution -->
                <div class="card">
                    <div class="card-header">
                        <h3>Report 6: Citizen Social Category Distribution</h3>
                    </div>
                    <div id="report6Container">
                        <div class="empty-state">Loading report...</div>
                    </div>
                    <details>
                        <summary>View SQL Statement (Demographic Inclusivity Ratio)</summary>
                        <pre class="sql-box">SELECT COALESCE(Category, 'General') AS Social_Category,
       COUNT(Citizen_ID) AS Citizen_Count,
       ROUND(COUNT(Citizen_ID) * 100.0 / (SELECT COUNT(*) FROM Citizen), 1) AS Percentage_Share
FROM Citizen
GROUP BY Social_Category
ORDER BY Citizen_Count DESC;</pre>
                    </details>
                </div>
            </div>

            <!-- Report 7: Scheme Utilization Rate -->
            <div class="card">
                <div class="card-header">
                    <h3>Report 7: Scheme Utilization Rate (Approved vs Applied)</h3>
                </div>
                <div id="report7Container">
                    <div class="empty-state">Loading report...</div>
                </div>
                <details>
                    <summary>View SQL Statement (Conditional SUM & NULLIF Protection)</summary>
                    <pre class="sql-box">SELECT s.Scheme_ID, s.Scheme_Name,
       COUNT(a.Application_ID) AS Total_Applied,
       SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) AS Total_Approved,
       ROUND((SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) * 100.0) / 
             NULLIF(COUNT(a.Application_ID), 0), 1) AS Utilization_Rate_Pct
FROM Scheme s
LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
GROUP BY s.Scheme_ID, s.Scheme_Name
ORDER BY Total_Applied DESC;</pre>
                </details>
            </div>

            <!-- Report 8: Pending Applications Summary & Age Analysis -->
            <div class="card">
                <div class="card-header">
                    <h3>Report 8: Pending Applications Age Analysis & Backlog Alert</h3>
                </div>
                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Applicant Name</th>
                                <th>Scheme Name</th>
                                <th>Submission Date</th>
                                <th>Turnaround Urgency</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="report8Table">
                            <tr><td colspan="6" class="empty-state">Loading pending applications...</td></tr>
                        </tbody>
                    </table>
                </div>
                <details>
                    <summary>View SQL Statement (DATEDIFF Turnaround Analysis)</summary>
                    <pre class="sql-box">SELECT a.Application_ID, c.Name AS Applicant_Name, c.Email AS Applicant_Email,
       s.Scheme_Name, d.Department_Name, a.Application_Date, a.Status,
       DATEDIFF(CURDATE(), a.Application_Date) AS Days_Pending
FROM Application a
INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
INNER JOIN Department d ON s.Department_ID = d.Department_ID
WHERE a.Status IN ('Pending', 'Under Review')
ORDER BY Days_Pending DESC;</pre>
                </details>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/reports.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadAllReports();
    });
</script>
</body>
</html>
