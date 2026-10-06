/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/eligibility.js
 */

let evaluatedSchemes = [];

// Evaluates citizen eligibility via parameterized SQL query in SQLite WASM
async function checkCitizenEligibility() {
    const resultsContainer = document.getElementById('eligibilityResultsContainer');
    if (!resultsContainer) return;

    resultsContainer.innerHTML = '<div class="empty-state"><p>Executing dynamic eligibility evaluation query against SQLite relational engine...</p></div>';

    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        if (!user || !user.citizen_id) {
            resultsContainer.innerHTML = '<div class="alert alert-warning">Please sign in as a Citizen to evaluate your scheme eligibility.</div>';
            return;
        }

        const citizen = GovDB.fetchOne("SELECT * FROM Citizen WHERE Citizen_ID = ?;", [user.citizen_id]);
        if (!citizen) return;

        // Render Citizen Demographic Snapshot
        renderCitizenDemographicPills(citizen);

        // Core Dynamic Eligibility Evaluation Query
        const sql = `
            SELECT 
                s.Scheme_ID,
                s.Scheme_Name,
                s.Description,
                s.Benefit_Type,
                s.Benefit_Description,
                s.Last_Date,
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
                (SELECT a.Status FROM Application a WHERE a.Scheme_ID = s.Scheme_ID AND a.Citizen_ID = ?) AS Citizen_Application_Status,
                (CASE WHEN (e.Min_Age IS NULL OR (? IS NOT NULL AND ? >= e.Min_Age)) THEN 1 ELSE 0 END) AS Met_Min_Age,
                (CASE WHEN (e.Max_Age IS NULL OR (? IS NOT NULL AND ? <= e.Max_Age)) THEN 1 ELSE 0 END) AS Met_Max_Age,
                (CASE WHEN (e.Income_Limit IS NULL OR (? IS NOT NULL AND ? <= e.Income_Limit)) THEN 1 ELSE 0 END) AS Met_Income,
                (CASE WHEN (e.Category IS NULL OR (? IS NOT NULL AND LOWER(?) = LOWER(e.Category))) THEN 1 ELSE 0 END) AS Met_Category,
                (CASE WHEN (e.Gender IS NULL OR (? IS NOT NULL AND LOWER(?) = LOWER(e.Gender))) THEN 1 ELSE 0 END) AS Met_Gender,
                (CASE WHEN (e.Occupation IS NULL OR (? IS NOT NULL AND LOWER(?) = LOWER(e.Occupation))) THEN 1 ELSE 0 END) AS Met_Occupation,
                (CASE WHEN (e.Education IS NULL OR (? IS NOT NULL AND LOWER(?) = LOWER(e.Education))) THEN 1 ELSE 0 END) AS Met_Education,
                (CASE WHEN (e.State IS NULL OR (? IS NOT NULL AND LOWER(?) = LOWER(e.State))) THEN 1 ELSE 0 END) AS Met_State,
                (CASE WHEN (e.District IS NULL OR (? IS NOT NULL AND LOWER(?) = LOWER(e.District))) THEN 1 ELSE 0 END) AS Met_District
            FROM Scheme s
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
            WHERE s.Status = 'Active'
              AND (s.Last_Date IS NULL OR s.Last_Date >= DATE('now'))
            ORDER BY s.Scheme_Name ASC;
        `;

        const params = [
            citizen.Citizen_ID,
            citizen.Age, citizen.Age,
            citizen.Age, citizen.Age,
            citizen.Income, citizen.Income,
            citizen.Category, citizen.Category,
            citizen.Gender, citizen.Gender,
            citizen.Occupation, citizen.Occupation,
            citizen.Education, citizen.Education,
            citizen.State, citizen.State,
            citizen.District, citizen.District
        ];

        const rows = GovDB.fetchAll(sql, params);

        evaluatedSchemes = rows.map(r => {
            const isEligible = (
                r.Met_Min_Age === 1 &&
                r.Met_Max_Age === 1 &&
                r.Met_Income === 1 &&
                r.Met_Category === 1 &&
                r.Met_Gender === 1 &&
                r.Met_Occupation === 1 &&
                r.Met_Education === 1 &&
                r.Met_State === 1 &&
                r.Met_District === 1
            );

            // Construct explanations of reasons if ineligible
            const reasons = [];
            if (r.Met_Min_Age === 0) reasons.push(`Requires minimum age of ${r.Min_Age} yrs (Citizen is ${citizen.Age || 'unknown'})`);
            if (r.Met_Max_Age === 0) reasons.push(`Requires maximum age of ${r.Max_Age} yrs (Citizen is ${citizen.Age || 'unknown'})`);
            if (r.Met_Income === 0) reasons.push(`Annual income ceiling is ₹${Number(r.Income_Limit).toLocaleString('en-IN')} (Citizen declared ₹${Number(citizen.Income || 0).toLocaleString('en-IN')})`);
            if (r.Met_Category === 0) reasons.push(`Reserved for '${r.Criteria_Category}' category (Citizen is '${citizen.Category || 'unknown'}')`);
            if (r.Met_Gender === 0) reasons.push(`Reserved for '${r.Criteria_Gender}' applicants (Citizen is '${citizen.Gender || 'unknown'}')`);
            if (r.Met_Occupation === 0) reasons.push(`Requires occupation '${r.Criteria_Occupation}' (Citizen is '${citizen.Occupation || 'unknown'}')`);
            if (r.Met_Education === 0) reasons.push(`Requires education '${r.Criteria_Education}' (Citizen is '${citizen.Education || 'unknown'}')`);
            if (r.Met_State === 0) reasons.push(`Restricted to domicile of '${r.Criteria_State}' (Citizen is '${citizen.State || 'unknown'}')`);
            if (r.Met_District === 0) reasons.push(`Restricted to district '${r.Criteria_District}' (Citizen is '${citizen.District || 'unknown'}')`);

            return {
                ...r,
                is_eligible: isEligible,
                reasons: reasons
            };
        });

        // Summary counts
        const eligibleCount = evaluatedSchemes.filter(s => s.is_eligible).length;
        const totalEvaluated = evaluatedSchemes.length;

        const summaryCountEl = document.getElementById('eligibleSchemesCount');
        if (summaryCountEl) summaryCountEl.textContent = `${eligibleCount} of ${totalEvaluated} Schemes Eligible`;

        renderEligibilityCards(evaluatedSchemes);

    } catch (err) {
        console.error('Eligibility check error:', err);
        resultsContainer.innerHTML = `<div class="alert alert-danger">SQL Query Evaluation Error: ${err.message}</div>`;
    }
}

