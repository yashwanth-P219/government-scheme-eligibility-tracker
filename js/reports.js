/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/reports.js
 */

let reportsDataCache = {};

// Load all 8 SQL reports from SQLite WASM
async function loadAllReports() {
    try {
        await GovDB.initializeDatabase();

        // 1. Applications by Scheme
        const rep1 = GovDB.fetchAll(`
            SELECT 
                s.Scheme_ID,
                s.Scheme_Name,
                d.Department_Name,
                COUNT(a.Application_ID) AS Total_Applications
            FROM Scheme s
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
            GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
            ORDER BY Total_Applications DESC;
        `);
        reportsDataCache.report1 = rep1;
        renderReport1(rep1);

        // 2. Applications by Status
        const rep2 = GovDB.fetchAll(`
            SELECT 
                Status,
                COUNT(Application_ID) AS Count,
                ROUND(COUNT(Application_ID) * 100.0 / (SELECT COUNT(*) FROM Application), 1) AS Percentage
            FROM Application
            GROUP BY Status
            ORDER BY Count DESC;
        `);
        reportsDataCache.report2 = rep2;
        renderReport2(rep2);

        // 3. Approved Applications by Department
        const rep3 = GovDB.fetchAll(`
            SELECT 
                d.Department_ID,
                d.Department_Name,
                COUNT(a.Application_ID) AS Approved_Count
            FROM Department d
            INNER JOIN Scheme s ON d.Department_ID = s.Department_ID
            LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID AND a.Status = 'Approved'
            GROUP BY d.Department_ID, d.Department_Name
            ORDER BY Approved_Count DESC;
        `);
        reportsDataCache.report3 = rep3;
        renderReport3(rep3);

        // 4. Benefits Distributed by Scheme
        const rep4 = GovDB.fetchAll(`
            SELECT 
                s.Scheme_ID,
                s.Scheme_Name,
                d.Department_Name,
                COUNT(b.Benefit_ID) AS Beneficiary_Count,
                COALESCE(SUM(b.Benefit_Amount), 0.0) AS Total_Benefits_Amount,
                COALESCE(ROUND(AVG(b.Benefit_Amount), 2), 0.0) AS Average_Benefit_Amount
            FROM Scheme s
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
            LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
            LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
            GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
            ORDER BY Total_Benefits_Amount DESC;
        `);
        reportsDataCache.report4 = rep4;
        renderReport4(rep4);

        // 5. Benefits Distributed by Month
        const rep5 = GovDB.fetchAll(`
            SELECT 
                STRFTIME('%Y-%m', Benefit_Date) AS Month_Year,
                COUNT(Benefit_ID) AS Beneficiary_Count,
                ROUND(SUM(Benefit_Amount), 2) AS Monthly_Total
            FROM Benefits
            WHERE Payment_Status IN ('Paid', 'Processing')
            GROUP BY Month_Year
            ORDER BY Month_Year ASC;
        `);
        reportsDataCache.report5 = rep5;
        renderReport5(rep5);

        // 6. Citizen Category Distribution
        const rep6 = GovDB.fetchAll(`
            SELECT 
                Category,
                COUNT(Citizen_ID) AS Citizen_Count,
                ROUND(COUNT(Citizen_ID) * 100.0 / (SELECT COUNT(*) FROM Citizen), 1) AS Percentage
            FROM Citizen
            GROUP BY Category
            ORDER BY Citizen_Count DESC;
        `);
        reportsDataCache.report6 = rep6;
        renderReport6(rep6);

        // 7. Scheme Utilization Rates
        const rep7 = GovDB.fetchAll(`
            SELECT 
                s.Scheme_ID,
                s.Scheme_Name,
                COUNT(a.Application_ID) AS Total_Applied,
                SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) AS Total_Approved,
                CASE 
                    WHEN COUNT(a.Application_ID) = 0 THEN 0.0
                    ELSE ROUND(SUM(CASE WHEN a.Status = 'Approved' THEN 1 ELSE 0 END) * 100.0 / COUNT(a.Application_ID), 1)
                END AS Approval_Rate_Pct
            FROM Scheme s
            LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
            GROUP BY s.Scheme_ID, s.Scheme_Name
            ORDER BY Total_Applied DESC;
        `);
        reportsDataCache.report7 = rep7;
        renderReport7(rep7);

        // 8. Pending Applications Queue Analysis
        const rep8 = GovDB.fetchAll(`
            SELECT 
                a.Application_ID,
                c.Name AS Citizen_Name,
                c.Category,
                s.Scheme_Name,
                d.Department_Name,
                a.Application_Date,
                ROUND(JULIANDAY('now') - JULIANDAY(a.Application_Date), 1) AS Days_Pending
            FROM Application a
            INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
            INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
            INNER JOIN Department d ON s.Department_ID = d.Department_ID
            WHERE a.Status = 'Pending'
            ORDER BY a.Application_Date ASC;
        `);
        reportsDataCache.report8 = rep8;
        renderReport8(rep8);

    } catch (err) {
        console.error('Error generating SQL reports:', err);
        showToast('Error querying SQL reports: ' + err.message, 'error');
    }
}

