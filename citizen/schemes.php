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
    <title>Welfare Schemes – Citizen Portal</title>
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
                <li><a href="schemes.php" class="active"><span class="menu-icon">📜</span> Welfare Schemes</a></li>
                <li><a href="applications.php"><span class="menu-icon">📝</span> My Applications</a></li>
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
                <h2 class="page-heading">Government Welfare Schemes Catalog</h2>
            </div>
            <div class="topbar-right">
                <a href="eligibility.php" class="btn btn-warning btn-sm">⚡ Check What You Qualify For</a>
            </div>
        </header>

        <main class="app-content">
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-item">
                    <label class="form-label" for="searchScheme">Search Scheme</label>
                    <input type="text" id="searchScheme" class="form-control" placeholder="Search by scheme name or keywords..." oninput="filterSchemes()">
                </div>
                <div class="filter-item">
                    <label class="form-label" for="filterDepartment">Department</label>
                    <select id="filterDepartment" class="form-control" onchange="filterSchemes()">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label class="form-label" for="filterType">Benefit Category</label>
                    <select id="filterType" class="form-control" onchange="filterSchemes()">
                        <option value="">All Benefit Types</option>
                        <option value="Financial Assistance">Financial Assistance</option>
                        <option value="Scholarship">Scholarship</option>
                        <option value="Subsidy">Subsidy</option>
                        <option value="Skill Training">Skill Training</option>
                        <option value="Pension & Healthcare">Pension & Healthcare</option>
                        <option value="Equipment Subsidy">Equipment Subsidy</option>
                    </select>
                </div>
            </div>

            <!-- Schemes Cards Grid -->
            <div id="schemesCatalogContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.5rem;">
                <!-- Rendered dynamically by js/schemes.js -->
            </div>
        </main>
    </div>
</div>

<!-- Scheme Details Modal -->
<div id="schemeModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="schemeModalTitle">Scheme Details</h3>
            <button type="button" class="modal-close" onclick="closeModal('schemeModal')">&times;</button>
        </div>
        <div class="modal-body" id="schemeModalContent"></div>
        <div class="modal-footer" id="schemeModalFooter"></div>
    </div>
</div>

<!-- Apply Confirmation Modal -->
<div id="applyModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Apply for Welfare Scheme</h3>
            <button type="button" class="modal-close" onclick="closeModal('applyModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="applySchemeId">
            <p>You are about to file an official online application for:</p>
            <div style="background: #eff6ff; border-left: 4px solid var(--primary); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                <strong id="applySchemeName" style="color: var(--primary-dark); font-size: 1.05rem;"></strong>
            </div>

            <div class="form-group">
                <label class="form-label" for="applyRemarks">Application Remarks / Statement of Purpose</label>
                <textarea id="applyRemarks" class="form-control" placeholder="Brief note explaining your requirement or enrollment intent..."></textarea>
                <div class="form-help">Your demographic profile will be attached automatically to this dossier.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('applyModal')">Cancel</button>
            <button type="button" id="confirmApplyBtn" class="btn btn-primary" onclick="submitApplication()">Confirm Application</button>
        </div>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/schemes.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadSchemesCatalog(false);
    });
</script>
</body>
</html>
