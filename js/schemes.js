/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/schemes.js
 */

let allSchemesData = [];

// Fetch and render scheme catalog from SQLite WASM
async function loadSchemesCatalog(isPublic = false) {
    const listContainer = document.getElementById('schemesCatalogContainer');
    if (!listContainer) return;

    listContainer.innerHTML = '<div class="empty-state"><p>Loading official welfare schemes...</p></div>';

    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        const citizenId = (user && user.role === 'CITIZEN') ? user.citizen_id : null;

        // Query active schemes with department and criteria details
        const sql = `
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
                e.Criteria_ID,
                e.Min_Age,
                e.Max_Age,
                e.Income_Limit,
                e.Category AS Criteria_Category,
                e.Gender AS Criteria_Gender,
                e.Occupation AS Criteria_Occupation,
                e.Education AS Criteria_Education,
                e.State AS Criteria_State,
                e.District AS Criteria_District,
                (SELECT a.Status FROM Application a WHERE a.Scheme_ID = s.Scheme_ID AND a.Citizen_ID = ?) AS Citizen_Application_Status
            FROM Scheme s
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
            WHERE s.Status = 'Active'
            ORDER BY s.Scheme_Name ASC;
        `;

        allSchemesData = GovDB.fetchAll(sql, [citizenId]);
        renderSchemes(allSchemesData, isPublic);
        populateDepartmentFilter(allSchemesData);

    } catch (err) {
        console.error('Error querying schemes:', err);
        listContainer.innerHTML = `<div class="alert alert-danger">Error loading schemes: ${err.message}</div>`;
    }
}