// -----------------------------------------------------------------------------
// REPORT 1: Applications by Scheme (Horizontal Bar Chart)
// -----------------------------------------------------------------------------
function renderReport1(data) {
    const container = document.getElementById('report1Container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state">No scheme application data found.</div>';
        return;
    }

    const maxVal = Math.max(...data.map(d => parseInt(d.Total_Applications) || 0), 1);

    container.innerHTML = data.map(d => {
        const count = parseInt(d.Total_Applications) || 0;
        const pct = Math.round((count / maxVal) * 100);
        return `
            <div class="bar-chart-row">
                <div class="bar-label" title="${d.Scheme_Name}">
                    ${d.Scheme_Name}
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: ${pct}%;">
                        ${count > 0 ? count : ''}
                    </div>
                </div>
                <div class="bar-value">${count} apps</div>
            </div>
        `;
    }).join('');
}

// -----------------------------------------------------------------------------
// REPORT 2: Applications by Status (Donut Chart & Legend)
// -----------------------------------------------------------------------------
function renderReport2(data) {
    const chartContainer = document.getElementById('report2Donut');
    const legendContainer = document.getElementById('report2Legend');
    if (!chartContainer || !legendContainer) return;

    if (!data || data.length === 0) {
        chartContainer.innerHTML = '<div class="empty-state">No status data.</div>';
        return;
    }

    const colorMap = {
        'Pending': '#f59e0b',
        'Under Review': '#3b82f6',
        'Approved': '#10b981',
        'Rejected': '#ef4444'
    };

    let cumulativePct = 0;
    const slices = data.map(d => {
        const pct = parseFloat(d.Percentage) || 0;
        const color = colorMap[d.Status] || '#94a3b8';
        const start = cumulativePct;
        cumulativePct += pct;
        return `${color} ${start}% ${cumulativePct}%`;
    }).join(', ');

    chartContainer.style.background = `conic-gradient(${slices})`;

    legendContainer.innerHTML = data.map(d => `
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; font-size: 0.84rem;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: ${colorMap[d.Status] || '#94a3b8'};"></span>
                <span>${d.Status}</span>
            </div>
            <strong>${d.Count} (${d.Percentage}%)</strong>
        </div>
    `).join('');
}

// -----------------------------------------------------------------------------
// REPORT 3: Approved Applications by Department (Bar Chart)
// -----------------------------------------------------------------------------
function renderReport3(data) {
    const container = document.getElementById('report3Container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state">No approved application records.</div>';
        return;
    }

    const maxVal = Math.max(...data.map(d => parseInt(d.Approved_Count) || 0), 1);

    container.innerHTML = data.map(d => {
        const count = parseInt(d.Approved_Count) || 0;
        const pct = Math.round((count / maxVal) * 100);
        return `
            <div class="bar-chart-row">
                <div class="bar-label" title="${d.Department_Name}">
                    ${d.Department_Name}
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: ${pct}%; background: linear-gradient(90deg, #10b981, #059669);">
                        ${count > 0 ? count : ''}
                    </div>
                </div>
                <div class="bar-value">${count} sanctioned</div>
            </div>
        `;
    }).join('');
}

// -----------------------------------------------------------------------------
// REPORT 4: Benefits Distributed by Scheme (Data Table)
// -----------------------------------------------------------------------------
function renderReport4(data) {
    const tableBody = document.getElementById('report4TableBody') || document.getElementById('report4Table');
    if (!tableBody) return;

    if (!data || data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="empty-state">No benefit disbursement records.</td></tr>';
        return;
    }

    tableBody.innerHTML = data.map(d => `
        <tr>
            <td>
                <strong>${d.Scheme_Name}</strong>
                <div style="font-size: 0.74rem; color: var(--text-muted);">${d.Department_Name}</div>
            </td>
            <td>${d.Beneficiary_Count}</td>
            <td style="color: var(--primary); font-weight: 600;">₹${Number(d.Total_Benefits_Amount).toLocaleString('en-IN')}</td>
            <td>₹${Number(d.Average_Benefit_Amount).toLocaleString('en-IN')}</td>
            <td>
                <span class="badge ${d.Beneficiary_Count > 0 ? 'badge-approved' : 'badge-inactive'}">
                    ${d.Beneficiary_Count > 0 ? 'Active' : 'Unused'}
                </span>
            </td>
        </tr>
    `).join('');
}

