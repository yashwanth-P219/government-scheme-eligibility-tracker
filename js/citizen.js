/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/citizen.js
 */

// Helper to calculate profile completeness
function calculateCompleteness(citizen) {
    const fields = ['Name', 'Email', 'Phone', 'Age', 'Gender', 'Category', 'Income', 'Occupation', 'Education', 'State', 'District', 'Address'];
    let filled = 0;
    fields.forEach(f => {
        if (citizen[f] !== null && citizen[f] !== undefined && String(citizen[f]).trim() !== '') {
            filled++;
        }
    });
    return Math.round((filled / fields.length) * 100);
}

// 1. Load Citizen Dashboard Metrics & Recent Applications
async function loadCitizenDashboard() {
    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        if (!user || !user.citizen_id) return;

        const citizenId = user.citizen_id;
        const citizen = GovDB.fetchOne("SELECT * FROM Citizen WHERE Citizen_ID = ?;", [citizenId]);
        if (!citizen) return;

        // Metric 1: Total available schemes
        const totalSchemesRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Scheme WHERE Status = 'Active';");
        const totalAvailable = totalSchemesRow ? totalSchemesRow.c : 0;

        // Metric 2: Applications submitted by this citizen
        const totalAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application WHERE Citizen_ID = ?;", [citizenId]);
        const appsSubmitted = totalAppsRow ? totalAppsRow.c : 0;

        // Metric 3: Pending applications
        const pendingAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application WHERE Citizen_ID = ? AND Status IN ('Pending', 'Under Review');", [citizenId]);
        const pendingCount = pendingAppsRow ? pendingAppsRow.c : 0;

        // Metric 4: Approved applications
        const approvedAppsRow = GovDB.fetchOne("SELECT COUNT(*) AS c FROM Application WHERE Citizen_ID = ? AND Status = 'Approved';", [citizenId]);
        const approvedCount = approvedAppsRow ? approvedAppsRow.c : 0;

        // Metric 5: Total benefits received (Paid)
        const benefitsPaidRow = GovDB.fetchOne(`
            SELECT COALESCE(SUM(b.Benefit_Amount), 0.0) AS total_paid
            FROM Benefits b
            INNER JOIN Approval ap ON b.Approval_ID = ap.Approval_ID
            INNER JOIN Application a ON ap.Application_ID = a.Application_ID
            WHERE a.Citizen_ID = ? AND b.Payment_Status = 'Paid';
        `, [citizenId]);
        const totalBenefitsPaid = benefitsPaidRow ? benefitsPaidRow.total_paid : 0.0;

        // Metric 6: Eligible Schemes count via Dynamic SQL query
        const eligibleRows = GovDB.fetchAll(`
            SELECT s.Scheme_ID
            FROM Scheme s
            INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
            WHERE s.Status = 'Active'
              AND (s.Last_Date IS NULL OR s.Last_Date >= DATE('now'))
              AND (e.Min_Age IS NULL OR ? IS NULL OR e.Min_Age <= ?)
              AND (e.Max_Age IS NULL OR ? IS NULL OR e.Max_Age >= ?)
              AND (e.Income_Limit IS NULL OR ? IS NULL OR e.Income_Limit >= ?)
              AND (e.Category IS NULL OR ? IS NULL OR LOWER(e.Category) = LOWER(?))
              AND (e.Gender IS NULL OR ? IS NULL OR LOWER(e.Gender) = LOWER(?))
              AND (e.Occupation IS NULL OR ? IS NULL OR LOWER(e.Occupation) = LOWER(?))
              AND (e.Education IS NULL OR ? IS NULL OR LOWER(e.Education) = LOWER(?))
              AND (e.State IS NULL OR ? IS NULL OR LOWER(e.State) = LOWER(?))
              AND (e.District IS NULL OR ? IS NULL OR LOWER(e.District) = LOWER(?));
        `, [
            citizen.Age, citizen.Age,
            citizen.Age, citizen.Age,
            citizen.Income, citizen.Income,
            citizen.Category, citizen.Category,
            citizen.Gender, citizen.Gender,
            citizen.Occupation, citizen.Occupation,
            citizen.Education, citizen.Education,
            citizen.State, citizen.State,
            citizen.District, citizen.District
        ]);
        const eligibleCount = eligibleRows.length;

        // Profile completeness
        const completeness = calculateCompleteness(citizen);

        // Update DOM elements
        const totalSchemesEl = document.getElementById('statTotalSchemes');
        const eligibleSchemesEl = document.getElementById('statEligibleSchemes');
        const submittedAppsEl = document.getElementById('statSubmittedApps');
        const pendingAppsEl = document.getElementById('statPendingApps');
        const approvedAppsEl = document.getElementById('statApprovedApps');
        const benefitsReceivedEl = document.getElementById('statBenefitsReceived');
        const profileCompletenessEl = document.getElementById('profileCompletenessPct');
        const profileProgressEl = document.getElementById('profileProgressBar');
        const citizenWelcomeName = document.getElementById('citizenWelcomeName');

        if (totalSchemesEl) totalSchemesEl.textContent = totalAvailable;
        if (eligibleSchemesEl) eligibleSchemesEl.textContent = eligibleCount;
        if (submittedAppsEl) submittedAppsEl.textContent = appsSubmitted;
        if (pendingAppsEl) pendingAppsEl.textContent = pendingCount;
        if (approvedAppsEl) approvedAppsEl.textContent = approvedCount;
        if (benefitsReceivedEl) benefitsReceivedEl.textContent = '₹' + Number(totalBenefitsPaid).toLocaleString('en-IN');
        if (citizenWelcomeName) citizenWelcomeName.textContent = citizen.Name;

        if (profileCompletenessEl) {
            profileCompletenessEl.textContent = `${completeness}%`;
            if (profileProgressEl) {
                profileProgressEl.style.width = `${completeness}%`;
                if (completeness < 70) {
                    profileProgressEl.className = 'progress-bar-fill fill-warning';
                    const notice = document.getElementById('profileNotice');
                    if (notice) notice.style.display = 'block';
                } else {
                    profileProgressEl.className = 'progress-bar-fill fill-success';
                }
            }
        }

        // Recent Applications Table (Latest 5 submissions)
        const recentTableBody = document.getElementById('recentApplicationsTable');
        if (recentTableBody) {
            const recentApps = GovDB.fetchAll(`
                SELECT 
                    a.Application_ID,
                    s.Scheme_Name,
                    d.Department_Name,
                    a.Application_Date,
                    a.Status,
                    b.Benefit_Amount,
                    b.Payment_Status
                FROM Application a
                INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                INNER JOIN Department d ON s.Department_ID = d.Department_ID
                LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
                LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
                WHERE a.Citizen_ID = ?
                ORDER BY a.Application_Date DESC
                LIMIT 5;
            `, [citizenId]);

            if (recentApps.length === 0) {
                recentTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon">📄</div>
                            <h3>No Applications Submitted Yet</h3>
                            <p>Browse eligible welfare schemes and submit your first application to track benefits here.</p>
                            <a href="eligibility.html" class="btn btn-primary btn-sm">Check Eligible Schemes</a>
                        </td>
                    </tr>
                `;
            } else {
                recentTableBody.innerHTML = recentApps.map(app => {
                    let badgeClass = 'badge-pending';
                    if (app.Status === 'Under Review') badgeClass = 'badge-review';
                    if (app.Status === 'Approved') badgeClass = 'badge-approved';
                    if (app.Status === 'Rejected') badgeClass = 'badge-rejected';

                    let benefitBadge = '-';
                    if (app.Benefit_Amount && Number(app.Benefit_Amount) > 0) {
                        benefitBadge = `<span class="text-success font-weight-bold">₹${Number(app.Benefit_Amount).toLocaleString('en-IN')}</span> (${app.Payment_Status || 'Pending'})`;
                    }

                    const appDate = app.Application_Date ? new Date(app.Application_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';

                    return `
                        <tr>
                            <td><strong>#${app.Application_ID}</strong></td>
                            <td>
                                <div style="font-weight: 600;">${app.Scheme_Name}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">${app.Department_Name}</div>
                            </td>
                            <td>${appDate}</td>
                            <td><span class="badge ${badgeClass}">${app.Status}</span></td>
                            <td>${benefitBadge}</td>
                            <td>
                                <a href="application-details.html?id=${app.Application_ID}" class="btn btn-secondary btn-sm">View Details</a>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }
    } catch (err) {
        console.error('Error loading citizen dashboard:', err);
        showToast('Error loading dashboard data: ' + err.message, 'error');
    }
}

