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
    <title>Review Application Dossier – Officer Portal</title>
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
                <li><a href="applications.php" class="active"><span class="menu-icon">📥</span> Review Applications</a></li>
                <li><a href="schemes.php"><span class="menu-icon">📜</span> Manage Schemes</a></li>
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
                <h2 class="page-heading">Citizen Application Dossier Scrutiny</h2>
            </div>
            <div class="topbar-right">
                <a href="applications.php" class="btn btn-secondary btn-sm">&larr; Back to Queue</a>
            </div>
        </header>

        <main class="app-content" id="reviewDossierContainer">
            <!-- Header Dossier Banner -->
            <div class="card" style="border-left: 5px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">
                            Application Dossier <span id="revAppId">-</span>
                        </div>
                        <h2 id="revSchemeName" style="color: var(--primary); font-size: 1.5rem; margin: 0.25rem 0;">-</h2>
                        <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;">
                            🏛️ <span id="revDepartment">-</span> &bull; Filed: <span id="revAppDate">-</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span id="revStatusBadge" class="badge badge-pending" style="font-size: 0.95rem; padding: 6px 14px;">-</span>
                    </div>
                </div>

                <!-- Adjudication Action Bar -->
                <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="markUnderReview()">
                        🔍 Mark Under Review
                    </button>
                    <button type="button" class="btn btn-success btn-sm" onclick="openModal('approveModal')">
                        ✅ Approve Application (ACID Transaction)
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="openModal('rejectModal')">
                        ❌ Reject Application
                    </button>
                </div>
            </div>

            <!-- Existing Benefit Record if approved -->
            <div id="existingBenefitBox" class="alert alert-success" style="display: none;">
                <div>
                    <strong>Direct Benefit Transfer Sanctioned:</strong>
                    Amount: <strong id="existingBenAmount">-</strong> | Status: <span id="existingBenStatus">-</span> | Ref: <code id="existingBenTxn">-</code>
                </div>
            </div>

            <!-- Two-Column Review Layout -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.5rem;">
                <!-- Column 1: Citizen Profile Details -->
                <div class="card">
                    <div class="card-header">
                        <h3>Applicant Demographic Profile</h3>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; font-size: 0.9rem;">
                        <div><span class="text-muted">Applicant:</span> <strong id="revCitizenName">-</strong></div>
                        <div><span class="text-muted">Email:</span> <span id="revCitizenEmail">-</span></div>
                        <div><span class="text-muted">Mobile Phone:</span> <span id="revCitizenPhone">-</span></div>
                        <div><span class="text-muted">Age:</span> <strong id="revCitizenAge">-</strong></div>
                        <div><span class="text-muted">Gender:</span> <span id="revCitizenGender">-</span></div>
                        <div><span class="text-muted">Social Category:</span> <strong id="revCitizenCategory">-</strong></div>
                        <div><span class="text-muted">Annual Income:</span> <strong id="revCitizenIncome" style="color: var(--primary);">-</strong></div>
                        <div><span class="text-muted">Occupation:</span> <span id="revCitizenOccupation">-</span></div>
                        <div><span class="text-muted">Education:</span> <span id="revCitizenEducation">-</span></div>
                        <div><span class="text-muted">Location:</span> <span id="revCitizenLocation">-</span></div>
                    </div>
                    <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 0.85rem;">
                        <span class="text-muted">Address:</span> <span id="revCitizenAddress">-</span>
                    </div>
                </div>

                <!-- Column 2: Scheme & Benefit Specifications -->
                <div class="card">
                    <div class="card-header">
                        <h3>Target Scheme & Entitlement</h3>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <span class="badge badge-review" id="revBenefitType">-</span>
                    </div>
                    <div style="background: #ecfdf5; border-left: 4px solid var(--success); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1rem;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #065f46; text-transform: uppercase;">Official Entitlement:</div>
                        <div id="revBenefitDesc" style="font-size: 0.92rem; color: #065f46; font-weight: 600; margin-top: 2px;">-</div>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">
                        Adjudicating officer must ensure applicant satisfies all criteria defined in the <code>Eligibility_Criteria</code> relation before committing approval.
                    </p>
                </div>
            </div>

            <!-- Criteria Verification Matrix Table -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3>Eligibility Criteria Scrutiny Matrix (Database Rules vs Applicant Attributes)</h3>
                </div>
                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>Criteria Rule</th>
                                <th>Mandated Scheme Requirement</th>
                                <th>Applicant Stored Value</th>
                                <th>Relational Match Result</th>
                            </tr>
                        </thead>
                        <tbody id="criteriaComparisonTable">
                            <tr><td colspan="4" class="empty-state">Evaluating rules...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal: Approve Application (ACID Transaction) -->
<div id="approveModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Sanction Application (ACID Transaction)</h3>
            <button type="button" class="modal-close" onclick="closeModal('approveModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="actionAppIdApprove">
            <div class="alert alert-info" style="font-size: 0.85rem;">
                <strong>DBMS Transaction Notice:</strong> Committing approval will atomically execute:
                <code>START TRANSACTION &rarr; Update Application &rarr; Insert Approval &rarr; Insert Benefits &rarr; COMMIT</code>.
                Any constraint failure automatically triggers a <code>ROLLBACK</code>.
            </div>

            <div class="form-group">
                <label class="form-label" for="approveBenefitAmount">Sanctioned Financial Amount (₹) <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-addon">₹</span>
                    <input type="number" id="approveBenefitAmount" class="form-control" placeholder="e.g. 25000" min="0" step="500" value="25000" required>
                </div>
                <div class="form-help">Amount will be recorded in the Benefits relation.</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="approvePaymentStatus">Initial Payment Status</label>
                    <select id="approvePaymentStatus" class="form-control">
                        <option value="Processing" selected>Processing (DBT In Queue)</option>
                        <option value="Paid">Paid (Immediate Clearance)</option>
                        <option value="Pending">Pending Funds Allocation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="approveTxnRef">Transaction Reference ID</label>
                    <input type="text" id="approveTxnRef" class="form-control" placeholder="Leave empty for auto-generation">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="approveRemarks">Officer Adjudication Remarks</label>
                <textarea id="approveRemarks" class="form-control" placeholder="Verification commentary and approval order details...">Application verified and found compliant with all departmental eligibility criteria.</textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
            <button type="button" id="confirmApproveBtn" class="btn btn-success" onclick="executeApprovalTransaction()">
                Commit Approval & Benefit Transaction
            </button>
        </div>
    </div>
</div>

<!-- Modal: Reject Application -->
<div id="rejectModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Reject Application Dossier</h3>
            <button type="button" class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="actionAppIdReject">
            <p>Please enter the official grounds for rejecting this application:</p>

            <div class="form-group">
                <label class="form-label" for="rejectRemarks">Rejection Grounds / Explanation <span class="required">*</span></label>
                <textarea id="rejectRemarks" class="form-control" placeholder="Specify which eligibility criterion was not met (e.g. Income exceeds scheme limit, Ineligible social category)..." required></textarea>
                <div class="form-help">Mandatory reason will be visible to the citizen applicant.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
            <button type="button" id="confirmRejectBtn" class="btn btn-danger" onclick="executeRejectionTransaction()">
                Confirm Rejection
            </button>
        </div>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/officer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadApplicationForReview();
    });
</script>
</body>
</html>
