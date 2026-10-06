/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/applications.js
 */

let currentApplySchemeId = null;

// 1. Load All Applications for the Logged-in Citizen
async function loadCitizenApplications() {
    const tableBody = document.getElementById('citizenApplicationsTable') || document.getElementById('applicationsListTable');
    if (!tableBody) return;

    tableBody.innerHTML = '<tr><td colspan="7" class="empty-state">Querying applications from SQLite database...</td></tr>';

    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        if (!user || !user.citizen_id) return;

        const apps = GovDB.fetchAll(`
            SELECT 
                a.Application_ID,
                s.Scheme_Name,
                d.Department_Name,
                s.Benefit_Type,
                a.Application_Date,
                a.Status,
                a.Remarks,
                b.Benefit_Amount,
                b.Payment_Status
            FROM Application a
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
            LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
            WHERE a.Citizen_ID = ?
            ORDER BY a.Application_Date DESC;
        `, [user.citizen_id]);

        if (apps.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3>No Applications Filed</h3>
                        <p>You have not submitted applications for any government welfare schemes yet.</p>
                        <a href="eligibility.html" class="btn btn-primary btn-sm">Find Eligible Schemes</a>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = apps.map(app => {
            let badgeClass = 'badge-pending';
            if (app.Status === 'Under Review') badgeClass = 'badge-review';
            if (app.Status === 'Approved') badgeClass = 'badge-approved';
            if (app.Status === 'Rejected') badgeClass = 'badge-rejected';

            let benefitInfo = '<span style="color: #94a3b8;">-</span>';
            if (app.Benefit_Amount && Number(app.Benefit_Amount) > 0) {
                benefitInfo = `<strong style="color: var(--primary);">₹${Number(app.Benefit_Amount).toLocaleString('en-IN')}</strong> (${app.Payment_Status || 'Pending'})`;
            }

            const appDate = app.Application_Date ? new Date(app.Application_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';

            return `
                <tr>
                    <td><strong>#APP-${app.Application_ID}</strong></td>
                    <td>
                        <div style="font-weight: 600; color: var(--primary-dark);">${app.Scheme_Name}</div>
                        <div style="font-size: 0.76rem; color: var(--text-muted);">${app.Department_Name}</div>
                    </td>
                    <td><span class="badge badge-dept" style="font-size: 0.74rem;">${app.Benefit_Type}</span></td>
                    <td>${appDate}</td>
                    <td><span class="badge ${badgeClass}">${app.Status}</span></td>
                    <td>${benefitInfo}</td>
                    <td>
                        <a href="application-details.html?id=${app.Application_ID}" class="btn btn-secondary btn-sm">View Timeline</a>
                    </td>
                </tr>
            `;
        }).join('');

    } catch (err) {
        console.error('Error loading applications:', err);
        tableBody.innerHTML = `<tr><td colspan="7" class="alert alert-danger">Error querying SQLite: ${err.message}</td></tr>`;
    }
}

// 2. Open Application Submission Modal
async function openApplyModal(schemeId) {
    currentApplySchemeId = schemeId;
    const user = getCurrentUser();
    if (!user || user.role !== 'CITIZEN') {
        alert('Please sign in as a Citizen to apply for welfare schemes.');
        window.location.href = '../login.html';
        return;
    }

    try {
        await GovDB.initializeDatabase();
        const scheme = GovDB.fetchOne(`
            SELECT s.*, d.Department_Name 
            FROM Scheme s 
            INNER JOIN Department d ON s.Department_ID = d.Department_ID 
            WHERE s.Scheme_ID = ?;
        `, [schemeId]);

        if (!scheme) {
            showToast('Scheme not found in database.', 'error');
            return;
        }

        const citizen = GovDB.fetchOne("SELECT * FROM Citizen WHERE Citizen_ID = ?;", [user.citizen_id]);

        // Support both applyModal and applySchemeModal element IDs
        const titleEl = document.getElementById('applySchemeName') || document.getElementById('applyModalSchemeTitle');
        const deptEl = document.getElementById('applyModalDept');
        const benefitEl = document.getElementById('applyModalBenefit');
        const citizenNameEl = document.getElementById('applyModalCitizenName');
        const citizenDetailsEl = document.getElementById('applyModalCitizenDetails');
        const schemeIdEl = document.getElementById('applySchemeId');

        if (titleEl) titleEl.textContent = scheme.Scheme_Name;
        if (schemeIdEl) schemeIdEl.value = scheme.Scheme_ID;
        if (deptEl) deptEl.textContent = scheme.Department_Name;
        if (benefitEl) benefitEl.textContent = `${scheme.Benefit_Type} - ${scheme.Benefit_Description}`;
        if (citizenNameEl) citizenNameEl.textContent = citizen ? citizen.Name : (user.name || 'Applicant');
        if (citizenDetailsEl) {
            if (citizen) {
                citizenDetailsEl.textContent = `Age: ${citizen.Age ?? 'N/A'}, Category: ${citizen.Category ?? 'N/A'}, Income: ₹${Number(citizen.Income || 0).toLocaleString('en-IN')}, Occupation: ${citizen.Occupation ?? 'N/A'}`;
            } else {
                citizenDetailsEl.textContent = 'Profile details unavailable.';
            }
        }

        const remarksInput = document.getElementById('applyRemarks') || document.getElementById('applyRemarksInput');
        if (remarksInput) remarksInput.value = '';

        const modalId = document.getElementById('applyModal') ? 'applyModal' : 'applySchemeModal';
        openModal(modalId);
    } catch (err) {
        console.error('Error opening apply modal:', err);
        showToast('Error opening application modal: ' + err.message, 'error');
    }
}

// 3. Confirm and Submit Application into SQLite WASM
async function submitSchemeApplication() {
    if (!currentApplySchemeId) {
        const idVal = document.getElementById('applySchemeId')?.value;
        if (idVal) currentApplySchemeId = parseInt(idVal, 10);
    }
    if (!currentApplySchemeId) {
        showToast('No scheme selected for application.', 'error');
        return;
    }
    const user = getCurrentUser();
    if (!user || !user.citizen_id) {
        showToast('User session expired. Please sign in again.', 'error');
        return;
    }

    const submitBtn = document.getElementById('confirmApplyBtn');
    const originalText = submitBtn ? submitBtn.textContent : 'Confirm';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verifying and Submitting...';
    }

    try {
        await GovDB.initializeDatabase();
        const citizen = GovDB.fetchOne("SELECT * FROM Citizen WHERE Citizen_ID = ?;", [user.citizen_id]);
        const scheme = GovDB.fetchOne("SELECT * FROM Scheme WHERE Scheme_ID = ?;", [currentApplySchemeId]);

        if (!scheme) {
            showToast('Scheme not found in database.', 'error');
            return;
        }

        // Pre-check 1: Profile Completeness
        if (!citizen || citizen.Age === null || citizen.Income === null || !citizen.Category || !citizen.Occupation) {
            showToast('Profile incomplete! Please complete your Age, Category, Income, and Occupation in your profile before applying.', 'error');
            return;
        }

        // Pre-check 2: Scheme Active and not expired
        if (scheme.Status !== 'Active') {
            showToast('This scheme is currently inactive.', 'error');
            return;
        }
        if (scheme.Last_Date && new Date(scheme.Last_Date) < new Date(new Date().setHours(0,0,0,0))) {
            showToast('The application deadline for this scheme has passed.', 'error');
            return;
        }

        // Pre-check 3: Duplicate Application constraint
        const duplicate = GovDB.fetchOne("SELECT Application_ID FROM Application WHERE Citizen_ID = ? AND Scheme_ID = ?;", [user.citizen_id, currentApplySchemeId]);
        if (duplicate) {
            showToast('Duplicate Application: You have already submitted an application for this scheme (#APP-' + duplicate.Application_ID + ').', 'warning');
            return;
        }

        // Pre-check 4: Criteria Match Verification via SQL
        const crit = GovDB.fetchOne("SELECT * FROM Eligibility_Criteria WHERE Scheme_ID = ?;", [currentApplySchemeId]);
        if (crit) {
            if (crit.Min_Age !== null && citizen.Age < crit.Min_Age) {
                showToast(`Ineligible: Minimum age required is ${crit.Min_Age} yrs.`, 'error');
                return;
            }
            if (crit.Max_Age !== null && citizen.Age > crit.Max_Age) {
                showToast(`Ineligible: Maximum age allowed is ${crit.Max_Age} yrs.`, 'error');
                return;
            }
            if (crit.Income_Limit !== null && citizen.Income > crit.Income_Limit) {
                showToast(`Ineligible: Annual income exceeds limit of ₹${Number(crit.Income_Limit).toLocaleString('en-IN')}.`, 'error');
                return;
            }
            if (crit.Category !== null && citizen.Category && citizen.Category.toLowerCase() !== crit.Category.toLowerCase()) {
                showToast(`Ineligible: Reserved for category '${crit.Category}'.`, 'error');
                return;
            }
            if (crit.Gender !== null && citizen.Gender && citizen.Gender.toLowerCase() !== crit.Gender.toLowerCase()) {
                showToast(`Ineligible: Reserved for gender '${crit.Gender}'.`, 'error');
                return;
            }
            if (crit.Occupation !== null && citizen.Occupation && citizen.Occupation.toLowerCase() !== crit.Occupation.toLowerCase()) {
                showToast(`Ineligible: Reserved for occupation '${crit.Occupation}'.`, 'error');
                return;
            }
        }

        const remarksInput = document.getElementById('applyRemarks') || document.getElementById('applyRemarksInput');
        const remarks = (remarksInput?.value || '').trim() || 'Citizen self-enrolled via portal';

        // Insert into Application table
        const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 19);
        const newAppId = await GovDB.insertRecord('Application', {
            Citizen_ID: user.citizen_id,
            Scheme_ID: currentApplySchemeId,
            Application_Date: nowStr,
            Status: 'Pending',
            Remarks: remarks
        });

        closeModal('applyModal');
        closeModal('applySchemeModal');
        showToast(`Application #APP-${newAppId} submitted successfully!`, 'success');

        // Refresh applications list or eligibility view if on those pages
        if (typeof loadCitizenApplications === 'function') loadCitizenApplications();
        if (typeof checkCitizenEligibility === 'function') checkCitizenEligibility();
        if (typeof loadSchemesCatalog === 'function') loadSchemesCatalog(false);

    } catch (err) {
        console.error('Submission error:', err);
        showToast('Application submission error: ' + err.message, 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }
}

