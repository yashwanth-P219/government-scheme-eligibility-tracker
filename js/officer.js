/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/officer.js
 */

let officerApplicationsData = [];
let officerSchemesData = [];
let officerBenefitsData = [];

// =============================================================================
// 1. OFFICER DASHBOARD
// =============================================================================
async function loadOfficerDashboard() {
    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        if (!user || user.role !== 'OFFICER') return;

        // Query metrics directly from SQLite WASM
        const totalSchemesRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Scheme;");
        const activeSchemesRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Scheme WHERE Status = 'Active';");
        const totalAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application;");
        const pendingAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application WHERE Status = 'Pending';");
        const reviewAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application WHERE Status = 'Under Review';");
        const approvedAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application WHERE Status = 'Approved';");
        const rejectedAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application WHERE Status = 'Rejected';");
        const benefitsTotalRow = GovDB.fetchOne("SELECT COALESCE(SUM(Benefit_Amount), 0.0) AS s FROM Benefits WHERE Payment_Status IN ('Paid', 'Processing');");
        const totalCitizensRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Citizen;");

        const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

        setVal('statTotalSchemes', totalSchemesRow ? totalSchemesRow.c : 0);
        setVal('statActiveSchemes', activeSchemesRow ? activeSchemesRow.c : 0);
        setVal('statTotalApps', totalAppsRow ? totalAppsRow.c : 0);
        setVal('statPendingApps', pendingAppsRow ? pendingAppsRow.c : 0);
        setVal('statReviewApps', reviewAppsRow ? reviewAppsRow.c : 0);
        setVal('statApprovedApps', approvedAppsRow ? approvedAppsRow.c : 0);
        setVal('statRejectedApps', rejectedAppsRow ? rejectedAppsRow.c : 0);
        setVal('statTotalBenefits', '₹' + Number(benefitsTotalRow ? benefitsTotalRow.s : 0).toLocaleString('en-IN'));
        setVal('statTotalCitizens', totalCitizensRow ? totalCitizensRow.c : 0);

        if (document.getElementById('officerNameDisplay')) {
            document.getElementById('officerNameDisplay').textContent = user.name;
        }
        if (document.getElementById('officerDeptDisplay')) {
            document.getElementById('officerDeptDisplay').textContent = `${user.designation || 'Officer'} – ${user.department_name || 'Ministry'}`;
        }

        // Recent applications queue table
        const queueTableBody = document.getElementById('officerQueueTable');
        if (queueTableBody) {
            const recentApps = GovDB.fetchAll(`
                SELECT 
                    a.Application_ID,
                    c.Name AS Citizen_Name,
                    c.Email AS Citizen_Email,
                    s.Scheme_Name,
                    d.Department_Name,
                    a.Application_Date,
                    a.Status
                FROM Application a
                INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
                INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                INNER JOIN Department d ON s.Department_ID = d.Department_ID
                WHERE a.Status IN ('Pending', 'Under Review')
                ORDER BY a.Application_Date ASC
                LIMIT 6;
            `);

            if (recentApps.length === 0) {
                queueTableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No pending applications awaiting scrutiny in the queue.</td></tr>';
            } else {
                queueTableBody.innerHTML = recentApps.map(app => {
                    let badgeClass = app.Status === 'Under Review' ? 'badge-review' : 'badge-pending';
                    const appDate = app.Application_Date ? new Date(app.Application_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }) : '-';
                    return `
                        <tr>
                            <td><strong>#${app.Application_ID}</strong></td>
                            <td>
                                <div style="font-weight: 600;">${app.Citizen_Name}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">${app.Citizen_Email}</div>
                            </td>
                            <td>
                                <div>${app.Scheme_Name}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">${app.Department_Name}</div>
                            </td>
                            <td>${appDate}</td>
                            <td><span class="badge ${badgeClass}">${app.Status}</span></td>
                            <td>
                                <a href="review-application.html?id=${app.Application_ID}" class="btn btn-primary btn-sm">Review Dossier</a>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }
    } catch (err) {
        console.error('Error loading officer dashboard:', err);
    }
}

// =============================================================================
// 2. SCHEME MANAGEMENT (Officer CRUD)
// =============================================================================
async function loadOfficerSchemes() {
    const tableBody = document.getElementById('officerSchemesTable');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="7" class="empty-state">Loading registered welfare schemes from SQLite...</td></tr>';

    try {
        await GovDB.initializeDatabase();
        officerSchemesData = GovDB.fetchAll(`
            SELECT 
                s.Scheme_ID,
                s.Department_ID,
                s.Scheme_Name,
                s.Description,
                s.Benefit_Type,
                s.Benefit_Description,
                s.Last_Date,
                s.Status,
                s.Created_At,
                d.Department_Name,
                COUNT(a.Application_ID) AS Total_Applicants
            FROM Scheme s
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
            GROUP BY s.Scheme_ID, s.Department_ID, s.Scheme_Name, s.Description, s.Benefit_Type, s.Benefit_Description, s.Last_Date, s.Status, s.Created_At, d.Department_Name
            ORDER BY s.Scheme_ID DESC;
        `);

        renderOfficerSchemesTable(officerSchemesData);
    } catch (err) {
        console.error('Error loading officer schemes:', err);
        tableBody.innerHTML = `<tr><td colspan="7" class="alert alert-danger">Error querying SQLite: ${err.message}</td></tr>`;
    }
}

function renderOfficerSchemesTable(schemes) {
    const tableBody = document.getElementById('officerSchemesTable');
    if (!tableBody) return;

    if (!schemes || schemes.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="empty-state">No schemes registered. Click "Create New Scheme" to add one.</td></tr>';
        return;
    }

    tableBody.innerHTML = schemes.map(s => {
        const isActive = s.Status === 'Active';
        return `
            <tr>
                <td><strong>#${s.Scheme_ID}</strong></td>
                <td>
                    <div style="font-weight: 600; color: var(--primary);">${s.Scheme_Name}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">${s.Department_Name}</div>
                </td>
                <td><span class="badge badge-review">${s.Benefit_Type}</span></td>
                <td><strong>${s.Total_Applicants || 0}</strong> applicants</td>
                <td>${s.Last_Date ? s.Last_Date : 'Permanent'}</td>
                <td>
                    <span class="badge ${isActive ? 'badge-approved' : 'badge-inactive'}">${s.Status}</span>
                </td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <a href="edit-scheme.html?id=${s.Scheme_ID}" class="btn btn-secondary btn-sm" title="Edit Scheme & Criteria">Edit</a>
                        <button onclick="toggleSchemeStatus(${s.Scheme_ID}, '${isActive ? 'Inactive' : 'Active'}')" class="btn ${isActive ? 'btn-danger' : 'btn-success'} btn-sm">
                            ${isActive ? 'Deactivate' : 'Activate'}
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

async function toggleSchemeStatus(schemeId, newStatus) {
    if (!confirm(`Are you sure you want to change this scheme's status to ${newStatus}?`)) return;

    try {
        await GovDB.initializeDatabase();
        await GovDB.updateRecord('Scheme', { Status: newStatus }, 'Scheme_ID = ?', [schemeId]);
        showToast(`Scheme status updated to '${newStatus}'!`, 'success');
        loadOfficerSchemes();
    } catch (e) {
        showToast('Error updating scheme status: ' + e.message, 'error');
    }
}

// Populate departments dropdown for forms
async function loadDepartmentsDropdown(selectId = 'department_id') {
    const select = document.getElementById(selectId);
    if (!select) return;

    try {
        await GovDB.initializeDatabase();
        const depts = GovDB.fetchAll("SELECT * FROM Department ORDER BY Department_Name ASC;");
        select.innerHTML = depts.map(d => `<option value="${d.Department_ID}">${d.Department_Name}</option>`).join('');
    } catch (e) {}
}

// Save New Scheme & Criteria
async function saveNewScheme(formData) {
    try {
        await GovDB.initializeDatabase();
        GovDB.beginTransaction();

        const schemeId = await GovDB.insertRecord('Scheme', {
            Department_ID: parseInt(formData.department_id),
            Scheme_Name: formData.scheme_name,
            Description: formData.description,
            Benefit_Type: formData.benefit_type,
            Benefit_Description: formData.benefit_description,
            Last_Date: formData.last_date || null,
            Status: 'Active'
        });

        await GovDB.insertRecord('Eligibility_Criteria', {
            Scheme_ID: schemeId,
            Min_Age: formData.min_age ? parseInt(formData.min_age) : null,
            Max_Age: formData.max_age ? parseInt(formData.max_age) : null,
            Income_Limit: formData.income_limit ? parseFloat(formData.income_limit) : null,
            Category: formData.category || null,
            Gender: formData.gender || null,
            Occupation: formData.occupation || null,
            Education: formData.education || null,
            State: formData.state || null,
            District: formData.district || null
        });

        await GovDB.commitTransaction();
        showToast(`Welfare Scheme #${schemeId} registered successfully!`, 'success');
        setTimeout(() => {
            window.location.href = 'schemes.html';
        }, 800);
    } catch (err) {
        GovDB.rollbackTransaction();
        showToast('Error saving scheme: ' + err.message, 'error');
    }
}

// =============================================================================
// 3. APPLICATIONS QUEUE & REVIEW
// =============================================================================
async function loadOfficerApplications() {
    const tableBody = document.getElementById('officerAppsTable');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="7" class="empty-state">Loading application submissions...</td></tr>';

    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();

        officerApplicationsData = GovDB.fetchAll(`
            SELECT 
                a.Application_ID,
                a.Citizen_ID,
                a.Scheme_ID,
                a.Application_Date,
                a.Status,
                a.Remarks,
                c.Name AS Citizen_Name,
                c.Email AS Citizen_Email,
                c.Phone AS Citizen_Phone,
                c.Age AS Citizen_Age,
                c.Category AS Citizen_Category,
                c.Income AS Citizen_Income,
                s.Scheme_Name,
                d.Department_Name
            FROM Application a
            INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            ORDER BY a.Application_ID DESC;
        `);

        renderOfficerAppsTable(officerApplicationsData);
    } catch (e) {
        console.error('Error loading officer apps:', e);
        tableBody.innerHTML = `<tr><td colspan="7" class="alert alert-danger">Error querying SQLite: ${e.message}</td></tr>`;
    }
}

function renderOfficerAppsTable(apps) {
    const tableBody = document.getElementById('officerAppsTable');
    if (!tableBody) return;

    if (!apps || apps.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="empty-state">No applications found matching criteria.</td></tr>';
        return;
    }

    tableBody.innerHTML = apps.map(app => {
        let badgeClass = 'badge-pending';
        if (app.Status === 'Under Review') badgeClass = 'badge-review';
        if (app.Status === 'Approved') badgeClass = 'badge-approved';
        if (app.Status === 'Rejected') badgeClass = 'badge-rejected';

        const appDate = app.Application_Date ? new Date(app.Application_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';

        return `
            <tr>
                <td><strong>#${app.Application_ID}</strong></td>
                <td>
                    <div style="font-weight: 600;">${app.Citizen_Name}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">${app.Citizen_Email}</div>
                </td>
                <td>
                    <div style="font-weight: 600; color: var(--primary);">${app.Scheme_Name}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">${app.Department_Name}</div>
                </td>
                <td>
                    <div>Age: ${app.Citizen_Age || '-'} | ${app.Citizen_Category || 'General'}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">₹${Number(app.Citizen_Income || 0).toLocaleString('en-IN')} / yr</div>
                </td>
                <td>${appDate}</td>
                <td><span class="badge ${badgeClass}">${app.Status}</span></td>
                <td>
                    <a href="review-application.html?id=${app.Application_ID}" class="btn btn-primary btn-sm">Review</a>
                </td>
            </tr>
        `;
    }).join('');
}

function filterOfficerApps() {
    const statusVal = document.getElementById('filterOfficerAppStatus')?.value || '';
    const searchVal = document.getElementById('searchOfficerApp')?.value.toLowerCase().trim() || '';

    const filtered = officerApplicationsData.filter(app => {
        const matchesStatus = !statusVal || app.Status === statusVal;
        const matchesSearch = !searchVal || 
            app.Citizen_Name.toLowerCase().includes(searchVal) ||
            app.Citizen_Email.toLowerCase().includes(searchVal) ||
            app.Scheme_Name.toLowerCase().includes(searchVal) ||
            app.Application_ID.toString().includes(searchVal);
        return matchesStatus && matchesSearch;
    });

    renderOfficerAppsTable(filtered);
}

// Load Application Dossier for Review
async function loadApplicationForReview() {
    const urlParams = new URLSearchParams(window.location.search);
    const appId = urlParams.get('id');

    if (!appId) {
        showToast('No Application ID specified.', 'error');
        return;
    }

    try {
        await GovDB.initializeDatabase();

        const d = GovDB.fetchOne(`
            SELECT 
                a.Application_ID,
                a.Citizen_ID,
                a.Scheme_ID,
                a.Application_Date,
                a.Status,
                a.Remarks AS Application_Remarks,
                c.Name AS Citizen_Name,
                c.Email AS Citizen_Email,
                c.Phone AS Citizen_Phone,
                c.Age AS Citizen_Age,
                c.Gender AS Citizen_Gender,
                c.Category AS Citizen_Category,
                c.Income AS Citizen_Income,
                c.Occupation AS Citizen_Occupation,
                c.Education AS Citizen_Education,
                c.District AS Citizen_District,
                c.State AS Citizen_State,
                c.Address AS Citizen_Address,
                s.Scheme_Name,
                s.Description AS Scheme_Description,
                s.Benefit_Type,
                s.Benefit_Description,
                s.Last_Date,
                d.Department_Name,
                d.Department_ID,
                e.Criteria_ID,
                e.Min_Age,
                e.Max_Age,
                e.Income_Limit,
                e.Category AS Req_Category,
                e.Gender AS Req_Gender,
                e.Occupation AS Req_Occupation,
                e.Education AS Req_Education,
                e.State AS Req_State,
                e.District AS Req_District,
                ap.Approval_ID,
                ap.Approval_Date,
                ap.Decision,
                ap.Remarks AS Approval_Remarks,
                b.Benefit_ID,
                b.Benefit_Amount,
                b.Payment_Status,
                b.Transaction_Reference
            FROM Application a
            INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
            LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
            LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
            WHERE a.Application_ID = ?;
        `, [appId]);

        if (!d) {
            document.getElementById('reviewDossierContainer').innerHTML = '<div class="alert alert-danger">Application not found in database.</div>';
            return;
        }

        const setTxt = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

        setTxt('revAppId', '#' + d.Application_ID);
        setTxt('revCitizenName', d.Citizen_Name);
        setTxt('revCitizenEmail', d.Citizen_Email);
        setTxt('revCitizenPhone', d.Citizen_Phone || 'Not set');
        setTxt('revCitizenAge', d.Citizen_Age !== null ? d.Citizen_Age + ' years' : 'Not specified');
        setTxt('revCitizenGender', d.Citizen_Gender || 'Not specified');
        setTxt('revCitizenCategory', d.Citizen_Category || 'General');
        setTxt('revCitizenIncome', d.Citizen_Income ? '₹' + Number(d.Citizen_Income).toLocaleString('en-IN') : 'Not specified');
        setTxt('revCitizenOccupation', d.Citizen_Occupation || 'Not specified');
        setTxt('revCitizenEducation', d.Citizen_Education || 'Not specified');
        setTxt('revCitizenLocation', `${d.Citizen_District || ''}, ${d.Citizen_State || ''}`);
        setTxt('revCitizenAddress', d.Citizen_Address || 'Not provided');

        setTxt('revSchemeName', d.Scheme_Name);
        setTxt('revDepartment', d.Department_Name);
        setTxt('revBenefitType', d.Benefit_Type);
        setTxt('revBenefitDesc', d.Benefit_Description);
        setTxt('revAppDate', new Date(d.Application_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }));

        let badgeClass = 'badge-pending';
        if (d.Status === 'Under Review') badgeClass = 'badge-review';
        if (d.Status === 'Approved') badgeClass = 'badge-approved';
        if (d.Status === 'Rejected') badgeClass = 'badge-rejected';

        const statusBadge = document.getElementById('revStatusBadge');
        if (statusBadge) {
            statusBadge.className = `badge ${badgeClass}`;
            statusBadge.textContent = d.Status;
        }

        renderCriteriaComparisonMatrix(d);

        if (document.getElementById('actionAppIdApprove')) document.getElementById('actionAppIdApprove').value = d.Application_ID;
        if (document.getElementById('actionAppIdReject')) document.getElementById('actionAppIdReject').value = d.Application_ID;

        if (d.Benefit_Amount && Number(d.Benefit_Amount) > 0) {
            const existingBenBox = document.getElementById('existingBenefitBox');
            if (existingBenBox) {
                existingBenBox.style.display = 'block';
                setTxt('existingBenAmount', '₹' + Number(d.Benefit_Amount).toLocaleString('en-IN'));
                setTxt('existingBenStatus', d.Payment_Status || 'Pending');
                setTxt('existingBenTxn', d.Transaction_Reference || 'Pending');
            }
        }

    } catch (err) {
        console.error('Error loading review dossier:', err);
    }
}

// Render Comparison Matrix
function renderCriteriaComparisonMatrix(d) {
    const tableBody = document.getElementById('criteriaComparisonTable');
    if (!tableBody) return;

    const rows = [
        {
            param: 'Age Qualification',
            req: (d.Min_Age || d.Max_Age) ? `${d.Min_Age || 0} to ${d.Max_Age || 'Any'} years` : 'Unrestricted',
            actual: d.Citizen_Age !== null ? `${d.Citizen_Age} years` : 'Not Set',
            matched: (!d.Min_Age || d.Citizen_Age >= d.Min_Age) && (!d.Max_Age || d.Citizen_Age <= d.Max_Age)
        },
        {
            param: 'Income Limit',
            req: d.Income_Limit ? `Max ₹${Number(d.Income_Limit).toLocaleString('en-IN')}` : 'No Limit',
            actual: d.Citizen_Income ? `₹${Number(d.Citizen_Income).toLocaleString('en-IN')}` : 'Not Set',
            matched: !d.Income_Limit || (d.Citizen_Income !== null && d.Citizen_Income <= d.Income_Limit)
        },
        {
            param: 'Gender',
            req: d.Req_Gender || 'Universal',
            actual: d.Citizen_Gender || 'Not Set',
            matched: !d.Req_Gender || (d.Citizen_Gender && d.Citizen_Gender.toLowerCase() === d.Req_Gender.toLowerCase())
        },
        {
            param: 'Category',
            req: d.Req_Category || 'Universal',
            actual: d.Citizen_Category || 'Not Set',
            matched: !d.Req_Category || (d.Citizen_Category && d.Citizen_Category.toLowerCase() === d.Req_Category.toLowerCase())
        },
        {
            param: 'Occupation',
            req: d.Req_Occupation || 'Universal',
            actual: d.Citizen_Occupation || 'Not Set',
            matched: !d.Req_Occupation || (d.Citizen_Occupation && d.Citizen_Occupation.toLowerCase() === d.Req_Occupation.toLowerCase())
        },
        {
            param: 'State / Region',
            req: d.Req_State || 'All India',
            actual: d.Citizen_State || 'Not Set',
            matched: !d.Req_State || (d.Citizen_State && d.Citizen_State.toLowerCase() === d.Req_State.toLowerCase())
        }
    ];

    tableBody.innerHTML = rows.map(r => `
        <tr>
            <td><strong>${r.param}</strong></td>
            <td>${r.req}</td>
            <td>${r.actual}</td>
            <td>
                <span class="badge ${r.matched ? 'badge-approved' : 'badge-rejected'}">
                    ${r.matched ? 'QUALIFIES' : 'MISMATCH'}
                </span>
            </td>
        </tr>
    `).join('');
}

// Action: Mark Under Review
async function markUnderReview() {
    const urlParams = new URLSearchParams(window.location.search);
    const appId = urlParams.get('id');

    if (!confirm(`Mark Application #${appId} as Under Review?`)) return;

    try {
        await GovDB.initializeDatabase();
        await GovDB.updateRecord('Application', {
            Status: 'Under Review',
            Remarks: 'Under active scrutiny by departmental verification officer.'
        }, 'Application_ID = ?', [appId]);

        showToast(`Application #${appId} marked as Under Review.`, 'success');
        loadApplicationForReview();
    } catch (e) {
        showToast('Error updating status: ' + e.message, 'error');
    }
}

// Action: Execute Application Approval & Sanction
async function executeApprovalTransaction() {
    const appId = document.getElementById('actionAppIdApprove')?.value;
    const amount = document.getElementById('approveBenefitAmount')?.value || 0;
    const status = document.getElementById('approvePaymentStatus')?.value || 'Processing';
    const txnRef = document.getElementById('approveTxnRef')?.value.trim() || '';
    const remarks = document.getElementById('approveRemarks')?.value.trim() || 'Application verified and approved in full compliance with departmental criteria.';

    const btn = document.getElementById('confirmApproveBtn');
    btn.disabled = true;
    btn.textContent = 'Sanctioning Application...';

    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        const officerId = user ? (user.officer_id || user.user_id) : 1;

        // 1. Begin Database Transaction
        GovDB.beginTransaction();

        // 2. Retrieve application and check state
        const app = GovDB.fetchOne("SELECT * FROM Application WHERE Application_ID = ?;", [appId]);
        if (!app) {
            throw new Error(`Application #${appId} not found in database.`);
        }
        if (app.Status === 'Approved') {
            throw new Error(`Application #${appId} is already approved.`);
        }

        // 3. Update Application Status to Approved
        await GovDB.updateRecord('Application', {
            Status: 'Approved',
            Remarks: remarks
        }, 'Application_ID = ?', [appId]);

        // 4. Insert Approval Adjudication Order
        const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 19);
        const approvalId = await GovDB.insertRecord('Approval', {
            Application_ID: appId,
            Officer_ID: officerId,
            Approval_Date: nowStr,
            Decision: 'Approved',
            Remarks: remarks
        });

        // 5. Insert Benefit DBT Record if grant amount > 0
        if (amount && Number(amount) > 0) {
            const finalTxn = txnRef || `TXN-DBT-${new Date().getFullYear()}-${Math.floor(10000 + Math.random() * 90000)}`;
            const todayStr = new Date().toISOString().substring(0, 10);
            await GovDB.insertRecord('Benefits', {
                Approval_ID: approvalId,
                Benefit_Amount: parseFloat(amount),
                Benefit_Date: todayStr,
                Payment_Status: status,
                Transaction_Reference: finalTxn
            });
        }

        // 6. Commit Transaction & Persist to Storage
        await GovDB.commitTransaction();

        showToast(`Application #${appId} Approved & Benefit Sanctioned Successfully.`, 'success');
        closeModal('approveModal');
        loadApplicationForReview();

    } catch (err) {
        GovDB.rollbackTransaction();
        console.error('Approval transaction aborted:', err);
        showToast(`Error processing approval: ${err.message}`, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sanction & Disburse Benefit';
    }
}

// Action: Execute Application Rejection
async function executeRejectionTransaction() {
    const appId = document.getElementById('actionAppIdReject')?.value;
    const remarks = document.getElementById('rejectRemarks')?.value.trim();

    if (!remarks) {
        showToast('Formal rejection remarks stating reasons are mandatory.', 'error');
        return;
    }

    const btn = document.getElementById('confirmRejectBtn');
    btn.disabled = true;
    btn.textContent = 'Processing Rejection...';

    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        const officerId = user ? (user.officer_id || user.user_id) : 1;

        GovDB.beginTransaction();

        await GovDB.updateRecord('Application', {
            Status: 'Rejected',
            Remarks: remarks
        }, 'Application_ID = ?', [appId]);

        const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 19);
        await GovDB.insertRecord('Approval', {
            Application_ID: appId,
            Officer_ID: officerId,
            Approval_Date: nowStr,
            Decision: 'Rejected',
            Remarks: remarks
        });

        await GovDB.commitTransaction();

        showToast(`Application #${appId} rejected and decision logged.`, 'success');
        closeModal('rejectModal');
        loadApplicationForReview();

    } catch (err) {
        GovDB.rollbackTransaction();
        showToast('Error recording rejection: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Confirm Rejection';
    }
}

// =============================================================================
// 4. BENEFITS DISBURSEMENT MANAGER
// =============================================================================
async function loadOfficerBenefits() {
    const tableBody = document.getElementById('officerBenefitsTable');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="8" class="empty-state">Loading disbursement ledger...</td></tr>';

    try {
        await GovDB.initializeDatabase();

        officerBenefitsData = GovDB.fetchAll(`
            SELECT 
                b.Benefit_ID,
                b.Approval_ID,
                b.Benefit_Amount,
                b.Benefit_Date,
                b.Payment_Status,
                b.Transaction_Reference,
                c.Name AS Citizen_Name,
                c.District AS Citizen_District,
                c.State AS Citizen_State,
                s.Scheme_Name,
                d.Department_Name
            FROM Benefits b
            INNER JOIN Approval ap ON b.Approval_ID = ap.Approval_ID
            INNER JOIN Application a ON ap.Application_ID = a.Application_ID
            INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            ORDER BY b.Benefit_ID DESC;
        `);

        // Compute summary counters
        let totalAmt = 0;
        let paidAmt = 0;
        let procAmt = 0;

        officerBenefitsData.forEach(b => {
            const amt = Number(b.Benefit_Amount) || 0;
            totalAmt += amt;
            if (b.Payment_Status === 'Paid') paidAmt += amt;
            if (b.Payment_Status === 'Processing') procAmt += amt;
        });

        const setTxt = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        setTxt('benTotalCount', officerBenefitsData.length);
        setTxt('benTotalAmount', '₹' + totalAmt.toLocaleString('en-IN'));
        setTxt('benPaidAmount', '₹' + paidAmt.toLocaleString('en-IN'));
        setTxt('benProcessingAmount', '₹' + procAmt.toLocaleString('en-IN'));

        renderOfficerBenefitsTable(officerBenefitsData);

    } catch (e) {
        console.error('Error loading benefits:', e);
        tableBody.innerHTML = `<tr><td colspan="8" class="alert alert-danger">Error loading ledger: ${e.message}</td></tr>`;
    }
}

function renderOfficerBenefitsTable(list) {
    const tableBody = document.getElementById('officerBenefitsTable');
    if (!tableBody) return;

    if (!list || list.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" class="empty-state">No benefit records exist yet.</td></tr>';
        return;
    }

    tableBody.innerHTML = list.map(b => {
        let badgeClass = 'badge-pending';
        if (b.Payment_Status === 'Processing') badgeClass = 'badge-review';
        if (b.Payment_Status === 'Paid') badgeClass = 'badge-approved';
        if (b.Payment_Status === 'Failed') badgeClass = 'badge-rejected';

        return `
            <tr>
                <td><strong>#${b.Benefit_ID}</strong></td>
                <td>
                    <div style="font-weight: 600;">${b.Citizen_Name}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">${b.Citizen_District || ''}, ${b.Citizen_State || ''}</div>
                </td>
                <td>
                    <div>${b.Scheme_Name}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">${b.Department_Name}</div>
                </td>
                <td><strong style="color: var(--success); font-size: 1rem;">₹${Number(b.Benefit_Amount).toLocaleString('en-IN')}</strong></td>
                <td>${b.Benefit_Date}</td>
                <td><span class="badge ${badgeClass}">${b.Payment_Status}</span></td>
                <td><code style="font-size: 0.8rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">${b.Transaction_Reference || 'Pending'}</code></td>
                <td>
                    <button onclick="openBenefitStatusModal(${b.Benefit_ID}, '${b.Payment_Status}', '${b.Transaction_Reference || ''}')" class="btn btn-secondary btn-sm">
                        Update Status
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function openBenefitStatusModal(benefitId, currentStatus, currentTxn) {
    document.getElementById('updateBenefitId').value = benefitId;
    document.getElementById('updatePaymentStatus').value = currentStatus;
    document.getElementById('updateTxnRef').value = currentTxn;
    openModal('benefitStatusModal');
}

async function saveBenefitStatusUpdate() {
    const benefitId = document.getElementById('updateBenefitId')?.value;
    const status = document.getElementById('updatePaymentStatus')?.value;
    const txnRef = document.getElementById('updateTxnRef')?.value.trim();

    try {
        await GovDB.initializeDatabase();
        await GovDB.updateRecord('Benefits', {
            Payment_Status: status,
            Transaction_Reference: txnRef
        }, 'Benefit_ID = ?', [benefitId]);

        showToast(`Benefit #${benefitId} updated successfully!`, 'success');
        closeModal('benefitStatusModal');
        loadOfficerBenefits();
    } catch (e) {
        showToast('Error updating benefit: ' + e.message, 'error');
    }
}
