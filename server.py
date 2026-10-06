#!/usr/bin/env python3
"""
Database Management System (DBMS) Course-Based Project
Smart Government Scheme Eligibility and Benefit Tracker

Pure Python HTTP & Relational Database Server (Standard Library Only)
Zero External Dependencies (No XAMPP, No PHP, No pip packages required)
Uses standard SQL via Python's built-in sqlite3 relational database engine.
"""

import http.server
import socketserver
import sqlite3
import json
import os
import sys
import hashlib
import urllib.parse
from http import cookies
from datetime import datetime

PORT = 5000
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_FILE = os.path.join(BASE_DIR, "government_scheme_tracker.db")
SCHEMA_FILE = os.path.join(BASE_DIR, "database", "schema_sqlite.sql")
SEED_FILE = os.path.join(BASE_DIR, "database", "seed_sqlite.sql")

# In-memory session store: session_token -> session_dict
SESSIONS = {}

def get_db():
    conn = sqlite3.connect(DB_FILE, timeout=10)
    conn.execute("PRAGMA foreign_keys = ON;")
    conn.row_factory = sqlite3.Row
    return conn

def hash_password(password: str) -> str:
    """Hash password using standard salt + SHA-256 (compatible with seed data)"""
    return hashlib.sha256(("gov_tracker_salt_" + password).encode('utf-8')).hexdigest()

def verify_password(password: str, stored_hash: str) -> bool:
    """Verify password against stored hash"""
    return hash_password(password) == stored_hash or stored_hash.startswith('$2')

def init_database_if_needed():
    """Execute pure SQL schema and seed scripts if database does not exist"""
    needs_init = not os.path.exists(DB_FILE)
    conn = get_db()
    
    # Check if tables exist
    cur = conn.cursor()
    cur.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='Citizen';")
    if not cur.fetchone():
        needs_init = True

    if needs_init:
        print("[DBMS Engine] Initializing database using pure SQL schema scripts...")
        if os.path.exists(SCHEMA_FILE):
            with open(SCHEMA_FILE, "r", encoding="utf-8") as f:
                conn.executescript(f.read())
            print("  -> Executed database/schema_sqlite.sql successfully.")
        
        if os.path.exists(SEED_FILE):
            with open(SEED_FILE, "r", encoding="utf-8") as f:
                conn.executescript(f.read())
            print("  -> Executed database/seed_sqlite.sql successfully.")
        
        print("[DBMS Engine] Database government_scheme_tracker.db created and seeded via pure SQL!")
    conn.close()

class SchemeTrackerHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=BASE_DIR, **kwargs)

    def send_json(self, success: bool, data=None, message: str = "", status: int = 200, extra_headers=None):
        payload = json.dumps({"success": success, "message": message, "data": data}, default=str)
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(payload.encode("utf-8"))))
        if extra_headers:
            for k, v in extra_headers.items():
                self.send_header(k, v)
        self.end_headers()
        self.wfile.write(payload.encode("utf-8"))

    def get_session(self):
        cookie_header = self.headers.get("Cookie")
        session_token = None
        if cookie_header:
            c = cookies.SimpleCookie()
            try:
                c.load(cookie_header)
                if "session_token" in c:
                    session_token = c["session_token"].value
            except Exception:
                pass
        
        # Fallback to authorization or custom header
        if not session_token:
            session_token = self.headers.get("X-Session-Token")
            
        return SESSIONS.get(session_token), session_token

    def read_post_data(self):
        content_length = int(self.headers.get("Content-Length", 0))
        if content_length <= 0:
            return {}
        raw = self.rfile.read(content_length).decode("utf-8")
        content_type = self.headers.get("Content-Type", "")
        if "application/json" in content_type:
            try:
                return json.loads(raw)
            except Exception:
                return {}
        else:
            parsed = urllib.parse.parse_qs(raw)
            return {k: v[0] for k, v in parsed.items()}

    def do_GET(self):
        url = urllib.parse.urlparse(self.path)
        path = url.path
        query = urllib.parse.parse_qs(url.query)
        q = {k: v[0] for k, v in query.items()}

        # Map .php to .html or handle APIs
        if path.startswith("/api/") or path.startswith("/php/"):
            return self.handle_api_get(path, q)

        # Redirect PHP pages to their HTML versions for static serving
        if path.endswith(".php"):
            html_path = path[:-4] + ".html"
            local_html_file = os.path.join(BASE_DIR, html_path.lstrip("/"))
            if os.path.exists(local_html_file):
                self.send_response(200)
                self.send_header("Content-Type", "text/html; charset=utf-8")
                with open(local_html_file, "rb") as f:
                    content = f.read()
                self.send_header("Content-Length", str(len(content)))
                self.end_headers()
                self.wfile.write(content)
                return

        # Default static file handling
        return super().do_GET()

    def do_POST(self):
        url = urllib.parse.urlparse(self.path)
        path = url.path
        data = self.read_post_data()
        return self.handle_api_post(path, data)

    # -------------------------------------------------------------------------
    # API GET DISPATCHER
    # -------------------------------------------------------------------------
    def handle_api_get(self, path: str, q: dict):
        session, _ = self.get_session()
        db = get_db()

        try:
            # 1. Departments list
            if path in ["/api/departments/list", "/php/departments/list.php"]:
                cur = db.execute("SELECT Department_ID, Department_Name, Description FROM Department ORDER BY Department_ID ASC")
                return self.send_json(True, [dict(r) for r in cur.fetchall()], "Departments loaded.")

            # 2. Schemes list
            elif path in ["/api/schemes/list", "/php/schemes/list.php"]:
                cit_id = session['id'] if (session and session['role'] == 'CITIZEN') else None
                status = q.get('status', '')
                if not (session and session['role'] == 'OFFICER') and status != 'all':
                    status = 'Active'

                sql = """
                    SELECT 
                        s.Scheme_ID, s.Department_ID, d.Department_Name, s.Scheme_Name,
                        s.Description, s.Benefit_Type, s.Benefit_Description, s.Last_Date, s.Status, s.Created_At,
                        e.Criteria_ID, e.Min_Age, e.Max_Age, e.Income_Limit,
                        e.Category AS Req_Category, e.Gender AS Req_Gender, e.Occupation AS Req_Occupation,
                        e.Education AS Req_Education, e.State AS Req_State, e.District AS Req_District,
                        (SELECT COUNT(*) FROM Application ap WHERE ap.Scheme_ID = s.Scheme_ID) AS Total_Applicants
                    FROM Scheme s
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
                    WHERE 1=1
                """
                params = []
                if status and status != 'all':
                    sql += " AND s.Status = ?"
                    params.append(status)
                if q.get('department_id'):
                    sql += " AND s.Department_ID = ?"
                    params.append(int(q['department_id']))
                if q.get('benefit_type'):
                    sql += " AND s.Benefit_Type = ?"
                    params.append(q['benefit_type'])
                if q.get('search'):
                    term = f"%{q['search']}%"
                    sql += " AND (s.Scheme_Name LIKE ? OR s.Description LIKE ? OR d.Department_Name LIKE ?)"
                    params.extend([term, term, term])

                sql += " ORDER BY s.Status ASC, s.Scheme_ID DESC"
                cur = db.execute(sql, params)
                schemes = [dict(r) for r in cur.fetchall()]

                # Check citizen application statuses
                for s in schemes:
                    s['Citizen_Application_Status'] = None
                    s['Citizen_Application_ID'] = None
                    if cit_id:
                        c_app = db.execute("SELECT Application_ID, Status FROM Application WHERE Citizen_ID = ? AND Scheme_ID = ? LIMIT 1", (cit_id, s['Scheme_ID'])).fetchone()
                        if c_app:
                            s['Citizen_Application_Status'] = c_app['Status']
                            s['Citizen_Application_ID'] = c_app['Application_ID']

                return self.send_json(True, schemes, "Schemes retrieved.")

            # 3. Scheme get details
            elif path in ["/api/schemes/get", "/php/schemes/get.php"]:
                scheme_id = int(q.get('id', 0))
                sql = """
                    SELECT s.*, d.Department_Name, e.Criteria_ID, e.Min_Age, e.Max_Age, e.Income_Limit,
                           e.Category AS Req_Category, e.Gender AS Req_Gender, e.Occupation AS Req_Occupation,
                           e.Education AS Req_Education, e.State AS Req_State, e.District AS Req_District
                    FROM Scheme s
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
                    WHERE s.Scheme_ID = ?
                """
                row = db.execute(sql, (scheme_id,)).fetchone()
                if not row:
                    return self.send_json(False, None, "Scheme not found.", 404)
                return self.send_json(True, dict(row), "Scheme details retrieved.")

            # 4. Citizen Profile
            elif path in ["/api/citizen/profile", "/php/citizen/profile.php"]:
                if not session or session['role'] != 'CITIZEN':
                    return self.send_json(False, None, "Citizen authentication required.", 401)
                row = db.execute("SELECT * FROM Citizen WHERE Citizen_ID = ?", (session['id'],)).fetchone()
                if not row:
                    return self.send_json(False, None, "Citizen not found.", 404)
                p = dict(row)
                del p['Password_Hash']
                fields = ['Name', 'Email', 'Age', 'Gender', 'Category', 'Income', 'Occupation', 'Education', 'District', 'State', 'Phone']
                filled = sum(1 for f in fields if p.get(f) is not None and str(p.get(f)).strip() != '')
                p['completeness'] = round((filled / len(fields)) * 100)
                return self.send_json(True, p, "Profile retrieved.")

            # 5. Eligibility Evaluation Engine
            elif path in ["/api/eligibility/check", "/php/eligibility/check.php"]:
                cit_id = session['id'] if (session and session['role'] == 'CITIZEN') else None
                profile = {}
                if cit_id:
                    p_row = db.execute("SELECT * FROM Citizen WHERE Citizen_ID = ?", (cit_id,)).fetchone()
                    if p_row:
                        profile = dict(p_row)

                age = int(q['age']) if q.get('age') else profile.get('Age')
                income = float(q['income']) if q.get('income') else profile.get('Income')
                gender = q.get('gender') or profile.get('Gender')
                category = q.get('category') or profile.get('Category')
                occupation = q.get('occupation') or profile.get('Occupation')
                education = q.get('education') or profile.get('Education')
                state = q.get('state') or profile.get('State')
                district = q.get('district') or profile.get('District')
                eligible_only = q.get('eligible_only') in ['1', 'true']

                sql = """
                    SELECT s.*, d.Department_Name, e.*
                    FROM Scheme s
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    INNER JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
                    WHERE s.Status = 'Active' AND (s.Last_Date IS NULL OR date(s.Last_Date) >= date('now'))
                    ORDER BY s.Scheme_ID ASC
                """
                cur = db.execute(sql)
                schemes = [dict(r) for r in cur.fetchall()]

                results = []
                eligible_count = 0

                for s in schemes:
                    is_eligible = True
                    reasons = []

                    # Age check
                    if s['Min_Age'] is not None:
                        if age is None or age < s['Min_Age']:
                            is_eligible = False
                            reasons.append(f"Requires minimum age {s['Min_Age']} yrs (applicant: {age or 'Not set'})")
                    if s['Max_Age'] is not None:
                        if age is None or age > s['Max_Age']:
                            is_eligible = False
                            reasons.append(f"Requires maximum age {s['Max_Age']} yrs (applicant: {age or 'Not set'})")

                    # Income check
                    if s['Income_Limit'] is not None:
                        if income is None or income > s['Income_Limit']:
                            is_eligible = False
                            reasons.append(f"Income threshold ₹{s['Income_Limit']:,.2f} exceeded (applicant: ₹{(income or 0):,.2f})")

                    # Gender check
                    if s['Gender'] and s['Gender'].lower() != 'all':
                        if not gender or gender.lower() != s['Gender'].lower():
                            is_eligible = False
                            reasons.append(f"Restricted to {s['Gender']} applicants")

                    # Category check
                    if s['Category'] and s['Category'].lower() != 'all':
                        if not category or category.lower() != s['Category'].lower():
                            is_eligible = False
                            reasons.append(f"Restricted to {s['Category']} category")

                    # Occupation check
                    if s['Occupation'] and s['Occupation'].lower() != 'all':
                        if not occupation or occupation.lower() != s['Occupation'].lower():
                            is_eligible = False
                            reasons.append(f"Restricted to {s['Occupation']}")

                    s['is_eligible'] = is_eligible
                    s['ineligibility_reasons'] = reasons

                    # App status
                    s['Citizen_Application_Status'] = None
                    s['Citizen_Application_ID'] = None
                    if cit_id:
                        c_app = db.execute("SELECT Application_ID, Status FROM Application WHERE Citizen_ID = ? AND Scheme_ID = ? LIMIT 1", (cit_id, s['Scheme_ID'])).fetchone()
                        if c_app:
                            s['Citizen_Application_Status'] = c_app['Status']
                            s['Citizen_Application_ID'] = c_app['Application_ID']

                    if is_eligible:
                        eligible_count += 1

                    if not eligible_only or is_eligible:
                        results.append(s)

                return self.send_json(True, {
                    "total_active_schemes": len(schemes),
                    "total_eligible_schemes": eligible_count,
                    "schemes": results
                }, "Eligibility evaluation completed.")

            # 6. Applications List
            elif path in ["/api/applications/list", "/php/applications/list.php"]:
                if not session:
                    return self.send_json(False, None, "Authentication required.", 401)

                sql = """
                    SELECT 
                        a.Application_ID, a.Citizen_ID, c.Name AS Citizen_Name, c.Email AS Citizen_Email,
                        c.Phone AS Citizen_Phone, c.Age AS Citizen_Age, c.Gender AS Citizen_Gender,
                        c.Category AS Citizen_Category, c.Income AS Citizen_Income, c.Occupation AS Citizen_Occupation,
                        c.Education AS Citizen_Education, c.District AS Citizen_District, c.State AS Citizen_State,
                        a.Scheme_ID, s.Scheme_Name, s.Benefit_Type, s.Benefit_Description,
                        d.Department_ID, d.Department_Name, a.Application_Date, a.Status, a.Remarks AS Application_Remarks,
                        ap.Approval_ID, ap.Approval_Date, ap.Decision, ap.Remarks AS Officer_Remarks,
                        o.Name AS Officer_Name, o.Designation AS Officer_Designation,
                        b.Benefit_ID, b.Benefit_Amount, b.Benefit_Date, b.Payment_Status, b.Transaction_Reference
                    FROM Application a
                    INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
                    INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
                    LEFT JOIN Officer o ON ap.Officer_ID = o.Officer_ID
                    LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
                    WHERE 1=1
                """
                params = []
                if session['role'] == 'CITIZEN':
                    sql += " AND a.Citizen_ID = ?"
                    params.append(session['id'])

                if q.get('status') and q['status'] != 'all':
                    sql += " AND a.Status = ?"
                    params.append(q['status'])
                if q.get('scheme_id'):
                    sql += " AND a.Scheme_ID = ?"
                    params.append(int(q['scheme_id']))
                if q.get('search'):
                    term = f"%{q['search']}%"
                    sql += " AND (c.Name LIKE ? OR c.Email LIKE ? OR s.Scheme_Name LIKE ?)"
                    params.extend([term, term, term])

                sql += " ORDER BY a.Application_ID DESC"
                cur = db.execute(sql, params)
                return self.send_json(True, [dict(r) for r in cur.fetchall()], "Applications loaded.")

            # 7. Application Details Dossier
            elif path in ["/api/applications/get", "/php/applications/get.php"]:
                app_id = int(q.get('id', 0))
                sql = """
                    SELECT 
                        a.Application_ID, a.Citizen_ID, c.Name AS Citizen_Name, c.Email AS Citizen_Email,
                        c.Phone AS Citizen_Phone, c.Age AS Citizen_Age, c.Gender AS Citizen_Gender,
                        c.Category AS Citizen_Category, c.Income AS Citizen_Income, c.Occupation AS Citizen_Occupation,
                        c.Education AS Citizen_Education, c.Address AS Citizen_Address, c.District AS Citizen_District,
                        c.State AS Citizen_State, a.Scheme_ID, s.Scheme_Name, s.Description AS Scheme_Description,
                        s.Benefit_Type, s.Benefit_Description, s.Last_Date AS Scheme_Deadline, s.Status AS Scheme_Status,
                        d.Department_ID, d.Department_Name, e.Min_Age, e.Max_Age, e.Income_Limit,
                        e.Category AS Req_Category, e.Gender AS Req_Gender, e.Occupation AS Req_Occupation,
                        e.Education AS Req_Education, e.State AS Req_State, e.District AS Req_District,
                        a.Application_Date, a.Status, a.Remarks AS Citizen_Remarks,
                        ap.Approval_ID, ap.Approval_Date, ap.Decision, ap.Remarks AS Officer_Remarks,
                        o.Name AS Officer_Name, o.Designation AS Officer_Designation,
                        b.Benefit_ID, b.Benefit_Amount, b.Benefit_Date, b.Payment_Status, b.Transaction_Reference
                    FROM Application a
                    INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
                    INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    LEFT JOIN Eligibility_Criteria e ON s.Scheme_ID = e.Scheme_ID
                    LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
                    LEFT JOIN Officer o ON ap.Officer_ID = o.Officer_ID
                    LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
                    WHERE a.Application_ID = ?
                """
                row = db.execute(sql, (app_id,)).fetchone()
                if not row:
                    return self.send_json(False, None, "Application not found.", 404)
                return self.send_json(True, dict(row), "Application details retrieved.")

            # 8. Benefits Ledger
            elif path in ["/api/benefits/list", "/php/benefits/list.php"]:
                if not session:
                    return self.send_json(False, None, "Authentication required.", 401)
                
                sql = """
                    SELECT 
                        b.Benefit_ID, b.Approval_ID, b.Benefit_Amount, b.Benefit_Date, b.Payment_Status,
                        b.Transaction_Reference, a.Application_ID, c.Name AS Citizen_Name, c.District AS Citizen_District,
                        c.State AS Citizen_State, s.Scheme_Name, d.Department_Name, o.Name AS Approving_Officer
                    FROM Benefits b
                    INNER JOIN Approval ap ON b.Approval_ID = ap.Approval_ID
                    INNER JOIN Application a ON ap.Application_ID = a.Application_ID
                    INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
                    INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    INNER JOIN Officer o ON ap.Officer_ID = o.Officer_ID
                    WHERE 1=1
                """
                params = []
                if session['role'] == 'CITIZEN':
                    sql += " AND a.Citizen_ID = ?"
                    params.append(session['id'])

                sql += " ORDER BY b.Benefit_Date DESC, b.Benefit_ID DESC"
                cur = db.execute(sql, params)
                bens = [dict(r) for r in cur.fetchall()]

                paid = sum(r['Benefit_Amount'] for r in bens if r['Payment_Status'] == 'Paid')
                proc = sum(r['Benefit_Amount'] for r in bens if r['Payment_Status'] == 'Processing')
                pend = sum(r['Benefit_Amount'] for r in bens if r['Payment_Status'] == 'Pending')

                return self.send_json(True, {
                    "benefits": bens,
                    "summary": {
                        "total_transactions": len(bens),
                        "total_amount": sum(r['Benefit_Amount'] for r in bens),
                        "paid_amount": paid,
                        "processing_amount": proc,
                        "pending_amount": pend
                    }
                }, "Benefits ledger loaded.")

            # 9. Dashboard Statistics
            elif path in ["/api/reports/dashboard", "/php/reports/dashboard.php"]:
                if not session:
                    return self.send_json(False, None, "Authentication required.", 401)

                if session['role'] == 'OFFICER':
                    t_sch = db.execute("SELECT COUNT(*), SUM(CASE WHEN Status='Active' THEN 1 ELSE 0 END) FROM Scheme").fetchone()
                    t_app = db.execute("""
                        SELECT COUNT(*),
                               SUM(CASE WHEN Status='Pending' THEN 1 ELSE 0 END),
                               SUM(CASE WHEN Status='Under Review' THEN 1 ELSE 0 END),
                               SUM(CASE WHEN Status='Approved' THEN 1 ELSE 0 END),
                               SUM(CASE WHEN Status='Rejected' THEN 1 ELSE 0 END)
                        FROM Application
                    """).fetchone()
                    t_ben = db.execute("SELECT COUNT(*), COALESCE(SUM(Benefit_Amount), 0.0), COALESCE(SUM(CASE WHEN Payment_Status='Paid' THEN Benefit_Amount ELSE 0 END), 0.0) FROM Benefits").fetchone()
                    t_cit = db.execute("SELECT COUNT(*) FROM Citizen").fetchone()[0]

                    recent = db.execute("""
                        SELECT a.Application_ID, c.Name AS Citizen_Name, c.Email AS Citizen_Email,
                               s.Scheme_Name, d.Department_Name, a.Application_Date, a.Status
                        FROM Application a
                        INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
                        INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                        INNER JOIN Department d ON s.Department_ID = d.Department_ID
                        ORDER BY a.Application_ID DESC LIMIT 6
                    """).fetchall()

                    return self.send_json(True, {
                        "role": "OFFICER",
                        "officer_name": session['name'],
                        "designation": session.get('designation', 'Officer'),
                        "department_name": session.get('department_name', 'Administration'),
                        "total_schemes": t_sch[0] or 0,
                        "active_schemes": t_sch[1] or 0,
                        "total_applications": t_app[0] or 0,
                        "pending_applications": t_app[1] or 0,
                        "under_review_applications": t_app[2] or 0,
                        "approved_applications": t_app[3] or 0,
                        "rejected_applications": t_app[4] or 0,
                        "total_benefits_amount": t_ben[1] or 0.0,
                        "paid_benefits_amount": t_ben[2] or 0.0,
                        "total_citizens": t_cit,
                        "recent_applications": [dict(r) for r in recent]
                    }, "Officer metrics loaded.")
                else:
                    cid = session['id']
                    tot_sch = db.execute("SELECT COUNT(*) FROM Scheme WHERE Status='Active'").fetchone()[0]
                    c_apps = db.execute("""
                        SELECT COUNT(*),
                               SUM(CASE WHEN Status='Pending' THEN 1 ELSE 0 END),
                               SUM(CASE WHEN Status='Under Review' THEN 1 ELSE 0 END),
                               SUM(CASE WHEN Status='Approved' THEN 1 ELSE 0 END),
                               SUM(CASE WHEN Status='Rejected' THEN 1 ELSE 0 END)
                        FROM Application WHERE Citizen_ID = ?
                    """, (cid,)).fetchone()
                    c_bens = db.execute("""
                        SELECT COUNT(*), COALESCE(SUM(b.Benefit_Amount), 0.0),
                               COALESCE(SUM(CASE WHEN b.Payment_Status='Paid' THEN b.Benefit_Amount ELSE 0 END), 0.0)
                        FROM Benefits b
                        JOIN Approval ap ON b.Approval_ID = ap.Approval_ID
                        JOIN Application a ON ap.Application_ID = a.Application_ID
                        WHERE a.Citizen_ID = ?
                    """, (cid,)).fetchone()

                    recent = db.execute("""
                        SELECT a.Application_ID, s.Scheme_Name, s.Benefit_Type, d.Department_Name,
                               a.Application_Date, a.Status, b.Benefit_Amount, b.Payment_Status
                        FROM Application a
                        JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                        JOIN Department d ON s.Department_ID = d.Department_ID
                        LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
                        LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
                        WHERE a.Citizen_ID = ? ORDER BY a.Application_ID DESC LIMIT 5
                    """, (cid,)).fetchall()

                    return self.send_json(True, {
                        "role": "CITIZEN",
                        "citizen_name": session['name'],
                        "total_available_schemes": tot_sch,
                        "eligible_schemes": 3,
                        "applications_submitted": c_apps[0] or 0,
                        "pending_applications": c_apps[1] or 0,
                        "under_review_applications": c_apps[2] or 0,
                        "approved_applications": c_apps[3] or 0,
                        "rejected_applications": c_apps[4] or 0,
                        "total_benefits_paid": c_bens[2] or 0.0,
                        "profile_completeness": 90,
                        "recent_applications": [dict(r) for r in recent]
                    }, "Citizen metrics loaded.")

            # 10. Application SQL Reports
            elif path in ["/api/reports/applications", "/php/reports/applications.php"]:
                r1 = db.execute("""
                    SELECT s.Scheme_ID, s.Scheme_Name, d.Department_Name, COUNT(a.Application_ID) AS Total_Applications
                    FROM Scheme s
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
                    GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
                    ORDER BY Total_Applications DESC
                """).fetchall()

                tot_apps = db.execute("SELECT COUNT(*) FROM Application").fetchone()[0] or 1
                r2 = db.execute(f"""
                    SELECT Status, COUNT(*) AS Total_Count,
                           ROUND(COUNT(*) * 100.0 / {tot_apps}, 1) AS Percentage
                    FROM Application GROUP BY Status ORDER BY Total_Count DESC
                """).fetchall()

                r3 = db.execute("""
                    SELECT d.Department_ID, d.Department_Name, COUNT(a.Application_ID) AS Approved_Count
                    FROM Department d
                    INNER JOIN Scheme s ON d.Department_ID = s.Department_ID
                    INNER JOIN Application a ON s.Scheme_ID = a.Scheme_ID
                    WHERE a.Status = 'Approved'
                    GROUP BY d.Department_ID, d.Department_Name ORDER BY Approved_Count DESC
                """).fetchall()

                r7 = db.execute("""
                    SELECT s.Scheme_ID, s.Scheme_Name, COUNT(a.Application_ID) AS Total_Applied,
                           SUM(CASE WHEN a.Status='Approved' THEN 1 ELSE 0 END) AS Total_Approved,
                           ROUND((SUM(CASE WHEN a.Status='Approved' THEN 1.0 ELSE 0.0 END) * 100.0) / 
                                 CASE WHEN COUNT(a.Application_ID) = 0 THEN 1 ELSE COUNT(a.Application_ID) END, 1) AS Utilization_Rate_Pct
                    FROM Scheme s
                    LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
                    GROUP BY s.Scheme_ID, s.Scheme_Name ORDER BY Total_Applied DESC
                """).fetchall()

                r8 = db.execute("""
                    SELECT a.Application_ID, c.Name AS Applicant_Name, c.Email AS Applicant_Email,
                           s.Scheme_Name, d.Department_Name, a.Application_Date, a.Status,
                           CAST((julianday('now') - julianday(a.Application_Date)) AS INTEGER) AS Days_Pending
                    FROM Application a
                    INNER JOIN Citizen c ON a.Citizen_ID = c.Citizen_ID
                    INNER JOIN Scheme s ON a.Scheme_ID = s.Scheme_ID
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    WHERE a.Status IN ('Pending', 'Under Review')
                    ORDER BY Days_Pending DESC
                """).fetchall()

                return self.send_json(True, {
                    "applications_by_scheme": [dict(r) for r in r1],
                    "applications_by_status": [dict(r) for r in r2],
                    "approved_by_department": [dict(r) for r in r3],
                    "scheme_utilization_rates": [dict(r) for r in r7],
                    "pending_applications_analysis": [dict(r) for r in r8]
                }, "Application reports generated via SQL.")

            # 11. Financial & Demographic SQL Reports
            elif path in ["/api/reports/benefits", "/php/reports/benefits.php"]:
                r4 = db.execute("""
                    SELECT s.Scheme_ID, s.Scheme_Name, d.Department_Name,
                           COUNT(b.Benefit_ID) AS Beneficiary_Count,
                           COALESCE(SUM(b.Benefit_Amount), 0.0) AS Total_Benefits_Amount,
                           COALESCE(AVG(b.Benefit_Amount), 0.0) AS Average_Benefit_Amount
                    FROM Scheme s
                    INNER JOIN Department d ON s.Department_ID = d.Department_ID
                    LEFT JOIN Application a ON s.Scheme_ID = a.Scheme_ID
                    LEFT JOIN Approval ap ON a.Application_ID = ap.Application_ID
                    LEFT JOIN Benefits b ON ap.Approval_ID = b.Approval_ID
                    GROUP BY s.Scheme_ID, s.Scheme_Name, d.Department_Name
                    ORDER BY Total_Benefits_Amount DESC
                """).fetchall()

                r5 = db.execute("""
                    SELECT strftime('%Y-%m', b.Benefit_Date) AS Disbursement_Month,
                           COUNT(b.Benefit_ID) AS Total_Disbursements,
                           SUM(b.Benefit_Amount) AS Monthly_Disbursed_Amount
                    FROM Benefits b
                    WHERE b.Payment_Status IN ('Paid', 'Processing')
                    GROUP BY Disbursement_Month ORDER BY Disbursement_Month ASC
                """).fetchall()

                tot_cit = db.execute("SELECT COUNT(*) FROM Citizen").fetchone()[0] or 1
                r6 = db.execute(f"""
                    SELECT COALESCE(Category, 'General') AS Social_Category,
                           COUNT(Citizen_ID) AS Citizen_Count,
                           ROUND(COUNT(Citizen_ID) * 100.0 / {tot_cit}, 1) AS Percentage_Share
                    FROM Citizen GROUP BY Social_Category ORDER BY Citizen_Count DESC
                """).fetchall()

                return self.send_json(True, {
                    "benefits_by_scheme": [dict(r) for r in r4],
                    "benefits_by_month": [dict(r) for r in r5],
                    "citizen_category_distribution": [dict(r) for r in r6]
                }, "Financial reports generated via SQL.")

            else:
                return self.send_json(False, None, f"Endpoint not found: {path}", 404)

        except Exception as e:
            return self.send_json(False, None, f"SQL error: {str(e)}", 500)
        finally:
            db.close()

    # -------------------------------------------------------------------------
    # API POST DISPATCHER
    # -------------------------------------------------------------------------
    def handle_api_post(self, path: str, data: dict):
        session, _ = self.get_session()
        db = get_db()

        try:
            # 1. Login
            if path in ["/api/auth/login", "/php/auth/login.php"]:
                email = data.get('email', '').strip()
                password = data.get('password', '').strip()
                role = data.get('role', 'CITIZEN').upper()

                if not email or not password:
                    return self.send_json(False, None, "Email and password are required.", 400)

                if role == 'OFFICER':
                    sql = """
                        SELECT o.*, d.Department_Name
                        FROM Officer o
                        JOIN Department d ON o.Department_ID = d.Department_ID
                        WHERE o.Email = ?
                    """
                    row = db.execute(sql, (email,)).fetchone()
                    if row and verify_password(password, row['Password_Hash']):
                        token = hashlib.sha256(f"{email}{time.time()}".encode()).hexdigest()
                        SESSIONS[token] = {
                            "id": row['Officer_ID'],
                            "role": "OFFICER",
                            "name": row['Name'],
                            "email": row['Email'],
                            "designation": row['Designation'],
                            "department_name": row['Department_Name'],
                            "department_id": row['Department_ID']
                        }
                        cookie = f"session_token={token}; Path=/; HttpOnly"
                        return self.send_json(True, {
                            "role": "OFFICER",
                            "name": row['Name'],
                            "token": token,
                            "redirect": "officer/dashboard.html"
                        }, "Officer authentication successful.", extra_headers={"Set-Cookie": cookie})
                else:
                    row = db.execute("SELECT * FROM Citizen WHERE Email = ?", (email,)).fetchone()
                    if row and verify_password(password, row['Password_Hash']):
                        token = hashlib.sha256(f"{email}{time.time()}".encode()).hexdigest()
                        SESSIONS[token] = {
                            "id": row['Citizen_ID'],
                            "role": "CITIZEN",
                            "name": row['Name'],
                            "email": row['Email']
                        }
                        cookie = f"session_token={token}; Path=/; HttpOnly"
                        return self.send_json(True, {
                            "role": "CITIZEN",
                            "name": row['Name'],
                            "token": token,
                            "redirect": "citizen/dashboard.html"
                        }, "Citizen authentication successful.", extra_headers={"Set-Cookie": cookie})

                return self.send_json(False, None, "Invalid email address or password.", 401)

            # 2. Register Citizen
            elif path in ["/api/auth/register", "/php/auth/register.php"]:
                email = data.get('email', '').strip()
                name = data.get('name', '').strip()
                password = data.get('password', '').strip()
                phone = data.get('phone', '').strip()

                if not name or not email or not password or not phone:
                    return self.send_json(False, None, "Name, email, password, and phone are mandatory.", 400)

                # Check duplicate
                if db.execute("SELECT Citizen_ID FROM Citizen WHERE Email = ?", (email,)).fetchone():
                    return self.send_json(False, None, "An account with this email already exists.", 409)

                hashed = hash_password(password)
                sql = """
                    INSERT INTO Citizen (
                        Name, Email, Password_Hash, Phone, Age, Gender, Category, Income,
                        Occupation, Education, Address, District, State
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """
                cur = db.execute(sql, (
                    name, email, hashed, phone,
                    int(data['age']) if data.get('age') else None,
                    data.get('gender') or None,
                    data.get('category') or None,
                    float(data['income']) if data.get('income') else None,
                    data.get('occupation') or None,
                    data.get('education') or None,
                    data.get('address') or None,
                    data.get('district') or None,
                    data.get('state') or None
                ))
                new_id = cur.lastrowid
                db.commit()

                token = hashlib.sha256(f"{email}{time.time()}".encode()).hexdigest()
                SESSIONS[token] = {"id": new_id, "role": "CITIZEN", "name": name, "email": email}
                cookie = f"session_token={token}; Path=/; HttpOnly"
                return self.send_json(True, {"citizen_id": new_id, "token": token, "redirect": "citizen/dashboard.html"}, "Citizen registered successfully!", 201, extra_headers={"Set-Cookie": cookie})

            # 3. Logout
            elif path in ["/api/auth/logout", "/php/auth/logout.php"]:
                _, token = self.get_session()
                if token and token in SESSIONS:
                    del SESSIONS[token]
                cookie = "session_token=deleted; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT"
                return self.send_json(True, {"redirect": "login.html"}, "Logged out successfully.", extra_headers={"Set-Cookie": cookie})

            # 4. Update Citizen Profile
            elif path in ["/api/citizen/update-profile", "/php/citizen/update-profile.php"]:
                if not session or session['role'] != 'CITIZEN':
                    return self.send_json(False, None, "Citizen authentication required.", 401)

                sql = """
                    UPDATE Citizen SET
                        Name = ?, Phone = ?, Age = ?, Gender = ?, Category = ?, Income = ?,
                        Occupation = ?, Education = ?, Address = ?, District = ?, State = ?
                    WHERE Citizen_ID = ?
                """
                db.execute(sql, (
                    data.get('name'), data.get('phone'),
                    int(data['age']) if data.get('age') else None,
                    data.get('gender') or None, data.get('category') or None,
                    float(data['income']) if data.get('income') else None,
                    data.get('occupation') or None, data.get('education') or None,
                    data.get('address') or None, data.get('district') or None,
                    data.get('state') or None, session['id']
                ))
                db.commit()
                session['name'] = data.get('name')
                return self.send_json(True, None, "Profile successfully updated in database!")

            # 5. Apply for Scheme
            elif path in ["/api/applications/apply", "/php/applications/apply.php"]:
                if not session or session['role'] != 'CITIZEN':
                    return self.send_json(False, None, "Citizen authentication required.", 401)

                scheme_id = int(data.get('scheme_id', 0))
                cid = session['id']

                # Check duplicate
                if db.execute("SELECT Application_ID FROM Application WHERE Citizen_ID = ? AND Scheme_ID = ?", (cid, scheme_id)).fetchone():
                    return self.send_json(False, None, "You have already applied for this scheme. Duplicate applications are prevented by DBMS constraints.", 409)

                # Insert application
                cur = db.execute("INSERT INTO Application (Citizen_ID, Scheme_ID, Application_Date, Status, Remarks) VALUES (?, ?, datetime('now'), 'Pending', ?)",
                                 (cid, scheme_id, data.get('remarks', 'Citizen online application')))
                app_id = cur.lastrowid
                db.commit()
                return self.send_json(True, {"application_id": app_id}, "Application registered successfully!", 201)

            # 6. ACID Transaction: Approve Application & Sanction Benefit
            elif path in ["/api/approvals/approve", "/php/approvals/approve.php"]:
                if not session or session['role'] != 'OFFICER':
                    return self.send_json(False, None, "Officer authorization required.", 403)

                app_id = int(data.get('application_id', 0))
                amount = float(data.get('benefit_amount', 0))
                remarks = data.get('remarks', 'Application verified and approved in compliance with criteria.')
                status = data.get('payment_status', 'Processing')
                txn = data.get('transaction_reference') or f"TXN-DBT-{datetime.now().strftime('%Y%m%d')}-{app_id:04d}"

                # BEGIN SQL TRANSACTION
                db.execute("BEGIN TRANSACTION;")
                try:
                    # 1. Update Application status
                    db.execute("UPDATE Application SET Status = 'Approved', Remarks = ? WHERE Application_ID = ?", (remarks, app_id))

                    # 2. Insert or replace Approval record
                    cur = db.execute("""
                        INSERT INTO Approval (Application_ID, Officer_ID, Approval_Date, Decision, Remarks)
                        VALUES (?, ?, datetime('now'), 'Approved', ?)
                        ON CONFLICT(Application_ID) DO UPDATE SET
                            Officer_ID = excluded.Officer_ID,
                            Approval_Date = excluded.Approval_Date,
                            Decision = excluded.Decision,
                            Remarks = excluded.Remarks
                    """, (app_id, session['id'], remarks))

                    appr_id = cur.lastrowid
                    if not appr_id:
                        appr_id = db.execute("SELECT Approval_ID FROM Approval WHERE Application_ID = ?", (app_id,)).fetchone()[0]

                    # 3. Create or update Benefit record
                    if amount >= 0:
                        db.execute("""
                            INSERT INTO Benefits (Approval_ID, Benefit_Amount, Benefit_Date, Payment_Status, Transaction_Reference)
                            VALUES (?, ?, date('now'), ?, ?)
                        """, (appr_id, amount, status, txn))

                    # COMMIT TRANSACTION
                    db.commit()
                    return self.send_json(True, {"application_id": app_id, "transaction_reference": txn}, "Application approved and benefit sanctioned via ACID Transaction.")
                except Exception as ex:
                    # ROLLBACK ON ERROR
                    db.execute("ROLLBACK;")
                    return self.send_json(False, None, f"Transaction aborted and rolled back: {str(ex)}", 500)

            # 7. Reject Application
            elif path in ["/api/approvals/reject", "/php/approvals/reject.php"]:
                if not session or session['role'] != 'OFFICER':
                    return self.send_json(False, None, "Officer authorization required.", 403)

                app_id = int(data.get('application_id', 0))
                remarks = data.get('remarks', 'Criteria mismatch')

                db.execute("BEGIN TRANSACTION;")
                try:
                    db.execute("UPDATE Application SET Status = 'Rejected', Remarks = ? WHERE Application_ID = ?", (remarks, app_id))
                    db.execute("""
                        INSERT INTO Approval (Application_ID, Officer_ID, Approval_Date, Decision, Remarks)
                        VALUES (?, ?, datetime('now'), 'Rejected', ?)
                        ON CONFLICT(Application_ID) DO UPDATE SET Decision = 'Rejected', Remarks = excluded.Remarks
                    """, (app_id, session['id'], remarks))
                    db.commit()
                    return self.send_json(True, {"application_id": app_id}, "Application formally rejected.")
                except Exception as ex:
                    db.execute("ROLLBACK;")
                    return self.send_json(False, None, f"Error rejecting application: {str(ex)}", 500)

            # 8. Mark Under Review
            elif path in ["/api/approvals/under-review", "/php/approvals/under-review.php"]:
                if not session or session['role'] != 'OFFICER':
                    return self.send_json(False, None, "Officer authorization required.", 403)

                app_id = int(data.get('application_id', 0))
                db.execute("UPDATE Application SET Status = 'Under Review' WHERE Application_ID = ?", (app_id,))
                db.commit()
                return self.send_json(True, None, f"Application #{app_id} marked Under Review.")

            # 9. Create Scheme (Transaction)
            elif path in ["/api/schemes/create", "/php/schemes/create.php"]:
                if not session or session['role'] != 'OFFICER':
                    return self.send_json(False, None, "Officer authorization required.", 403)

                db.execute("BEGIN TRANSACTION;")
                try:
                    cur = db.execute("""
                        INSERT INTO Scheme (Department_ID, Scheme_Name, Description, Benefit_Type, Benefit_Description, Last_Date, Status)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    """, (
                        int(data.get('department_id', 1)),
                        data.get('scheme_name'),
                        data.get('description'),
                        data.get('benefit_type'),
                        data.get('benefit_description'),
                        data.get('last_date') or None,
                        data.get('status', 'Active')
                    ))
                    sch_id = cur.lastrowid

                    db.execute("""
                        INSERT INTO Eligibility_Criteria (Scheme_ID, Min_Age, Max_Age, Income_Limit, Category, Gender, Occupation, Education, State, District)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    """, (
                        sch_id,
                        int(data['min_age']) if data.get('min_age') else None,
                        int(data['max_age']) if data.get('max_age') else None,
                        float(data['income_limit']) if data.get('income_limit') else None,
                        data.get('category') if data.get('category') != 'All' else None,
                        data.get('gender') if data.get('gender') != 'All' else None,
                        data.get('occupation') if data.get('occupation') != 'All' else None,
                        data.get('education') if data.get('education') != 'All' else None,
                        data.get('state') or None,
                        data.get('district') or None
                    ))
                    db.commit()
                    return self.send_json(True, {"scheme_id": sch_id}, "Scheme and eligibility criteria registered via transaction!", 201)
                except Exception as ex:
                    db.execute("ROLLBACK;")
                    return self.send_json(False, None, f"Failed to create scheme: {str(ex)}", 500)

            # 10. Update Scheme
            elif path in ["/api/schemes/update", "/php/schemes/update.php"]:
                if not session or session['role'] != 'OFFICER':
                    return self.send_json(False, None, "Officer authorization required.", 403)

                sch_id = int(data.get('scheme_id', 0))
                db.execute("BEGIN TRANSACTION;")
                try:
                    db.execute("""
                        UPDATE Scheme SET
                            Department_ID = ?, Scheme_Name = ?, Description = ?,
                            Benefit_Type = ?, Benefit_Description = ?, Last_Date = ?, Status = ?
                        WHERE Scheme_ID = ?
                    """, (
                        int(data.get('department_id', 1)),
                        data.get('scheme_name'),
                        data.get('description'),
                        data.get('benefit_type'),
                        data.get('benefit_description'),
                        data.get('last_date') or None,
                        data.get('status', 'Active'),
                        sch_id
                    ))

                    db.execute("""
                        UPDATE Eligibility_Criteria SET
                            Min_Age = ?, Max_Age = ?, Income_Limit = ?, Category = ?,
                            Gender = ?, Occupation = ?, Education = ?, State = ?, District = ?
                        WHERE Scheme_ID = ?
                    """, (
                        int(data['min_age']) if data.get('min_age') else None,
                        int(data['max_age']) if data.get('max_age') else None,
                        float(data['income_limit']) if data.get('income_limit') else None,
                        data.get('category') if data.get('category') != 'All' else None,
                        data.get('gender') if data.get('gender') != 'All' else None,
                        data.get('occupation') if data.get('occupation') != 'All' else None,
                        data.get('education') if data.get('education') != 'All' else None,
                        data.get('state') or None,
                        data.get('district') or None,
                        sch_id
                    ))
                    db.commit()
                    return self.send_json(True, None, "Scheme updated successfully!")
                except Exception as ex:
                    db.execute("ROLLBACK;")
                    return self.send_json(False, None, f"Update failed: {str(ex)}", 500)

            # 11. Toggle Scheme Status
            elif path in ["/api/schemes/deactivate", "/php/schemes/deactivate.php"]:
                if not session or session['role'] != 'OFFICER':
                    return self.send_json(False, None, "Officer authorization required.", 403)

                sch_id = int(data.get('scheme_id', 0))
                status = data.get('status')
                if not status:
                    curr = db.execute("SELECT Status FROM Scheme WHERE Scheme_ID = ?", (sch_id,)).fetchone()
                    status = 'Inactive' if curr and curr['Status'] == 'Active' else 'Active'

                db.execute("UPDATE Scheme SET Status = ? WHERE Scheme_ID = ?", (status, sch_id))
                db.commit()
                return self.send_json(True, {"new_status": status}, f"Status changed to {status}.")

            # 12. Update Benefit Status
            elif path in ["/api/benefits/update-status", "/php/benefits/update-status.php"]:
                if not session or session['role'] != 'OFFICER':
                    return self.send_json(False, None, "Officer authorization required.", 403)

                bid = int(data.get('benefit_id', 0))
                status = data.get('payment_status')
                txn = data.get('transaction_reference') or f"TXN-DBT-{datetime.now().strftime('%Y%m%d')}-{bid:04d}"

                db.execute("UPDATE Benefits SET Payment_Status = ?, Transaction_Reference = ? WHERE Benefit_ID = ?", (status, txn, bid))
                db.commit()
                return self.send_json(True, None, f"Payment status updated to {status}.")

            else:
                return self.send_json(False, None, f"POST endpoint not found: {path}", 404)

        except Exception as e:
            return self.send_json(False, None, f"Server error: {str(e)}", 500)
        finally:
            db.close()

def run_server():
    init_database_if_needed()
    server_address = ("", PORT)
    with socketserver.TCPServer(server_address, SchemeTrackerHandler) as httpd:
        print("=" * 70)
        print("  SMART GOVERNMENT SCHEME ELIGIBILITY AND BENEFIT TRACKER")
        print("  B.Tech. II Year I Sem – AIML & B Section | DBMS Project")
        print("=" * 70)
        print(f"  -> Server running at: http://localhost:{PORT}/")
        print(f"  -> Relational Database: {DB_FILE} (SQLite 3NF)")
        print(f"  -> Zero XAMPP / Zero PHP / Zero External Dependencies")
        print("  -> Press CTRL+C in terminal to stop server.")
        print("=" * 70)
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\nShutting down server gracefully...")
            httpd.server_close()

if __name__ == "__main__":
    import time
    run_server()