// 2. Load Citizen Profile Form
async function loadCitizenProfile() {
    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        if (!user || !user.citizen_id) return;

        const p = GovDB.fetchOne("SELECT * FROM Citizen WHERE Citizen_ID = ?;", [user.citizen_id]);
        if (!p) return;

        if (document.getElementById('name')) document.getElementById('name').value = p.Name || '';
        if (document.getElementById('email')) document.getElementById('email').value = p.Email || '';
        if (document.getElementById('phone')) document.getElementById('phone').value = p.Phone || '';
        if (document.getElementById('age')) document.getElementById('age').value = p.Age !== null ? p.Age : '';
        if (document.getElementById('gender')) document.getElementById('gender').value = p.Gender || '';
        if (document.getElementById('category')) document.getElementById('category').value = p.Category || '';
        if (document.getElementById('income')) document.getElementById('income').value = p.Income !== null ? p.Income : '';
        if (document.getElementById('occupation')) document.getElementById('occupation').value = p.Occupation || '';
        if (document.getElementById('education')) document.getElementById('education').value = p.Education || '';
        if (document.getElementById('state')) document.getElementById('state').value = p.State || '';
        if (document.getElementById('district')) document.getElementById('district').value = p.District || '';
        if (document.getElementById('address')) document.getElementById('address').value = p.Address || '';

        const completeness = calculateCompleteness(p);
        const badge = document.getElementById('profileCompletenessBadge');
        if (badge) {
            badge.textContent = `${completeness}% Completed`;
            badge.className = completeness >= 80 ? 'badge badge-approved' : 'badge badge-pending';
        }
    } catch (err) {
        console.error('Error loading citizen profile:', err);
    }
}

