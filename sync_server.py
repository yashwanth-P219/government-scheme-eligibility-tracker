#!/usr/bin/env python3
"""
Smart Government Scheme Tracker - Automatic Database Sync Server
Academic Context: B.Tech. II Year I Sem - AIML & B Section
Subject: Database Management System (DBMS)

This server:
1. Automatically generates government_scheme_tracker.db if not present.
2. Serves static assets (HTML, CSS, JS, WASM with correct application/wasm MIME).
3. Automatically serves government_scheme_tracker.db to the browser on page load.
4. Automatically receives database saves from the browser (/api/save-database) 
   and writes directly to government_scheme_tracker.db on disk (Zero Clicks!).
5. Supports /api/reset-database to automatically re-seed the SQLite file from SQL scripts.
"""

import http.server
import socketserver
import os
import sys
import json
import sqlite3

PORT = 5000
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_FILE = os.path.join(BASE_DIR, "government_scheme_tracker.db")
SQLITE_FILE = os.path.join(BASE_DIR, "government_scheme_tracker.sqlite")
SCHEMA_SQL = os.path.join(BASE_DIR, "database", "schema.sql")
SEED_SQL = os.path.join(BASE_DIR, "database", "seed.sql")

def ensure_database():
    """Ensure government_scheme_tracker.db exists and contains all 8 tables."""
    needs_build = not os.path.exists(DB_FILE) or os.path.getsize(DB_FILE) < 1000
    if not needs_build:
        try:
            conn = sqlite3.connect(DB_FILE)
            cur = conn.cursor()
            cur.execute("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';")
            count = cur.fetchone()[0]
            conn.close()
            if count < 8:
                needs_build = True
        except Exception:
            needs_build = True

    if needs_build:
        print("[Auto-DB] Generating government_scheme_tracker.db automatically from SQL scripts...")
        if os.path.exists(SCHEMA_SQL) and os.path.exists(SEED_SQL):
            with open(SCHEMA_SQL, "r", encoding="utf-8") as f:
                schema = f.read()
            with open(SEED_SQL, "r", encoding="utf-8") as f:
                seed = f.read()

            for target in [DB_FILE, SQLITE_FILE]:
                if os.path.exists(target):
                    try:
                        os.remove(target)
                    except Exception:
                        pass
                conn = sqlite3.connect(target)
                conn.execute("PRAGMA foreign_keys = ON;")
                conn.executescript(schema)
                conn.executescript(seed)
                conn.commit()
                conn.close()
            print("[Auto-DB] Database generated successfully with 8 tables and seed records.")
        else:
            print("[Auto-DB] Warning: schema.sql or seed.sql not found at expected location.")
    else:
        print(f"[Auto-DB] Database ready: {os.path.basename(DB_FILE)} ({os.path.getsize(DB_FILE)} bytes)")

class AutoSyncHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=BASE_DIR, **kwargs)

    def end_headers(self):
        # Disable caching for database and wasm so changes are immediate
        if self.path.endswith('.db') or self.path.endswith('.sqlite') or '/api/' in self.path:
            self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
            self.send_header('Pragma', 'no-cache')
            self.send_header('Expires', '0')
        super().end_headers()

    def guess_type(self, path):
        if path.endswith('.wasm'):
            return 'application/wasm'
        if path.endswith('.db') or path.endswith('.sqlite'):
            return 'application/vnd.sqlite3'
        return super().guess_type(path)

    def do_POST(self):
        clean_path = self.path.split('?')[0].rstrip('/')
        
        # 1. Automatic Save Database from Browser
        if clean_path.endswith('/api/save-database'):
            try:
                content_len = int(self.headers.get('Content-Length', 0))
                if content_len <= 0:
                    self._send_json({"error": "Empty database payload"}, 400)
                    return

                binary_data = self.rfile.read(content_len)
                
                # Write to both .db and .sqlite on disk
                with open(DB_FILE, 'wb') as f:
                    f.write(binary_data)
                with open(SQLITE_FILE, 'wb') as f:
                    f.write(binary_data)

                # Also write to parent directory if running from subfolder
                parent_db = os.path.join(os.path.dirname(BASE_DIR), "government_scheme_tracker.db")
                parent_sqlite = os.path.join(os.path.dirname(BASE_DIR), "government_scheme_tracker.sqlite")
                if os.path.exists(os.path.dirname(BASE_DIR)):
                    try:
                        with open(parent_db, 'wb') as f:
                            f.write(binary_data)
                        with open(parent_sqlite, 'wb') as f:
                            f.write(binary_data)
                    except Exception:
                        pass

                print(f"[Auto-Sync] Received and saved {len(binary_data)} bytes to {os.path.basename(DB_FILE)} on disk.")
                self._send_json({"status": "ok", "message": "Saved to disk successfully", "bytes": len(binary_data)})
            except Exception as e:
                print(f"[Auto-Sync] Error saving database: {e}")
                self._send_json({"error": str(e)}, 500)
            return

        # 2. Automatic Reset Database to Academic Seed Data
        if clean_path.endswith('/api/reset-database'):
            try:
                with open(SCHEMA_SQL, "r", encoding="utf-8") as f:
                    schema = f.read()
                with open(SEED_SQL, "r", encoding="utf-8") as f:
                    seed = f.read()

                for target in [DB_FILE, SQLITE_FILE]:
                    conn = sqlite3.connect(target)
                    conn.execute("PRAGMA foreign_keys = ON;")
                    conn.executescript(schema)
                    conn.executescript(seed)
                    conn.commit()
                    conn.close()

                print("[Auto-Sync] Database reset to academic seed data on disk.")
                self._send_json({"status": "ok", "message": "Database reset to academic seed data on disk"})
            except Exception as e:
                print(f"[Auto-Sync] Error resetting database: {e}")
                self._send_json({"error": str(e)}, 500)
            return

        self._send_json({"error": "Endpoint not found"}, 404)

    def _send_json(self, data, status_code=200):
        body = json.dumps(data).encode('utf-8')
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

def run(port=PORT):
    ensure_database()
    socketserver.TCPServer.allow_reuse_address = True
    with socketserver.TCPServer(("", port), AutoSyncHandler) as httpd:
        print("=" * 70)
        print(f"  AUTOMATIC DATABASE SYNC SERVER RUNNING ON: http://localhost:{port}/")
        print("  - Auto-loads government_scheme_tracker.db on page open/refresh")
        print("  - Auto-saves browser changes to government_scheme_tracker.db on disk")
        print("  - Edit with sqlite3.exe or DB Browser -> Refresh page to see changes!")
        print("  - Zero manual clicking required.")
        print("=" * 70)
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\nServer stopped.")

if __name__ == "__main__":
    p = PORT
    if len(sys.argv) > 1 and sys.argv[1].isdigit():
        p = int(sys.argv[1])
    run(p)
