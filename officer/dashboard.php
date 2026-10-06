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
    <title>Officer Command Center – Smart Scheme Tracker</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/forms.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="app-shell">
    <!-- Officer Sidebar -->
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
                <div class="user-name" title="<?= htmlspecialchars($currentUser['name']) ?>">
                    <?= htmlspecialchars($currentUser['name']) ?>
                </div>
                <span class="user-role" style="background: #0284c7;"><?= htmlspecialchars($currentUser['designation'] ?? 'OFFICER') ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Management Modules</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><span class="menu-icon">📊</span> Command Center</a></li>
                <li><a href="applications.php"><span class="menu-icon">📥</span> Review Applications</a></li>
                <li><a href="schemes.php"><span class="menu-icon">📜</span> Manage Schemes</a></li>
                <li><a href="create-scheme.php"><span class="menu-icon">➕</span> Create New Scheme</a></li>
                <li><a href="criteria.php"><span class="menu-icon">⚙️</span> Eligibility Rules</a></li>
                <li><a href="benefits.php"><span class="menu-icon">💳</span> Benefit Disbursements</a></li>
                <li><a href="reports.php"><span class="menu-icon">📈</span> SQL Analytics & Reports</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <button onclick="handleLogout()" class="btn btn-danger btn-sm" style="width: 100%;">
                🚪 Sign Out
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="menu-toggle-btn">☰</button>
                <h2 class="page-heading">Government Officer Command Center</h2>
            </div>
            <div class="topbar-right">
                <a href="create-scheme.php" class="btn btn-primary btn-sm">+ Launch New Scheme</a>
                <a href="reports.php" class="btn btn-warning btn-sm">📊 View SQL Analytics</a>
            </div>
        </header>

        <main class="app-content">
            <!-- Officer Identity Card -->
            <div class="card" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); color: #ffffff; margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <span class="badge badge-approved" style="margin-bottom: 0.5rem;">OFFICIAL AUTHORITY ACTIVE</span>
                        <h2 style="color: #ffffff; font-size: 1.5rem; margin-bottom: 4px;" id="officerNameDisplay">
                            <?= htmlspecialchars($currentUser['name']) ?>
                        </h2>
                        <p style="color: #93c5fd; margin-bottom: 0; font-size: 0.9rem;" id="officerDeptDisplay">
                            <?= htmlspecialchars($currentUser['designation'] ?? 'Officer') ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <a href="applications.php?status=Pending" class="btn btn-warning btn-sm">Pending Review Queue</a>
                        <a href="benefits.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.2);">DBT Ledger</a>
                    </div>
                </div>
            </div>

            <!-- Real-time Database Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Schemes</span>
                        <div class="stat-icon">📜</div>
                    </div>
                    <div class="stat-value" id="statTotalSchemes">-</div>
                    <div class="stat-desc"><span id="statActiveSchemes">-</span> active programs</div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-header">
                        <span class="stat-title">Total Applications</span>
                        <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">📥</div>
                    </div>
                    <div class="stat-value text-primary" id="statTotalApps">-</div>
                    <div class="stat-desc">Citizen dossiers filed</div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-header">
                        <span class="stat-title">Pending Adjudication</span>
                        <div class="stat-icon" style="background: var(--warning-bg); color: var(--warning);">⏳</div>
                    </div>
                    <div class="stat-value text-warning" id="statPendingApps">-</div>
                    <div class="stat-desc">Awaiting first scrutiny</div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-header">
                        <span class="stat-title">Under Review</span>
                        <div class="stat-icon" style="background: #eff6ff; color: #2563eb;">🔍</div>
                    </div>
                    <div class="stat-value" id="statReviewApps" style="color: #2563eb;">-</div>
                    <div class="stat-desc">Field inquiries active</div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-header">
                        <span class="stat-title">Approved & Sanctioned</span>
                        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">✅</div>
                    </div>
                    <div class="stat-value text-success" id="statApprovedApps">-</div>
                    <div class="stat-desc">Qualifying citizens</div>
                </div>

                <div class="stat-card stat-danger">
                    <div class="stat-header">
                        <span class="stat-title">Rejected Dossiers</span>
                        <div class="stat-icon" style="background: var(--danger-bg); color: var(--danger);">❌</div>
                    </div>
                    <div class="stat-value text-danger" id="statRejectedApps">-</div>
                    <div class="stat-desc">Ineligible or criteria mismatch</div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-header">
                        <span class="stat-title">Benefits Sanctioned</span>
                        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">💰</div>
                    </div>
                    <div class="stat-value text-success" id="statTotalBenefits">-</div>
                    <div class="stat-desc">Total financial disbursements</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Registered Citizens</span>
                        <div class="stat-icon">👥</div>
                    </div>
                    <div class="stat-value" id="statTotalCitizens">-</div>
                    <div class="stat-desc">Total user database entries</div>
                </div>
            </div>

            <!-- Recent Applications Queue Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Citizen Submissions (Pending Review Queue)</h3>
                    <a href="applications.php" class="btn btn-outline btn-sm">Full Application Registry &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Applicant</th>
                                <th>Target Welfare Scheme</th>
                                <th>Filing Date</th>
                                <th>Current Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="officerQueueTable">
                            <tr><td colspan="6" class="empty-state">Loading application queue...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/officer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadOfficerDashboard();
    });
</script>
</body>
</html>