// Render scheme cards
function renderSchemes(schemes, isPublic = false) {
    const container = document.getElementById('schemesCatalogContainer');
    if (!container) return;

    if (!schemes || schemes.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon">🔍</div>
                <h3>No Schemes Found</h3>
                <p>No government schemes matched your filter criteria. Try adjusting the search term or department filters.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = schemes.map(s => {
        const hasApplied = s.Citizen_Application_Status !== null && s.Citizen_Application_Status !== undefined;
        let actionBtn = '';

        if (isPublic) {
            actionBtn = `<a href="login.html" class="btn btn-outline btn-sm">Login to Apply</a>`;
        } else if (hasApplied) {
            actionBtn = `<span class="badge badge-approved">Applied (${s.Citizen_Application_Status})</span>`;
        } else {
            actionBtn = `<button class="btn btn-primary btn-sm" onclick="openApplyModal(${s.Scheme_ID})">Apply Now</button>`;
        }

        const deadline = s.Last_Date ? new Date(s.Last_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Open Year-Round';

        return `
            <div class="card scheme-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px;">
                        <span class="badge badge-dept" style="background: rgba(30,58,138,0.1); color: var(--primary); font-size: 0.74rem;">${s.Department_Name}</span>
                        <span class="badge badge-active" style="font-size: 0.72rem;">${s.Benefit_Type}</span>
                    </div>
                    <h3 style="font-size: 1.12rem; margin-bottom: 8px; color: var(--primary-dark); line-height: 1.35;">${s.Scheme_Name}</h3>
                    <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        ${s.Description}
                    </p>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 10px; margin-bottom: 14px; font-size: 0.84rem;">
                        <strong style="color: var(--text-main);">Benefit:</strong>
                        <div style="color: var(--secondary-dark); font-weight: 500; margin-top: 2px;">${s.Benefit_Description}</div>
                    </div>
                </div>

                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 14px; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                        <span>📅 Last Date: <strong>${deadline}</strong></span>
                        <button class="btn-link" style="color: var(--primary); font-weight: 600; cursor: pointer; border: none; background: none;" onclick="showSchemeModal(${s.Scheme_ID})">Details</button>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        ${actionBtn}
                        <button class="btn btn-secondary btn-sm" onclick="showSchemeModal(${s.Scheme_ID})">View Rules</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Populate department filter dropdown
function populateDepartmentFilter(schemes) {
    const filterSelect = document.getElementById('filterDepartment');
    if (!filterSelect) return;

    const currentVal = filterSelect.value;
    const depts = [...new Set(schemes.map(s => s.Department_Name))].sort();

    filterSelect.innerHTML = '<option value="">All Departments</option>' +
        depts.map(d => `<option value="${d}" ${d === currentVal ? 'selected' : ''}>${d}</option>`).join('');
}

// Client-Side Search and Filter Handler
function filterSchemes() {
    const searchVal = (document.getElementById('searchScheme')?.value || '').toLowerCase().trim();
    const deptVal = document.getElementById('filterDepartment')?.value || '';
    const benefitVal = document.getElementById('filterBenefitType')?.value || document.getElementById('filterType')?.value || '';

    const isPublic = !window.location.pathname.includes('/citizen/');

    const filtered = allSchemesData.filter(s => {
        const matchesSearch = !searchVal || 
            s.Scheme_Name.toLowerCase().includes(searchVal) || 
            s.Description.toLowerCase().includes(searchVal) || 
            s.Department_Name.toLowerCase().includes(searchVal);
        
        const matchesDept = !deptVal || s.Department_Name === deptVal;
        const matchesBenefit = !benefitVal || s.Benefit_Type === benefitVal;

        return matchesSearch && matchesDept && matchesBenefit;
    });

    renderSchemes(filtered, isPublic);
}

// Modal: Detailed View of Scheme & Criteria
function showSchemeModal(schemeId) {
    const scheme = allSchemesData.find(s => s.Scheme_ID === schemeId);
    if (!scheme) return;

    const titleEl = document.getElementById('schemeModalTitle') || document.getElementById('modalSchemeTitle');
    const contentEl = document.getElementById('schemeModalContent');
    const footerEl = document.getElementById('schemeModalFooter');

    if (titleEl) titleEl.textContent = scheme.Scheme_Name;

    // Criteria format
    const formatCrit = (label, val, unit = '') => {
        if (val === null || val === undefined || val === '') {
            return `<li><strong>${label}:</strong> <span style="color: #64748b;">Not Restricted (Universal)</span></li>`;
        }
        return `<li><strong>${label}:</strong> <span style="color: #1e3a8a; font-weight: 600;">${unit}${val}</span></li>`;
    };

    let critHtml = '<ul style="margin: 0; padding-left: 20px; line-height: 1.7; font-size: 0.88rem;">';
    critHtml += formatCrit('Minimum Age', scheme.Min_Age ? `${scheme.Min_Age} yrs` : null);
    critHtml += formatCrit('Maximum Age', scheme.Max_Age ? `${scheme.Max_Age} yrs` : null);
    critHtml += formatCrit('Income Ceiling', scheme.Income_Limit ? Number(scheme.Income_Limit).toLocaleString('en-IN') : null, '₹');
    critHtml += formatCrit('Eligible Category', scheme.Criteria_Category);
    critHtml += formatCrit('Gender Requirement', scheme.Criteria_Gender);
    critHtml += formatCrit('Occupation Requirement', scheme.Criteria_Occupation);
    critHtml += formatCrit('Minimum Education', scheme.Criteria_Education);
    critHtml += formatCrit('Domicile State', scheme.Criteria_State);
    critHtml += formatCrit('District', scheme.Criteria_District);
    critHtml += '</ul>';

    if (contentEl) {
        const deadline = scheme.Last_Date ? new Date(scheme.Last_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Open Year-Round';
        contentEl.innerHTML = `
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                    <span class="badge badge-dept" style="background: rgba(30,58,138,0.1); color: var(--primary);">${scheme.Department_Name}</span>
                    <span class="badge badge-active">${scheme.Benefit_Type}</span>
                    <span class="badge ${scheme.Status === 'Active' ? 'badge-approved' : 'badge-rejected'}">${scheme.Status}</span>
                </div>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); margin-bottom: 1rem;">${scheme.Description}</p>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 12px; margin-bottom: 1rem;">
                    <strong style="color: var(--text-main);">Benefit Entitlement:</strong>
                    <div style="color: var(--secondary-dark); font-weight: 600; margin-top: 4px; font-size: 0.95rem;">${scheme.Benefit_Description}</div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong style="color: var(--text-main); display: block; margin-bottom: 6px;">Relational Criteria Requirements (Eligibility Rules):</strong>
                    <div style="background: #f1f5f9; padding: 12px; border-radius: var(--radius-sm);">
                        ${critHtml}
                    </div>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid #e2e8f0; padding-top: 10px;">
                    📅 Application Deadline: <strong>${deadline}</strong>
                </div>
            </div>
        `;
    }

    if (footerEl) {
        const user = typeof getCurrentUser === 'function' ? getCurrentUser() : null;
        const hasApplied = scheme.Citizen_Application_Status !== null && scheme.Citizen_Application_Status !== undefined;
        let actionBtn = '';
        if (!user || user.role !== 'CITIZEN') {
            actionBtn = `<a href="login.html" class="btn btn-primary btn-sm">Login to Apply</a>`;
        } else if (hasApplied) {
            actionBtn = `<span class="badge badge-approved">Applied (${scheme.Citizen_Application_Status})</span>`;
        } else {
            actionBtn = `<button type="button" class="btn btn-primary btn-sm" onclick="closeModal('schemeModal'); openApplyModal(${scheme.Scheme_ID});">Apply Now</button>`;
        }
        footerEl.innerHTML = `
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('schemeModal')">Close</button>
            ${actionBtn}
        `;
    }

    const modalId = document.getElementById('schemeModal') ? 'schemeModal' : 'schemeDetailModal';
    openModal(modalId);
}

window.filterSchemes = filterSchemes;
window.showSchemeModal = showSchemeModal;
