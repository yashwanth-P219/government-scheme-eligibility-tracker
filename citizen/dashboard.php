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
    <title>Citizen Dashboard – Smart Scheme Tracker</title>
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
            <div class="user-avatar">
                <?= strtoupper(substr($currentUser['name'] ?? 'C', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name" title="<?= htmlspecialchars($currentUser['name']) ?>">
                    <?= htmlspecialchars($currentUser['name']) ?>
                </div>
                <span class="user-role">CITIZEN</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main Navigation</div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><span class="menu-icon">📊</span> Dashboard</a></li>
                <li><a href="eligibility.php"><span class="menu-icon">🎯</span> Eligibility Checker</a></li>
                <li><a href="schemes.php"><span class="menu-icon">📜</span> Welfare Schemes</a></li>
                <li><a href="applications.php"><span class="menu-icon">📝</span> My Applications</a></li>
                <li><a href="benefits.php"><span class="menu-icon">💰</span> Benefits & DBT</a></li>
                <li><a href="profile.php"><span class="menu-icon">👤</span> My Profile</a></li>
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
                <h2 class="page-heading">Citizen Overview Dashboard</h2>
            </div>
            <div class="topbar-right">
                <a href="eligibility.php" class="btn btn-warning btn-sm">⚡ Check Eligible Schemes</a>
                <a href="profile.php" class="btn btn-outline btn-sm">Edit Profile</a>
            </div>
        </header>

        <main class="app-content">
            <!-- Incomplete Profile Warning Notice (conditional via JS) -->
            <div id="profileNotice" class="alert alert-warning" style="display: none;">
                <div>
                    <strong>Profile Information Incomplete!</strong> Your demographic and income profile is not fully filled out. An incomplete profile prevents the SQL eligibility engine from accurately matching all welfare schemes.
                </div>
                <a href="profile.php" class="btn btn-warning btn-sm" style="margin-left: auto;">Complete Profile</a>
            </div>

            <!-- Citizen Welcome Banner -->
            <div class="card" style="background: linear-gradient(135deg, #1e3a8a, #0f172a); color: #ffffff; margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="color: #ffffff; font-size: 1.5rem; margin-bottom: 4px;">
                            Welcome back, <span id="citizenWelcomeName"><?= htmlspecialchars($currentUser['name']) ?></span>
                        </h2>
                        <p style="color: #cbd5e1; margin-bottom: 0; font-size: 0.92rem;">
                            Your centralized gateway to monitor scheme eligibility, submit applications, and track direct benefit transfers.
                        </p>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); padding: 0.75rem 1.25rem; border-radius: var(--radius-md); min-width: 200px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 4px;">
                            <span>Profile Readiness:</span>
                            <strong id="profileCompletenessPct">0%</strong>
                        </div>
                        <div class="progress-bar-container" style="background: rgba(255,255,255,0.2); height: 8px;">
                            <div id="profileProgressBar" class="progress-bar-fill fill-success" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metrics Stat Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Available Schemes</span>
                        <div class="stat-icon">📜</div>
                    </div>
                    <div class="stat-value" id="statTotalSchemes">-</div>
                    <div class="stat-desc">Government welfare schemes active</div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-header">
                        <span class="stat-title">Eligible Schemes</span>
                        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">🎯</div>
                    </div>
                    <div class="stat-value text-success" id="statEligibleSchemes">-</div>
                    <div class="stat-desc">Matched based on your profile</div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-header">
                        <span class="stat-title">Applications Filed</span>
                        <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">📝</div>
                    </div>
                    <div class="stat-value text-primary" id="statSubmittedApps">-</div>
                    <div class="stat-desc">Total applications submitted</div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-header">
                        <span class="stat-title">Pending / In Review</span>
                        <div class="stat-icon" style="background: var(--warning-bg); color: var(--warning);">⏳</div>
                    </div>
                    <div class="stat-value text-warning" id="statPendingApps">-</div>
                    <div class="stat-desc">Awaiting officer adjudication</div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-header">
                        <span class="stat-title">Approved Schemes</span>
                        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">✅</div>
                    </div>
                    <div class="stat-value text-success" id="statApprovedApps">-</div>
                    <div class="stat-desc">Sanctioned applications</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Benefits Received</span>
                        <div class="stat-icon">💰</div>
                    </div>
                    <div class="stat-value" id="statBenefitsReceived" style="color: var(--success);">-</div>
                    <div class="stat-desc">Direct Benefit Transfer (DBT) disbursed</div>
                </div>
            </div>

            <!-- Recent Applications Section -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Applications & Status</h3>
                    <a href="applications.php" class="btn btn-outline btn-sm">View All Applications &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Welfare Scheme & Department</th>
                                <th>Filing Date</th>
                                <th>Review Status</th>
                                <th>Sanctioned Benefit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="recentApplicationsTable">
                            <tr><td colspan="6" class="empty-state">Loading your applications...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/citizen.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadCitizenDashboard();
    });
</script>
</body>
</html>
