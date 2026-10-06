/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/auth.js
 */

const SESSION_KEY = 'gov_tracker_session';

// 1. Toast Notification Helper
function showToast(message, type = 'info', duration = 4000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';
    if (type === 'warning') icon = '⚠️';

    toast.innerHTML = `
        <span style="font-size: 1.2rem;">${icon}</span>
        <div style="flex: 1; font-size: 0.88rem; line-height: 1.4;">${message}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// 2. Cryptographic Password Hash (Web Crypto API SHA-256 with Salt)
async function hashPassword(plainText) {
    const salted = 'gov_tracker_salt_' + plainText;
    const msgUint8 = new TextEncoder().encode(salted);
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

// 3. Session Utilities
function getSession() {
    try {
        const raw = sessionStorage.getItem(SESSION_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
}

function setSession(sessionData) {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(sessionData));
}

function clearSession() {
    sessionStorage.removeItem(SESSION_KEY);
}

function getCurrentUser() {
    return getSession();
}

// 4. Role Guards (Page Protectors)
function requireCitizen() {
    const user = getSession();
    if (!user || user.role !== 'CITIZEN') {
        alert('Access Restricted: Please sign in with a Citizen demonstration account.');
        const isSub = window.location.pathname.includes('/citizen/') || window.location.pathname.includes('/officer/');
        window.location.href = isSub ? '../login.html' : 'login.html';
        return false;
    }
    return true;
}

function requireOfficer() {
    const user = getSession();
    if (!user || user.role !== 'OFFICER') {
        alert('Access Restricted: Please sign in with a Government Officer account.');
        const isSub = window.location.pathname.includes('/citizen/') || window.location.pathname.includes('/officer/');
        window.location.href = isSub ? '../login.html' : 'login.html';
        return false;
    }
    return true;
}

// 5. Global Navbar User Info Display
function initSessionHeader() {
    const user = getSession();
    if (!user) return;

    const navName = document.getElementById('navUserName');
    if (navName && user.name) {
        navName.textContent = user.name;
    }
    const avatar = document.getElementById('userAvatarChar');
    if (avatar && user.name) {
        avatar.textContent = user.name.charAt(0).toUpperCase();
    }
    const desig = document.getElementById('navUserDesignation');
    if (desig) {
        desig.textContent = user.designation || (user.role === 'CITIZEN' ? 'Registered Citizen' : 'Officer');
    }
}

// 6. Global Logout Handler
function handleLogout() {
    if (confirm('Are you sure you wish to log out of the demonstration portal?')) {
        clearSession();
        const isSub = window.location.pathname.includes('/citizen/') || window.location.pathname.includes('/officer/');
        window.location.href = isSub ? '../login.html' : 'login.html';
    }
}

// 7. Modal Handlers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        if (!modal.dataset.backdropBound) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modalId);
                }
            });
            modal.dataset.backdropBound = 'true';
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});

window.openModal = openModal;
window.closeModal = closeModal;

// 8. Login Page Logic
function initLoginForm() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;

    const roleBtns = document.querySelectorAll('.login-role-tab');
    let currentRole = 'CITIZEN';

    roleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            roleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentRole = btn.dataset.role;
            const roleInput = document.getElementById('loginRole');
            if (roleInput) roleInput.value = currentRole;

            const badge = document.getElementById('portalBadge');
            if (badge) {
                badge.textContent = currentRole === 'OFFICER' ? 'OFFICER PORTAL' : 'CITIZEN PORTAL';
                badge.className = currentRole === 'OFFICER' ? 'badge badge-review' : 'badge badge-approved';
            }
        });
    });

    // Quick demo pre-fill
    window.fillDemoAccount = function (role) {
        const emailField = document.getElementById('email');
        const passField = document.getElementById('password');
        const roleField = document.getElementById('loginRole');

        if (role === 'CITIZEN') {
            emailField.value = 'rahul.sharma@example.com';
            passField.value = 'Citizen@123';
            if (roleField) roleField.value = 'CITIZEN';
            roleBtns.forEach(b => b.classList.toggle('active', b.dataset.role === 'CITIZEN'));
            currentRole = 'CITIZEN';
            showToast('Loaded demo credentials: Rahul Sharma (Citizen)', 'info');
        } else if (role === 'OFFICER') {
            emailField.value = 'officer.agri@gov.in';
            passField.value = 'Officer@123';
            if (roleField) roleField.value = 'OFFICER';
            roleBtns.forEach(b => b.classList.toggle('active', b.dataset.role === 'OFFICER'));
            currentRole = 'OFFICER';
            showToast('Loaded demo credentials: Dr. Anand Verma (Officer)', 'info');
        }
    };

    // Form Submission: Executes query directly against in-browser SQLite WASM!
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Querying SQLite Database...';

        try {
            await GovDB.initializeDatabase();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const role = document.getElementById('loginRole') ? document.getElementById('loginRole').value : currentRole;

            if (!email || !password) {
                showToast('Please provide both email and password.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            const inputHash = await hashPassword(password);

            if (role === 'CITIZEN') {
                const userRow = GovDB.fetchOne("SELECT * FROM Citizen WHERE LOWER(Email) = LOWER(?);", [email]);
                if (!userRow) {
                    showToast('Citizen record not found with this email.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }
                if (userRow.Password_Hash !== inputHash) {
                    showToast('Invalid citizen password. Demo password is Citizen@123', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }

                // Successful login
                setSession({
                    role: 'CITIZEN',
                    user_id: userRow.Citizen_ID,
                    citizen_id: userRow.Citizen_ID,
                    email: userRow.Email,
                    name: userRow.Name,
                    category: userRow.Category,
                    income: userRow.Income,
                    age: userRow.Age,
                    occupation: userRow.Occupation
                });

                showToast(`Welcome back, ${userRow.Name}! Loading Citizen Dashboard...`, 'success');
                setTimeout(() => {
                    window.location.href = 'citizen/dashboard.html';
                }, 600);

            } else if (role === 'OFFICER') {
                const officerRow = GovDB.fetchOne(`
                    SELECT o.*, d.Department_Name 
                    FROM Officer o 
                    INNER JOIN Department d ON o.Department_ID = d.Department_ID 
                    WHERE LOWER(o.Email) = LOWER(?);
                `, [email]);

                if (!officerRow) {
                    showToast('Officer record not found with this email.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }
                if (officerRow.Password_Hash !== inputHash) {
                    showToast('Invalid officer password. Demo password is Officer@123', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    return;
                }

                // Successful login
                setSession({
                    role: 'OFFICER',
                    user_id: officerRow.Officer_ID,
                    officer_id: officerRow.Officer_ID,
                    email: officerRow.Email,
                    name: officerRow.Name,
                    designation: officerRow.Designation,
                    department_id: officerRow.Department_ID,
                    department_name: officerRow.Department_Name
                });

                showToast(`Officer authenticated: ${officerRow.Name} (${officerRow.Department_Name})...`, 'success');
                setTimeout(() => {
                    window.location.href = 'officer/dashboard.html';
                }, 600);
            }
        } catch (err) {
            console.error('Login error:', err);
            showToast('Authentication Error: ' + err.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// 9. Registration Page Logic
function initRegisterForm() {
    const regForm = document.getElementById('registerForm');
    if (!regForm) return;

    regForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = regForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving to SQLite...';

        try {
            await GovDB.initializeDatabase();

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPass = document.getElementById('confirmPassword') ? document.getElementById('confirmPassword').value : password;
            const phone = document.getElementById('phone') ? document.getElementById('phone').value.trim() : '';

            if (!name || !email || !password) {
                showToast('Please fill in all mandatory fields.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            if (password !== confirmPass) {
                showToast('Passwords do not match.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            // Check duplicate email in SQLite
            const existing = GovDB.fetchOne("SELECT Citizen_ID FROM Citizen WHERE LOWER(Email) = LOWER(?);", [email]);
            if (existing) {
                showToast('An account already exists with this email address.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            const passwordHash = await hashPassword(password);
            const ageVal = document.getElementById('age') && document.getElementById('age').value ? parseInt(document.getElementById('age').value) : null;
            const incomeVal = document.getElementById('income') && document.getElementById('income').value ? parseFloat(document.getElementById('income').value) : null;

            const newCitizenData = {
                Name: name,
                Email: email,
                Password_Hash: passwordHash,
                Phone: phone,
                Age: ageVal,
                Gender: document.getElementById('gender') ? document.getElementById('gender').value || null : null,
                Category: document.getElementById('category') ? document.getElementById('category').value || null : null,
                Income: incomeVal,
                Occupation: document.getElementById('occupation') ? document.getElementById('occupation').value || null : null,
                Education: document.getElementById('education') ? document.getElementById('education').value || null : null,
                District: document.getElementById('district') ? document.getElementById('district').value.trim() || null : null,
                State: document.getElementById('state') ? document.getElementById('state').value.trim() || null : null,
                Address: document.getElementById('address') ? document.getElementById('address').value.trim() || null : null
            };

            const newCitizenId = await GovDB.insertRecord('Citizen', newCitizenData);

            setSession({
                role: 'CITIZEN',
                user_id: newCitizenId,
                citizen_id: newCitizenId,
                email: email,
                name: name,
                category: newCitizenData.Category,
                income: newCitizenData.Income,
                age: newCitizenData.Age,
                occupation: newCitizenData.Occupation
            });

            showToast('Account registered successfully! Redirecting to dashboard...', 'success');
            setTimeout(() => {
                window.location.href = 'citizen/dashboard.html';
            }, 800);

        } catch (err) {
            console.error('Registration error:', err);
            showToast('Registration Error: ' + err.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// 10. DOM Ready Handlers
document.addEventListener('DOMContentLoaded', () => {
    // Mobile sidebar toggle
    const toggleBtn = document.querySelector('.menu-toggle-btn');
    const sidebar = document.querySelector('.app-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    // Modal close triggers
    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            if (overlay) overlay.classList.remove('active');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });

    // Populate user display
    initSessionHeader();
    initLoginForm();
    initRegisterForm();
});