// Global Aliases for Window / Onclick Callbacks
window.openApplyModal = openApplyModal;
window.submitSchemeApplication = submitSchemeApplication;
window.submitApplication = submitSchemeApplication;

// 4. Load Detailed Application Dossier & Timeline
async function loadApplicationDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const appId = urlParams.get('id');

    if (!appId) {
        showToast('Missing Application ID in request.', 'error');
        return;
    }

    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        const isOfficer = user && user.role === 'OFFICER';

        // Query application with citizen, scheme, department, approval, and benefit details
        const app = GovDB.fetchOne(`
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
                ap.Approval_ID,
                ap.Approval_Date,
                ap.Decision,
                ap.Remarks AS Approval_Remarks,
                o.Name AS Officer_Name,
                o.Designation AS Officer_Designation,
                b.Benefit_ID,
                b.Benefit_Amount,
                b.Benefit_Date,
                b.Payment_Status,
                b.Transaction_Reference
            FROM Application a
            INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
            LEFT JOIN Officer o ON ap.Officer_ID = o.Officer_ID
            LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
            WHERE a.Application_ID = ?;
        `, [appId]);

        if (!app) {
            showToast('Application dossier not found.', 'error');
            return;
        }

        // Fill dossier fields in DOM
        const setText = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val || '-';
        };

        setText('dossierAppId', `#APP-${app.Application_ID}`);
        setText('dossierSchemeName', app.Scheme_Name);
        setText('dossierDeptName', app.Department_Name);
        setText('dossierBenefitType', app.Benefit_Type);
        setText('dossierBenefitDesc', app.Benefit_Description);
        setText('dossierAppDate', app.Application_Date ? new Date(app.Application_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-');
        setText('dossierRemarks', app.Application_Remarks);

        // Citizen fields
        setText('dossierCitizenName', app.Citizen_Name);
        setText('dossierCitizenEmail', app.Citizen_Email);
        setText('dossierCitizenPhone', app.Citizen_Phone);
        setText('dossierCitizenAge', app.Citizen_Age ? `${app.Citizen_Age} yrs` : '-');
        setText('dossierCitizenGender', app.Citizen_Gender);
        setText('dossierCitizenCategory', app.Citizen_Category);
        setText('dossierCitizenIncome', app.Citizen_Income ? `₹${Number(app.Citizen_Income).toLocaleString('en-IN')}` : '-');
        setText('dossierCitizenOccupation', app.Citizen_Occupation);
        setText('dossierCitizenEducation', app.Citizen_Education);
        setText('dossierCitizenLocation', `${app.Citizen_District || ''}, ${app.Citizen_State || ''}`);
        setText('dossierCitizenAddress', app.Citizen_Address);

        // Status badge
        const statusBadge = document.getElementById('dossierStatusBadge');
        if (statusBadge) {
            let bClass = 'badge-pending';
            if (app.Status === 'Under Review') bClass = 'badge-review';
            if (app.Status === 'Approved') bClass = 'badge-approved';
            if (app.Status === 'Rejected') bClass = 'badge-rejected';
            statusBadge.className = `badge ${bClass}`;
            statusBadge.textContent = app.Status;
        }

        // Timeline visualization
        renderTimelineSteps(app);

        // Financial benefit section
        const benefitSection = document.getElementById('dossierBenefitSection');
        if (benefitSection) {
            if (app.Benefit_ID) {
                benefitSection.style.display = 'block';
                setText('dossierBenefitAmount', `₹${Number(app.Benefit_Amount).toLocaleString('en-IN')}`);
                setText('dossierBenefitStatus', app.Payment_Status);
                setText('dossierBenefitDate', app.Benefit_Date ? new Date(app.Benefit_Date).toLocaleDateString('en-IN') : '-');
                setText('dossierTxnRef', app.Transaction_Reference || 'Pending Reference');
            } else {
                benefitSection.style.display = 'none';
            }
        }

    } catch (err) {
        console.error('Error loading application details:', err);
    }
}

