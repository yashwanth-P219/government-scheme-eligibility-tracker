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
    <title>Eligibility Evaluation Engine – Citizen Portal</title>
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
                <li><a href="eligibility.php" class="active"><span class="menu-icon">🎯</span> Eligibility Checker</a></li>
                <li><a href="schemes.php"><span class="menu-icon">📜</span> Welfare Schemes</a></li>
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
                <h2 class="page-heading">Database-Driven Eligibility Evaluation Engine</h2>
            </div>
            <div class="topbar-right">
                <a href="profile.php" class="btn btn-outline btn-sm">Update Saved Profile</a>
            </div>
        </header>

        <main class="app-content">
            <!-- Academic Explanation Card -->
            <div class="card" style="background: #f8fafc; border-left: 4px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h3 style="color: var(--primary); font-size: 1.1rem; margin-bottom: 4px;">
                            ⚙️ Real-time Relational Database Query Execution
                        </h3>
                        <p style="font-size: 0.88rem; color: var(--text-main); margin-bottom: 0;">
                            This engine runs SQL queries matching your registered profile against the <code>Eligibility_Criteria</code> table.
                            Condition values configured as <code>NULL</code> in MySQL are interpreted as universal (non-restrictive).
                        </p>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="text-align: right;">
                            <div style="font-size: 1.4rem; font-weight: 800; color: var(--success);" id="eligibleCountDisplay">-</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Eligible Schemes</div>
                        </div>
                        <div style="font-size: 1.5rem; color: var(--border-dark);">/</div>
                        <div>
                            <div style="font-size: 1.4rem; font-weight: 800; color: var(--primary-dark);" id="totalCountDisplay">-</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Active</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- What-if Parameter Simulator Accordion -->
            <div class="card">
                <div class="card-header">
                    <h3>Simulation & Profile Override (What-If Analysis)</h3>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="runEligibilityCheck(false)">Show All Schemes</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="runEligibilityCheck(true)">Show Qualifying Only</button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="evalAge">Test Age</label>
                        <input type="number" id="evalAge" class="form-control" placeholder="Leave blank to use profile">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="evalIncome">Test Annual Income (₹)</label>
                        <input type="number" id="evalIncome" class="form-control" placeholder="Leave blank to use profile">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="evalGender">Test Gender</label>
                        <select id="evalGender" class="form-control">
                            <option value="">Use Profile Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="evalCategory">Test Category</label>
                        <select id="evalCategory" class="form-control">
                            <option value="">Use Profile Category</option>
                            <option value="General">General</option>
                            <option value="OBC">OBC</option>
                            <option value="SC">SC</option>
                            <option value="ST">ST</option>
                            <option value="EWS">EWS</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="evalOccupation">Test Occupation</label>
                        <select id="evalOccupation" class="form-control">
                            <option value="">Use Profile Occupation</option>
                            <option value="Student">Student</option>
                            <option value="Farmer">Farmer</option>
                            <option value="Artisan">Artisan</option>
                            <option value="Self-Employed">Self-Employed</option>
                            <option value="Unemployed">Unemployed</option>
                            <option value="Retired">Retired</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="runEligibilityCheck(false)">
                        🔄 Re-evaluate Eligibility Against Database
                    </button>
                </div>
            </div>

            <!-- Evaluated Schemes Results Grid -->
            <div id="eligibilityResultsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                <!-- Rendered dynamically by js/eligibility.js -->
            </div>
        </main>
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
            <p>You qualify for this scheme. Confirm submission for:</p>
            <div style="background: #eff6ff; border-left: 4px solid var(--primary); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                <strong id="applySchemeName" style="color: var(--primary-dark); font-size: 1.05rem;"></strong>
            </div>

            <div class="form-group">
                <label class="form-label" for="applyRemarks">Statement of Purpose / Notes</label>
                <textarea id="applyRemarks" class="form-control" placeholder="Optional notes for the reviewing officer..."></textarea>
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
<script src="../js/eligibility.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        runEligibilityCheck(false);
    });
</script>
</body>
</html>
