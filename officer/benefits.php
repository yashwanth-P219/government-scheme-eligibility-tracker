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
    <title>Benefit Disbursements & DBT Manager – Officer Portal</title>
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
                <li><a href="create-scheme.php"><span class="menu-icon">➕</span> Create New Scheme</a></li>
                <li><a href="criteria.php"><span class="menu-icon">⚙️</span> Eligibility Rules</a></li>
                <li><a href="benefits.php" class="active"><span class="menu-icon">💳</span> Benefit Disbursements</a></li>
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
                <h2 class="page-heading">Direct Benefit Transfer (DBT) Disbursement Ledger</h2>
            </div>
            <div class="topbar-right">
                <a href="reports.php" class="btn btn-warning btn-sm">📊 Financial Reports</a>
            </div>
        </header>

        <main class="app-content">
            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Sanctioned Grants</span>
                        <div class="stat-icon">📑</div>
                    </div>
                    <div class="stat-value" id="benTotalCount">-</div>
                    <div class="stat-desc">Benefits provisioned in database</div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-header">
                        <span class="stat-title">Total Financial Value</span>
                        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">₹</div>
                    </div>
                    <div class="stat-value text-success" id="benTotalAmount">-</div>
                    <div class="stat-desc">Cumulative sanctioned amount</div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-header">
                        <span class="stat-title">Cleared (Paid)</span>
                        <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">✅</div>
                    </div>
                    <div class="stat-value text-success" id="benPaidAmount">-</div>
                    <div class="stat-desc">Directly credited to citizens</div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-header">
                        <span class="stat-title">In Clearing (Processing)</span>
                        <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">⏳</div>
                    </div>
                    <div class="stat-value text-primary" id="benProcessingAmount">-</div>
                    <div class="stat-desc">Awaiting banking DBT confirmation</div>
                </div>
            </div>

            <!-- Ledger Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Disbursement Ledger (Benefits Relation)</h3>
                </div>

                <div class="table-responsive">
                    <table class="gov-table">
                        <thead>
                            <tr>
                                <th>Benefit ID</th>
                                <th>Citizen Beneficiary</th>
                                <th>Welfare Scheme</th>
                                <th>Sanctioned Amount</th>
                                <th>Benefit Date</th>
                                <th>Disbursement Status</th>
                                <th>DBT Reference</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="officerBenefitsTable">
                            <tr><td colspan="8" class="empty-state">Loading disbursement ledger...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal: Update Benefit Status -->
<div id="benefitStatusModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width: 480px;">
        <div class="modal-header">
            <h3>Update Disbursement Status</h3>
            <button type="button" class="modal-close" onclick="closeModal('benefitStatusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="updateBenefitId">

            <div class="form-group">
                <label class="form-label" for="updatePaymentStatus">Payment Status <span class="required">*</span></label>
                <select id="updatePaymentStatus" class="form-control" required>
                    <option value="Pending">Pending Funds Allocation</option>
                    <option value="Processing">Processing (Banking Clearing)</option>
                    <option value="Paid">Paid (Credited to Citizen)</option>
                    <option value="Failed">Failed / Reversed</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="updateTxnRef">Transaction Reference / UTR Number</label>
                <input type="text" id="updateTxnRef" class="form-control" placeholder="e.g. TXN-DBT-2026-88219">
                <div class="form-help">Unique reference from government banking gateway.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('benefitStatusModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveBenefitStatusUpdate()">Save Status Update</button>
        </div>
    </div>
</div>

<script src="../js/auth.js"></script>
<script src="../js/officer.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadOfficerBenefits();
    });
</script>
</body>
</html>
