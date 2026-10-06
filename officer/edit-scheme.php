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
    <title>Edit Scheme & Criteria – Officer Portal</title>
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
                <li><a href="schemes.php" class="active"><span class="menu-icon">📜</span> Manage Schemes</a></li>
                <li><a href="create-scheme.php"><span class="menu-icon">➕</span> Create New Scheme</a></li>
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
                <h2 class="page-heading">Edit Welfare Scheme & Eligibility Criteria</h2>
            </div>
            <div class="topbar-right">
                <a href="schemes.php" class="btn btn-secondary btn-sm">&larr; Back to Schemes</a>
            </div>
        </header>

        <main class="app-content">
            <div class="card" style="max-width: 950px; margin: 0 auto;">
                <div class="card-header">
                    <div>
                        <h3>Modify Scheme Parameters <span id="schemeIdBadge"></span></h3>
                        <p class="text-muted" style="margin: 0; font-size: 0.85rem;">
                            Updates scheme metadata and associated eligibility boundaries in MySQL.
                        </p>
                    </div>
                </div>

                <form id="editSchemeForm">
                    <input type="hidden" id="scheme_id">

                    <h4 style="color: var(--primary); font-size: 1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem;">
                        1. Scheme Metadata
                    </h4>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label" for="scheme_name">Scheme Title <span class="required">*</span></label>
                            <input type="text" id="scheme_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="department_id">Department <span class="required">*</span></label>
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
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Detailed Description <span class="required">*</span></label>
                        <textarea id="description" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="benefit_description">Benefit Entitlements <span class="required">*</span></label>
                        <textarea id="benefit_description" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Scheme Status</label>
                        <select id="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <h4 style="color: var(--primary); font-size: 1rem; margin: 1.75rem 0 1rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem;">
                        2. Associated Eligibility Constraints
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="min_age">Minimum Age</label>
                            <input type="number" id="min_age" class="form-control" min="0" max="120">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="max_age">Maximum Age</label>
                            <input type="number" id="max_age" class="form-control" min="0" max="120">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="income_limit">Max Income Ceiling (₹)</label>
                            <div class="input-group">
                                <span class="input-addon">₹</span>
                                <input type="number" id="income_limit" class="form-control" min="0" step="1000">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="gender">Gender</label>
                            <select id="gender" class="form-control">
                                <option value="All">All Genders (Universal)</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="category">Social Category</label>
                            <select id="category" class="form-control">
                                <option value="All">All Categories (Universal)</option>
                                <option value="General">General</option>
                                <option value="OBC">OBC</option>
                                <option value="SC">SC</option>
                                <option value="ST">ST</option>
                                <option value="EWS">EWS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="occupation">Target Occupation</label>
                            <select id="occupation" class="form-control">
                                <option value="All">All Occupations (Universal)</option>
                                <option value="Student">Student</option>
                                <option value="Farmer">Farmer</option>
                                <option value="Artisan">Artisan</option>
                                <option value="Self-Employed">Self-Employed</option>
                                <option value="Unemployed">Unemployed</option>
                                <option value="Retired">Retired</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="education">Education</label>
                            <select id="education" class="form-control">
                                <option value="All">Universal</option>
                                <option value="Below 10th">Below 10th</option>
                                <option value="10th Pass">10th Standard Pass</option>
                                <option value="12th Pass">12th Standard Pass</option>
                                <option value="Graduate">Graduate</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="state">State</label>
                            <input type="text" id="state" class="form-control" placeholder="All India">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="district">District</label>
                            <input type="text" id="district" class="form-control" placeholder="All Districts">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.75rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                        <a href="schemes.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg">Update Scheme in Database</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/officer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        await loadDepartmentsDropdown('department_id');

        const urlParams = new URLSearchParams(window.location.search);
        const schemeId = urlParams.get('id');

        if (!schemeId) {
            showToast('No Scheme ID specified.', 'error');
            return;
        }

        try {
            const res = await fetch(`../php/schemes/get.php?id=${schemeId}`);
            const data = await res.json();

            if (!data.success) {
                showToast(data.message, 'error');
                return;
            }

            const s = data.data;
            document.getElementById('scheme_id').value = s.Scheme_ID;
            document.getElementById('schemeIdBadge').textContent = `(#${s.Scheme_ID})`;
            document.getElementById('scheme_name').value = s.Scheme_Name;
            document.getElementById('department_id').value = s.Department_ID;
            document.getElementById('benefit_type').value = s.Benefit_Type;
            document.getElementById('description').value = s.Description;
            document.getElementById('benefit_description').value = s.Benefit_Description;
            document.getElementById('last_date').value = s.Last_Date || '';
            document.getElementById('status').value = s.Status;

            document.getElementById('min_age').value = s.Min_Age !== null ? s.Min_Age : '';
            document.getElementById('max_age').value = s.Max_Age !== null ? s.Max_Age : '';
            document.getElementById('income_limit').value = s.Income_Limit !== null ? s.Income_Limit : '';
            document.getElementById('gender').value = s.Req_Gender || 'All';
            document.getElementById('category').value = s.Req_Category || 'All';
            document.getElementById('occupation').value = s.Req_Occupation || 'All';
            document.getElementById('education').value = s.Req_Education || 'All';
            document.getElementById('state').value = s.Req_State || '';
            document.getElementById('district').value = s.Req_District || '';

        } catch (e) {
            showToast('Error loading scheme details.', 'error');
        }

        // Form submission
        document.getElementById('editSchemeForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Committing Updates...';

            const payload = {
                scheme_id: document.getElementById('scheme_id').value,
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
                const res = await fetch('../php/schemes/update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    setTimeout(() => { window.location.href = 'schemes.php'; }, 1000);
                } else {
                    showToast(result.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Update Scheme in Database';
                }
            } catch (err) {
                showToast('Error updating scheme.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Update Scheme in Database';
            }
        });
    });
</script>
</body>
</html>
