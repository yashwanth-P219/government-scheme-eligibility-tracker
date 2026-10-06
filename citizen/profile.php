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
    <title>My Profile – Citizen Portal</title>
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
                <li><a href="benefits.php"><span class="menu-icon">💰</span> Benefits & DBT</a></li>
                <li><a href="profile.php" class="active"><span class="menu-icon">👤</span> My Profile</a></li>
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
                <h2 class="page-heading">Citizen Profile & Socioeconomic Parameters</h2>
            </div>
            <div class="topbar-right">
                <span id="profileCompletenessBadge" class="badge badge-pending">Loading...</span>
            </div>
        </header>

        <main class="app-content">
            <div class="card" style="max-width: 900px; margin: 0 auto;">
                <div class="card-header">
                    <div>
                        <h3>Citizen Demographic & Eligibility Attributes</h3>
                        <p class="text-muted" style="margin: 0; font-size: 0.88rem;">
                            These attributes are queried by the MySQL database eligibility engine to calculate qualifying schemes.
                        </p>
                    </div>
                </div>

                <form id="profileForm">
                    <h4 style="font-size: 1rem; color: var(--primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem;">
                        1. Personal & Contact Details
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" class="form-control" readonly title="Email cannot be modified as it acts as unique identity.">
                            <div class="form-help">Unique Citizen Login Identifier (Read-only)</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="age">Age (Years) <span class="required">*</span></label>
                            <input type="number" id="age" class="form-control" min="0" max="120" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" class="form-control" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <h4 style="font-size: 1rem; color: var(--primary); margin: 1.5rem 0 1rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem;">
                        2. Socioeconomic & Educational Classification
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="category">Social Category <span class="required">*</span></label>
                            <select id="category" class="form-control" required>
                                <option value="General">General</option>
                                <option value="OBC">OBC (Other Backward Class)</option>
                                <option value="SC">SC (Scheduled Caste)</option>
                                <option value="ST">ST (Scheduled Tribe)</option>
                                <option value="EWS">EWS (Economically Weaker Section)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="income">Annual Household Income (₹) <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-addon">₹</span>
                                <input type="number" id="income" class="form-control" min="0" step="1000" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="occupation">Occupation <span class="required">*</span></label>
                            <select id="occupation" class="form-control" required>
                                <option value="Student">Student</option>
                                <option value="Farmer">Farmer</option>
                                <option value="Artisan">Artisan / Weaver</option>
                                <option value="Self-Employed">Self-Employed / Business</option>
                                <option value="Unemployed">Unemployed Youth</option>
                                <option value="Private Job">Private Sector Employee</option>
                                <option value="Government Job">Government Employee</option>
                                <option value="Retired">Retired / Senior Citizen</option>
                                <option value="Homemaker">Homemaker</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="education">Highest Qualification <span class="required">*</span></label>
                            <select id="education" class="form-control" required>
                                <option value="Below 10th">Below 10th Standard</option>
                                <option value="10th Pass">10th Standard (Matric)</option>
                                <option value="12th Pass">12th Standard (Higher Secondary)</option>
                                <option value="Diploma">Polytechnic Diploma</option>
                                <option value="Graduate">Bachelor Degree (Graduate)</option>
                                <option value="Post Graduate">Master Degree (Post Graduate)</option>
                                <option value="None">None</option>
                            </select>
                        </div>
                    </div>

                    <h4 style="font-size: 1rem; color: var(--primary); margin: 1.5rem 0 1rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem;">
                        3. Domicile & Residential Location
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="state">State <span class="required">*</span></label>
                            <input type="text" id="state" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="district">District <span class="required">*</span></label>
                            <input type="text" id="district" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="address">Full Residential Address</label>
                        <textarea id="address" class="form-control"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg">Update Profile in Database</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/citizen.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadCitizenProfile();
        initProfileForm();
    });
</script>
</body>
</html>
