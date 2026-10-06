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
    <title>Eligibility Criteria Matrix – Officer Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="../css/responsive.css">
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
                <li><a href="criteria.php" class="active"><span class="menu-icon">⚙️</span> Eligibility Rules</a></li>
                <li><a href="benefits.php"><span class="menu-icon">💳</span> Benefit Disbursements</a></li>
                <li><a href="reports.php"><span class="menu-icon">📈</span> SQL Analytics & Reports</a></li>
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
                <h2 class="page-heading">Eligibility Criteria Evaluation Matrix</h2>
            </div>
            <div class="topbar-right">
                <a href="create-scheme.php" class="btn btn-primary btn-sm">+ Define Scheme Criteria</a>
            </div>
        </header>

        <main class="app-content">
            <div class="alert alert-info">
                <strong>DBMS Concept Demonstrated:</strong> This matrix shows rows from <code>Eligibility_Criteria</code> joined with <code>Scheme</code>. A value of <code>NULL</code> represents that no restriction is applied on that attribute during citizen eligibility checking.
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Scheme Qualification Rules Registry</h3>
                </div>

                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>Scheme Name</th>
                                <th>Age Range</th>
                                <th>Income Limit</th>
                                <th>Category</th>
                                <th>Gender</th>
                                <th>Occupation</th>
                                <th>Education</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="criteriaMatrixTable">
                            <tr><td colspan="8" class="empty-state">Loading criteria matrix...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const tableBody = document.getElementById('criteriaMatrixTable');
        try {
            const res = await fetch('../php/schemes/list.php?status=all');
            const data = await res.json();

            if (!data.success) {
                tableBody.innerHTML = `<tr><td colspan="8" class="alert alert-danger">${data.message}</td></tr>`;
                return;
            }

            tableBody.innerHTML = data.data.map(s => {
                const ageText = (s.Min_Age || s.Max_Age) ? `${s.Min_Age || 0} - ${s.Max_Age || 'Any'} yrs` : '<span class="badge badge-inactive">Universal</span>';
                const incomeText = s.Income_Limit ? `≤ ₹${Number(s.Income_Limit).toLocaleString('en-IN')}` : '<span class="badge badge-inactive">No Limit</span>';
                const catText = s.Req_Category ? `<span class="badge badge-review">${s.Req_Category}</span>` : '<span class="badge badge-inactive">All</span>';
                const genText = s.Req_Gender ? `<span class="badge badge-pending">${s.Req_Gender}</span>` : '<span class="badge badge-inactive">All</span>';
                const occText = s.Req_Occupation ? `<span class="badge badge-approved">${s.Req_Occupation}</span>` : '<span class="badge badge-inactive">All</span>';
                const eduText = s.Req_Education ? s.Req_Education : '<span class="badge badge-inactive">None</span>';

                return `
                    <tr>
                        <td>
                            <strong style="color: var(--primary);">${s.Scheme_Name}</strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${s.Department_Name}</div>
                        </td>
                        <td>${ageText}</td>
                        <td><strong>${incomeText}</strong></td>
                        <td>${catText}</td>
                        <td>${genText}</td>
                        <td>${occText}</td>
                        <td>${eduText}</td>
                        <td>
                            <a href="edit-scheme.php?id=${s.Scheme_ID}" class="btn btn-secondary btn-sm">Edit Rules</a>
                        </td>
                    </tr>
                `;
            }).join('');

        } catch (e) {
            tableBody.innerHTML = '<tr><td colspan="8" class="alert alert-danger">Error loading criteria matrix.</td></tr>';
        }
    });
</script>
</body>
</html>