// 3. Profile Form Submission
function initProfileForm() {
    const form = document.getElementById('profileForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating SQLite...';

        try {
            await GovDB.initializeDatabase();
            const user = getCurrentUser();
            if (!user || !user.citizen_id) return;

            const name = document.getElementById('name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const ageVal = document.getElementById('age').value ? parseInt(document.getElementById('age').value) : null;
            const genderVal = document.getElementById('gender').value || null;
            const categoryVal = document.getElementById('category').value || null;
            const incomeVal = document.getElementById('income').value ? parseFloat(document.getElementById('income').value) : null;
            const occupationVal = document.getElementById('occupation').value || null;
            const educationVal = document.getElementById('education').value || null;
            const stateVal = document.getElementById('state').value.trim() || null;
            const districtVal = document.getElementById('district').value.trim() || null;
            const addressVal = document.getElementById('address').value.trim() || null;

            await GovDB.updateRecord('Citizen', {
                Name: name,
                Phone: phone,
                Age: ageVal,
                Gender: genderVal,
                Category: categoryVal,
                Income: incomeVal,
                Occupation: occupationVal,
                Education: educationVal,
                State: stateVal,
                District: districtVal,
                Address: addressVal
            }, 'Citizen_ID = ?', [user.citizen_id]);

            // Update session cache
            user.name = name;
            user.age = ageVal;
            user.category = categoryVal;
            user.income = incomeVal;
            user.occupation = occupationVal;
            setSession(user);

            showToast('Profile updated successfully!', 'success');
            loadCitizenProfile();
            initSessionHeader();

        } catch (err) {
            console.error('Profile update error:', err);
            showToast('Error updating profile: ' + err.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// 4. Load Citizen Benefits Ledger
async function loadCitizenBenefits() {
    try {
        await GovDB.initializeDatabase();
        const user = getCurrentUser();
        if (!user || !user.citizen_id) return;

        const benefits = GovDB.fetchAll(`
            SELECT 
                b.Benefit_ID,
                s.Scheme_Name,
                d.Department_Name,
                b.Benefit_Amount,
                b.Benefit_Date,
                b.Payment_Status,
                b.Transaction_Reference,
                ap.Approval_Date,
                a.Application_ID
            FROM Benefits b
            INNER JOIN Approval ap ON b.Approval_ID = ap.Approval_ID
            INNER JOIN Application a ON ap.Application_ID = a.Application_ID
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            WHERE a.Citizen_ID = ?
            ORDER BY b.Benefit_Date DESC;
        `, [user.citizen_id]);

        // Calculate summary cards
        let totalPaid = 0;
        let totalPending = 0;
        let totalProcessing = 0;

        benefits.forEach(b => {
            const amt = Number(b.Benefit_Amount) || 0;
            if (b.Payment_Status === 'Paid') totalPaid += amt;
            else if (b.Payment_Status === 'Processing') totalProcessing += amt;
            else totalPending += amt;
        });

        const paidEl = document.getElementById('benefitTotalPaid') || document.getElementById('citPaidAmount');
        const processingEl = document.getElementById('benefitTotalProcessing') || document.getElementById('citProcessingAmount');
        const pendingEl = document.getElementById('benefitTotalPending');
        const countEl = document.getElementById('benefitCountTotal') || document.getElementById('citTotalTransactions');

        if (paidEl) paidEl.textContent = '₹' + totalPaid.toLocaleString('en-IN');
        if (processingEl) processingEl.textContent = '₹' + totalProcessing.toLocaleString('en-IN');
        if (pendingEl) pendingEl.textContent = '₹' + totalPending.toLocaleString('en-IN');
        if (countEl) countEl.textContent = benefits.length;

        const tableBody = document.getElementById('citizenBenefitsTable') || document.getElementById('citBenefitsTable');
        if (tableBody) {
            if (benefits.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-icon">💰</div>
                            <h3>No Benefits Sanctioned Yet</h3>
                            <p>Once your submitted scheme application is scrutinized and approved by an officer, your Direct Benefit Transfer records will appear here.</p>
                            <a href="eligibility.html" class="btn btn-primary btn-sm">Find Eligible Schemes</a>
                        </td>
                    </tr>
                `;
            } else {
                tableBody.innerHTML = benefits.map(b => {
                    let badgeClass = 'badge-pending';
                    if (b.Payment_Status === 'Paid') badgeClass = 'badge-approved';
                    if (b.Payment_Status === 'Processing') badgeClass = 'badge-review';
                    if (b.Payment_Status === 'Failed') badgeClass = 'badge-rejected';

                    const bDate = b.Benefit_Date ? new Date(b.Benefit_Date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
                    const txRef = b.Transaction_Reference || '<span style="color: #94a3b8; font-style: italic;">Pending Generation</span>';

                    return `
                        <tr>
                            <td><strong>#BEN-${b.Benefit_ID}</strong></td>
                            <td>
                                <div style="font-weight: 600;">${b.Scheme_Name}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">${b.Department_Name}</div>
                            </td>
                            <td class="font-weight-bold" style="font-size: 1.05rem; color: var(--primary);">₹${Number(b.Benefit_Amount).toLocaleString('en-IN')}</td>
                            <td>${bDate}</td>
                            <td><span class="badge ${badgeClass}">${b.Payment_Status}</span></td>
                            <td><code style="font-size: 0.78rem;">${txRef}</code></td>
                            <td>
                                <a href="application-details.html?id=${b.Application_ID}" class="btn btn-secondary btn-sm">View Application</a>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }
    } catch (err) {
        console.error('Error loading citizen benefits:', err);
    }
}
