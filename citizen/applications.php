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
    <title>My Applications – Citizen Portal</title>
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
                <li><a href="applications.php" class="active"><span class="menu-icon">📝</span> My Applications</a></li>
                <li><a href="benefits.php"><span class="menu-icon">💰</span> Benefits & DBT</a></li>
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
                <h2 class="page-heading">My Welfare Applications Tracking</h2>
            </div>
            <div class="topbar-right">
                <a href="eligibility.php" class="btn btn-primary btn-sm">+ Apply for New Scheme</a>
            </div>
        </header>

        <main class="app-content">
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-item">
                    <label class="form-label" for="searchApp">Search by Scheme / ID</label>
                    <input type="text" id="searchApp" class="form-control" placeholder="Search applications..." oninput="filterCitizenApps()">
                </div>
                <div class="filter-item">
                    <label class="form-label" for="filterAppStatus">Filter by Status</label>
                    <select id="filterAppStatus" class="form-control" onchange="filterCitizenApps()">
                        <option value="">All Application Statuses</option>
                        <option value="Pending">Pending Review</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Applications Table Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Submitted Application History</h3>
                </div>
                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>App ID</th>
                                <th>Scheme & Department</th>
                                <th>Benefit Type</th>
                                <th>Date Filed</th>
                                <th>Review Status</th>
                                <th>Sanctioned Benefit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="applicationsListTable">
                            <tr><td colspan="7" class="empty-state">Loading your application records...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/applications.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadCitizenApplications();
    });
</script>
</body>
</html>