// Helper: Render Timeline Steps
function renderTimelineSteps(app) {
    const timelineEl = document.getElementById('dossierTimeline');
    if (!timelineEl) return;

    const isSubmitted = true;
    const isUnderReview = app.Status === 'Under Review' || app.Status === 'Approved' || app.Status === 'Rejected';
    const isDecided = app.Status === 'Approved' || app.Status === 'Rejected';
    const isBenefitPaid = app.Payment_Status === 'Paid';

    const renderStep = (title, desc, isDone, isActive = false, dateStr = null) => `
        <div class="timeline-step ${isDone ? 'completed' : (isActive ? 'active' : '')}" style="display: flex; gap: 14px; margin-bottom: 20px;">
            <div style="display: flex; flex-direction: column; align-items: center;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: ${isDone ? '#16a34a' : (isActive ? '#f59e0b' : '#cbd5e1')}; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                    ${isDone ? '✓' : '•'}
                </div>
                <div style="width: 2px; flex: 1; background: #e2e8f0; margin-top: 4px;"></div>
            </div>
            <div style="flex: 1; padding-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <strong style="color: var(--primary-dark); font-size: 0.95rem;">${title}</strong>
                    ${dateStr ? `<span style="font-size: 0.76rem; color: var(--text-muted);">${dateStr}</span>` : ''}
                </div>
                <div style="font-size: 0.84rem; color: var(--text-muted); margin-top: 2px;">${desc}</div>
            </div>
        </div>
    `;

    let html = '';
    html += renderStep('Application Submitted', 'Citizen submitted application dossier via online portal.', isSubmitted, false, app.Application_Date ? new Date(app.Application_Date).toLocaleDateString('en-IN') : null);
    html += renderStep('Departmental Scrutiny', app.Status === 'Pending' ? 'Queue awaiting departmental officer evaluation.' : 'Application dossier scrutinized against eligibility rules.', isUnderReview, app.Status === 'Pending');
    html += renderStep(`Adjudication Decision: ${app.Decision || app.Status}`, app.Approval_Remarks || (isDecided ? 'Formal order logged.' : 'Awaiting officer sanction order.'), isDecided, app.Status === 'Under Review', app.Approval_Date ? new Date(app.Approval_Date).toLocaleDateString('en-IN') : null);
    if (app.Benefit_Type === 'Monetary' || app.Benefit_ID) {
        html += renderStep('Direct Benefit Transfer (DBT)', app.Payment_Status === 'Paid' ? `Sanctioned grant of ₹${Number(app.Benefit_Amount).toLocaleString('en-IN')} disbursed (Ref: ${app.Transaction_Reference}).` : 'Sanctioned benefit in payment processing queue.', isBenefitPaid, !isBenefitPaid && isDecided);
    }

    timelineEl.innerHTML = html;
}
