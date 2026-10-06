<?php
require_once __DIR__ . '/../php/auth/session.php';
requireCitizen(false);
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Benefits & Direct Benefit Transfer (DBT) – Citizen Portal</title>
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
            <div class="sidebar-emblem">🏛️</div>
            <div class="sidebar-title">
                <h2>Welfare Portal</h2>
                <span>Citizen Services</span>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'C', 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="user-role">CITIZEN</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main Navigation</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
                <li><a href="eligibility.php"><span class="menu-icon">🎯</span> Eligibility Checker</a></li>
                <li><a href="schemes.php"><span class="menu-icon">📜</span> Welfare Schemes</a></li>
                <li><a href="applications.php"><span class="menu-icon">📝</span> My Applications</a></li>
                <li><a href="benefits.php" class="active"><span class="menu-icon">💰</span> Benefits & DBT</a></li>
                <li><a href="profile.php"><span class="menu-icon">👤</span> My Profile</a></li>
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
                <h2 class="page-heading">Direct Benefit Transfer (DBT) Ledger</h2>
            </div>
            <div class="topbar-right">
                <a href="applications.php" class="btn btn-outline btn-sm">View Applications</a>
            </div>
        </header>

        <main class="app-content">
            <!-- Summary Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-success">
                    <div class="stat-header">
                        <span class="stat-title">Total Benefits Paid</span>
                        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">₹</div>
                    </div>
                    <div class="stat-value text-success" id="citPaidAmount">₹0</div>
                    <div class="stat-desc">Directly credited to citizen account</div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-header">
                        <span class="stat-title">In Processing</span>
                        <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">⏳</div>
                    </div>
                    <div class="stat-value text-primary" id="citProcessingAmount">₹0</div>
                    <div class="stat-desc">Sanctioned bank clearance in progress</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Sanctioned Transactions</span>
                        <div class="stat-icon">📑</div>
                    </div>
                    <div class="stat-value" id="citTotalTransactions">0</div>
                    <div class="stat-desc">Total benefit disbursement records</div>
                </div>
            </div>

            <!-- Benefits Ledger Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Disbursement Transaction History</h3>
                </div>
                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>Benefit ID</th>
                                <th>Scheme Name & Department</th>
                                <th>Benefit Amount</th>
                                <th>Sanction Date</th>
                                <th>Payment Status</th>
                                <th>Transaction Reference (DBT)</th>
                                <th>Approving Officer</th>
                            </tr>
                        </thead>
                        <tbody id="citBenefitsTable">
                            <tr><td colspan="7" class="empty-state">Loading your benefits history...</td></tr>
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
        const tableBody = document.getElementById('citBenefitsTable');
        try {
            const res = await fetch('../php/benefits/list.php');
            const data = await res.json();

            if (!data.success) {
                tableBody.innerHTML = `<tr><td colspan="7" class="alert alert-danger">${data.message}</td></tr>`;
                return;
            }

            const b = data.data;
            document.getElementById('citPaidAmount').textContent = '₹' + Number(b.summary.paid_amount || 0).toLocaleString('en-IN');
            document.getElementById('citProcessingAmount').textContent = '₹' + Number(b.summary.processing_amount || 0).toLocaleString('en-IN');
            document.getElementById('citTotalTransactions').textContent = b.summary.total_transactions;

            if (!b.benefits || b.benefits.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-icon">💸</div>
                            <h3>No Benefits Disbursed Yet</h3>
                            <p>Once your scheme applications are approved by designated officers, your financial and material benefit grants will be recorded here.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = b.benefits.map(row => {
                let badgeClass = 'badge-pending';
                if (row.Payment_Status === 'Processing') badgeClass = 'badge-review';
                if (row.Payment_Status === 'Paid') badgeClass = 'badge-approved';
                if (row.Payment_Status === 'Failed') badgeClass = 'badge-rejected';

                return `
                    <tr>
                        <td><strong>#${row.Benefit_ID}</strong></td>
                        <td>
                            <div style="font-weight: 600; color: var(--primary);">${row.Scheme_Name}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">🏛️ ${row.Department_Name}</div>
                        </td>
                        <td><strong style="color: var(--success); font-size: 1.05rem;">₹${Number(row.Benefit_Amount).toLocaleString('en-IN')}</strong></td>
                        <td>${row.Benefit_Date}</td>
                        <td><span class="badge ${badgeClass}">${row.Payment_Status}</span></td>
                        <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">${row.Transaction_Reference || 'Awaiting DBT Reference'}</code></td>
                        <td>${row.Approving_Officer}</td>
                    </tr>
                `;
            }).join('');

        } catch (e) {
            tableBody.innerHTML = '<tr><td colspan="7" class="alert alert-danger">Error retrieving benefit records.</td></tr>';
        }
    });
</script>
</body>
</html>
