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
    <title>Create Welfare Scheme – Officer Portal</title>
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
                <li><a href="create-scheme.php" class="active"><span class="menu-icon">➕</span> Create New Scheme</a></li>
                <li><a href="criteria.php"><span class="menu-icon">⚙️</span> Eligibility Rules</a></li>
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
                <h2 class="page-heading">Launch Welfare Scheme & Define Eligibility Criteria</h2>
            </div>
            <div class="topbar-right">
                <a href="schemes.php" class="btn btn-secondary btn-sm">&larr; Back to Schemes</a>
            </div>
        </header>

        <main class="app-content">
            <div class="card" style="max-width: 950px; margin: 0 auto;">
                <div class="card-header">
                    <div>
                        <h3>New Scheme Registration Form</h3>
                        <p class="text-muted" style="margin: 0; font-size: 0.85rem;">
                            Inserts atomically into both <code>Scheme</code> and <code>Eligibility_Criteria</code> tables using an ACID transaction.
                        </p>
                    </div>
                </div>

                <form id="createSchemeForm">
                    <h4 style="color: var(--primary); font-size: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem;">
                        1. Scheme Metadata & Administrative Sponsoring
                    </h4>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label" for="scheme_name">Scheme Title / Name <span class="required">*</span></label>
                            <input type="text" id="scheme_name" class="form-control" placeholder="e.g. National Merit Post-Matric Scholarship Scheme" required minlength="3">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="department_id">Sponsoring Department <span class="required">*</span></label>
                            <select id="department_id" class="form-control" required></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="benefit_type">Benefit Classification <span class="required">*</span></label>
                            <select id="benefit_type" class="form-control" required>
                                <option value="Financial Assistance">Direct Financial Assistance</option>
                                <option value="Scholarship">Scholarship / Fee Waiver</option>
                                <option value="Subsidy">Capital / Equipment Subsidy</option>
                                <option value="Skill Training">Skill Training & Stipend</option>
                                <option value="Pension & Healthcare">Pension & Healthcare Allowance</option>
                                <option value="Equipment Subsidy">Equipment / Toolkit Subsidy</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_date">Application Deadline</label>
                            <input type="date" id="last_date" class="form-control">
                            <div class="form-help">Leave empty if open year-round.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Scheme Objectives & Detailed Description <span class="required">*</span></label>
                        <textarea id="description" class="form-control" placeholder="Provide complete information on what the scheme accomplishes, target demographics, and eligibility mandate..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="benefit_description">Official Benefit Provision & Entitlements <span class="required">*</span></label>
                        <textarea id="benefit_description" class="form-control" placeholder="e.g. ₹25,000 per academic year credited directly to student fee accounts." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Initial Scheme Status <span class="required">*</span></label>
                        <select id="status" class="form-control" required>
                            <option value="Active">Active (Accepting Applications)</option>
                            <option value="Inactive">Inactive (Draft / Archived)</option>
                        </select>
                    </div>

                    <h4 style="color: var(--primary); font-size: 1rem; margin: 1.75rem 0 1rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem;">
                        2. Eligibility Criteria Matrix (NULL = Condition Not Applicable)
                    </h4>

                    <div class="alert alert-info" style="font-size: 0.85rem;">
                        <strong>DBMS Criteria Rule:</strong> Fields left empty will be stored as <code>NULL</code> in the <code>Eligibility_Criteria</code> table, meaning that specific constraint will not restrict any citizen applicant.
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="min_age">Minimum Age (Years)</label>
                            <input type="number" id="min_age" class="form-control" placeholder="e.g. 18" min="0" max="120">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="max_age">Maximum Age (Years)</label>
                            <input type="number" id="max_age" class="form-control" placeholder="e.g. 35" min="0" max="120">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="income_limit">Max Annual Income Ceiling (₹)</label>
                            <div class="input-group">
                                <span class="input-addon">₹</span>
                                <input type="number" id="income_limit" class="form-control" placeholder="e.g. 250000" min="0" step="1000">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="gender">Gender Requirement</label>
                            <select id="gender" class="form-control">
                                <option value="All">All Genders (Universal)</option>
                                <option value="Male">Male Only</option>
                                <option value="Female">Female Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="category">Social Category Restriction</label>
                            <select id="category" class="form-control">
                                <option value="All">All Categories (Universal)</option>
                                <option value="General">General Only</option>
                                <option value="OBC">OBC Only</option>
                                <option value="SC">SC Only</option>
                                <option value="ST">ST Only</option>
                                <option value="EWS">EWS Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="occupation">Target Occupation</label>
                            <select id="occupation" class="form-control">
                                <option value="All">All Occupations (Universal)</option>
                                <option value="Student">Student</option>
                                <option value="Farmer">Farmer</option>
                                <option value="Artisan">Artisan / Weaver</option>
                                <option value="Self-Employed">Self-Employed / Business</option>
                                <option value="Unemployed">Unemployed Youth</option>
                                <option value="Retired">Retired / Senior Citizen</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="education">Minimum Qualification</label>
                            <select id="education" class="form-control">
                                <option value="All">No Requirement (Universal)</option>
                                <option value="Below 10th">Below 10th</option>
                                <option value="10th Pass">10th Standard Pass</option>
                                <option value="12th Pass">12th Standard Pass</option>
                                <option value="Graduate">Graduate (Bachelor Degree)</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="state">State Restriction</label>
                            <input type="text" id="state" class="form-control" placeholder="All India (Leave blank if universal)">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="district">District Restriction</label>
                            <input type="text" id="district" class="form-control" placeholder="All Districts (Leave blank if universal)">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.75rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                        <a href="schemes.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg">Launch Scheme & Commit Criteria</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/officer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadDepartmentsDropdown('department_id');

        const form = document.getElementById('createSchemeForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Executing Database Transaction...';

            const payload = {
                scheme_name: document.getElementById('scheme_name').value.trim(),
                department_id: document.getElementById('department_id').value,
                benefit_type: document.getElementById('benefit_type').value,
                description: document.getElementById('description').value.trim(),
                benefit_description: document.getElementById('benefit_description').value.trim(),
                last_date: document.getElementById('last_date').value || null,
                status: document.getElementById('status').value,

                min_age: document.getElementById('min_age').value || null,
                max_age: document.getElementById('max_age').value || null,
                income_limit: document.getElementById('income_limit').value || null,
                gender: document.getElementById('gender').value,
                category: document.getElementById('category').value,
                occupation: document.getElementById('occupation').value,
                education: document.getElementById('education').value,
                state: document.getElementById('state').value.trim() || null,
                district: document.getElementById('district').value.trim() || null
            };

            try {
                const res = await fetch('../php/schemes/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await res.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'schemes.php';
                    }, 1000);
                } else {
                    showToast(result.message || 'Error creating scheme.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Launch Scheme & Commit Criteria';
                }
            } catch (err) {
                showToast('Server communication error during transaction.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Launch Scheme & Commit Criteria';
            }
        });
    });
</script>
</body>
</html>