// -----------------------------------------------------------------------------
// REPORT 5: Monthly Disbursements (Line / Column Chart)
// -----------------------------------------------------------------------------
function renderReport5(data) {
    const container = document.getElementById('report5Container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state">No financial transactions recorded.</div>';
        return;
    }

    const maxAmt = Math.max(...data.map(d => parseFloat(d.Monthly_Total) || 0), 1000);

    container.innerHTML = `
        <div style="display: flex; align-items: flex-end; gap: 20px; height: 180px; padding-top: 20px; border-bottom: 2px solid #cbd5e1;">
            ${data.map(d => {
                const amt = parseFloat(d.Monthly_Total) || 0;
                const hPct = Math.round((amt / maxAmt) * 100);
                return `
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end;">
                        <span style="font-size: 0.74rem; font-weight: 600; color: var(--primary); margin-bottom: 4px;">
                            ₹${Math.round(amt / 1000)}k
                        </span>
                        <div style="width: 100%; max-width: 45px; height: ${Math.max(hPct, 8)}%; background: linear-gradient(180deg, #1e3a8a, #3b82f6); border-radius: 4px 4px 0 0;" title="${d.Month_Year}: ₹${amt.toLocaleString('en-IN')}"></div>
                        <span style="font-size: 0.72rem; color: var(--text-muted); margin-top: 6px;">${d.Month_Year}</span>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

// -----------------------------------------------------------------------------
// REPORT 6: Citizen Category Distribution
// -----------------------------------------------------------------------------
function renderReport6(data) {
    const container = document.getElementById('report6Container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state">No citizen demographic data.</div>';
        return;
    }

    container.innerHTML = data.map(d => `
        <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 4px;">
                <strong>${d.Category || 'General / Unclassified'}</strong>
                <span>${d.Citizen_Count} citizens (${d.Percentage}%)</span>
            </div>
            <div class="progress-bar-bg" style="height: 10px;">
                <div class="progress-bar-fill fill-primary" style="width: ${d.Percentage}%;"></div>
            </div>
        </div>
    `).join('');
}

// -----------------------------------------------------------------------------
// REPORT 7: Scheme Utilization Rate
// -----------------------------------------------------------------------------
function renderReport7(data) {
    const container = document.getElementById('report7Container');
    if (!container) return;

    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state">No scheme utilization data.</div>';
        return;
    }

    container.innerHTML = data.map(d => {
        const rate = parseFloat(d.Approval_Rate_Pct) || 0;
        let colorClass = 'fill-primary';
        if (rate >= 75) colorClass = 'fill-success';
        else if (rate < 40) colorClass = 'fill-warning';

        return `
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; margin-bottom: 4px;">
                    <span style="font-weight: 600;">${d.Scheme_Name}</span>
                    <span><strong>${d.Total_Approved}</strong> / ${d.Total_Applied} approved (${rate}%)</span>
                </div>
                <div class="progress-bar-bg" style="height: 8px;">
                    <div class="progress-bar-fill ${colorClass}" style="width: ${rate}%;"></div>
                </div>
            </div>
        `;
    }).join('');
}

// -----------------------------------------------------------------------------
// REPORT 8: Pending Applications Turnaround Queue
// -----------------------------------------------------------------------------
function renderReport8(data) {
    const tableBody = document.getElementById('report8TableBody') || document.getElementById('report8Table');
    if (!tableBody) return;

    if (!data || data.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="empty-state">No applications currently pending in queue.</td></tr>';
        return;
    }

    tableBody.innerHTML = data.map(d => {
        const days = parseFloat(d.Days_Pending) || 0;
        let urgencyBadge = '<span class="badge badge-approved">&lt; 7 Days</span>';
        if (days > 14) urgencyBadge = '<span class="badge badge-rejected">&gt; 14 Days</span>';
        else if (days > 7) urgencyBadge = '<span class="badge badge-review">7-14 Days</span>';

        return `
            <tr>
                <td><strong>#${d.Application_ID}</strong></td>
                <td>
                    <div style="font-weight: 600;">${d.Citizen_Name}</div>
                    <div style="font-size: 0.74rem; color: var(--text-muted);">${d.Category || 'General'}</div>
                </td>
                <td>${d.Scheme_Name}</td>
                <td>${d.Department_Name}</td>
                <td>${d.Application_Date ? new Date(d.Application_Date).toLocaleDateString('en-IN') : '-'}</td>
                <td>${urgencyBadge} (${days} days)</td>
            </tr>
        `;
    }).join('');
}

// -----------------------------------------------------------------------------
// CSV EXPORT HELPER
// -----------------------------------------------------------------------------
function exportReportCSV(reportKey, filename = 'report_data.csv') {
    const data = reportsDataCache[reportKey];
    if (!data || data.length === 0) {
        showToast('No data available to export.', 'error');
        return;
    }

    const headers = Object.keys(data[0]);
    const csvRows = [headers.join(',')];

    data.forEach(row => {
        const values = headers.map(h => {
            const val = row[h] === null || row[h] === undefined ? '' : String(row[h]);
            return `"${val.replace(/"/g, '""')}"`;
        });
        csvRows.push(values.join(','));
    });

    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    setTimeout(() => {
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }, 300);
}