// Render citizen profile snapshot pills
function renderCitizenDemographicPills(c) {
    const container = document.getElementById('citizenDemographicPills');
    if (!container) return;

    const pills = [
        { label: 'Age', val: c.Age ? `${c.Age} yrs` : 'Not Set' },
        { label: 'Gender', val: c.Gender || 'Not Set' },
        { label: 'Category', val: c.Category || 'Not Set' },
        { label: 'Annual Income', val: c.Income ? `₹${Number(c.Income).toLocaleString('en-IN')}` : 'Not Set' },
        { label: 'Occupation', val: c.Occupation || 'Not Set' },
        { label: 'Education', val: c.Education || 'Not Set' },
        { label: 'State', val: c.State || 'Not Set' },
        { label: 'District', val: c.District || 'Not Set' }
    ];

    container.innerHTML = pills.map(p => `
        <span style="background: rgba(30,58,138,0.08); border: 1px solid rgba(30,58,138,0.2); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; color: var(--primary);">
            <strong>${p.label}:</strong> ${p.val}
        </span>
    `).join('');
}

// Render cards
function renderEligibilityCards(schemes) {
    const container = document.getElementById('eligibilityResultsContainer');
    if (!container) return;

    if (!schemes || schemes.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No Welfare Schemes Evaluated</h3>
                <p>No active government welfare schemes were found in the database.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = schemes.map(s => {
        const hasApplied = s.Citizen_Application_Status !== null && s.Citizen_Application_Status !== undefined;
        let actionArea = '';

        if (hasApplied) {
            actionArea = `
                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 14px;">
                    <span class="badge badge-approved" style="font-size: 0.82rem;">✅ Application Submitted (${s.Citizen_Application_Status})</span>
                    <a href="applications.html" class="btn btn-secondary btn-sm">Track Dossier</a>
                </div>
            `;
        } else if (s.is_eligible) {
            actionArea = `
                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 14px;">
                    <span style="font-size: 0.84rem; color: #16a34a; font-weight: 600;">✨ You satisfy all relational criteria rules!</span>
                    <button class="btn btn-primary btn-sm" onclick="openApplyModal(${s.Scheme_ID})">Apply Now</button>
                </div>
            `;
        } else {
            actionArea = `
                <div style="border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 14px;">
                    <div style="font-size: 0.8rem; color: #dc2626; font-weight: 600; margin-bottom: 6px;">❌ Ineligible under database criteria:</div>
                    <ul style="margin: 0; padding-left: 18px; font-size: 0.78rem; color: #64748b; line-height: 1.4;">
                        ${s.reasons.map(r => `<li>${r}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        const borderStyle = s.is_eligible ? 'border-left: 5px solid #16a34a;' : 'border-left: 5px solid #ef4444; opacity: 0.88;';
        const badgeTag = s.is_eligible ? 
            `<span class="badge badge-approved" style="font-size: 0.8rem;">Eligible</span>` : 
            `<span class="badge badge-rejected" style="font-size: 0.8rem;">Ineligible</span>`;

        return `
            <div class="card" style="margin-bottom: 16px; ${borderStyle}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px;">
                    <div>
                        <span class="badge badge-dept" style="background: rgba(30,58,138,0.1); color: var(--primary); font-size: 0.74rem;">${s.Department_Name}</span>
                        <h3 style="font-size: 1.15rem; margin-top: 6px; margin-bottom: 4px; color: var(--primary-dark);">${s.Scheme_Name}</h3>
                    </div>
                    ${badgeTag}
                </div>

                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 10px;">
                    ${s.Description}
                </p>

                <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.82rem; background: #f8fafc; padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid #e2e8f0;">
                    <div><strong>Benefit Type:</strong> <span style="color: var(--secondary-dark); font-weight: 500;">${s.Benefit_Type}</span></div>
                    <div><strong>Sanctioned Value:</strong> <span style="color: var(--primary); font-weight: 600;">${s.Benefit_Description}</span></div>
                    <div><strong>Deadline:</strong> ${s.Last_Date ? new Date(s.Last_Date).toLocaleDateString('en-IN') : 'Open'}</div>
                </div>

                ${actionArea}
            </div>
        `;
    }).join('');
}

// Filter Toggle: Show All vs Eligible Only
function toggleEligibilityFilter(filterType) {
    document.querySelectorAll('.filter-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filterType);
    });

    if (filterType === 'eligible') {
        const filtered = evaluatedSchemes.filter(s => s.is_eligible);
        renderEligibilityCards(filtered);
    } else {
        renderEligibilityCards(evaluatedSchemes);
    }
}

// Global Alias
window.runEligibilityCheck = checkCitizenEligibility;
