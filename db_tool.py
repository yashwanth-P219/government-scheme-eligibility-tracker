"""
Smart Government Scheme Tracker - Interactive Database Management Utility
Academic Context: B.Tech. II Year I Sem - AIML & B Section
Subject: Database Management System (DBMS)

Run this tool to:
1. View all database tables & record counts
2. View schema / table structure (columns, types, constraints)
3. Browse and view table records
4. Execute custom SQL queries (SELECT, INSERT, UPDATE, DELETE)
5. Reset / Re-seed database from SQL script files
"""

import os
import sys
import sqlite3

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_FILE = os.path.join(BASE_DIR, "government_scheme_tracker.db")
SCHEMA_SQL = os.path.join(BASE_DIR, "database", "schema_sqlite.sql")
SEED_SQL = os.path.join(BASE_DIR, "database", "seed_sqlite.sql")

def get_db():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON;")
    return conn

def print_separator(char="-", length=75):
    print(char * length)

def list_tables(conn):
    cursor = conn.cursor()
    cursor.execute("""
        SELECT name FROM sqlite_master 
        WHERE type='table' AND name NOT LIKE 'sqlite_%' 
        ORDER BY name;
    """)
    tables = [row[0] for row in cursor.fetchall()]
    
    print("\n" + "=" * 55)
    print(f" DATABASE TABLES IN: {os.path.basename(DB_FILE)}")
    print("=" * 55)
    print(f"{'#':<4} {'Table Name':<28} {'Record Count':<12}")
    print_separator("-", 55)
    for idx, table in enumerate(tables, 1):
        cur2 = conn.cursor()
        cur2.execute(f"SELECT COUNT(*) FROM \"{table}\"")
        count = cur2.fetchone()[0]
        print(f"{idx:<4} {table:<28} {count:<12}")
    print_separator("-", 55)
    return tables

def show_table_schema(conn, table_name):
    cursor = conn.cursor()
    cursor.execute(f"PRAGMA table_info(\"{table_name}\")")
    columns = cursor.fetchall()
    
    cursor.execute(f"PRAGMA foreign_key_list(\"{table_name}\")")
    fks = cursor.fetchall()

    print("\n" + "=" * 75)
    print(f" SCHEMA / COLUMNS FOR TABLE: {table_name}")
    print("=" * 75)
    print(f"{'CID':<5} {'Column Name':<24} {'Type':<14} {'Not Null':<10} {'PK':<5}")
    print_separator("-", 75)
    for col in columns:
        cid, name, col_type, notnull, default_val, pk = col
        print(f"{cid:<5} {name:<24} {col_type:<14} {('YES' if notnull else 'NO'):<10} {('YES' if pk else 'NO'):<5}")
    
    if fks:
        print("\nForeign Keys:")
        for fk in fks:
            print(f" - {fk[3]} -> {fk[2]}({fk[4]})")
    print_separator("-", 75)

def view_records(conn, table_name, limit=20):
    cursor = conn.cursor()
    cursor.execute(f"SELECT * FROM \"{table_name}\" LIMIT {limit}")
    rows = cursor.fetchall()
    
    if not rows:
        print(f"\n[INFO] Table '{table_name}' is empty.")
        return

    col_names = [description[0] for description in cursor.description]
    
    print(f"\nShowing up to {limit} records from '{table_name}':")
    print_separator("=", 90)
    
    for idx, row in enumerate(rows, 1):
        print(f"[Record {idx}]")
        for col in col_names:
            val = row[col]
            if val is not None and len(str(val)) > 60:
                val = str(val)[:57] + "..."
            print(f"  {col:<24}: {val}")
        print_separator("-", 40)

def execute_raw_sql(conn):
    print("\n" + "=" * 65)
    print(" RAW SQL EXECUTION CONSOLE")
    print(" Type your SQL query (e.g. SELECT * FROM Citizen;)")
    print(" or type 'cancel' to return to main menu.")
    print("=" * 65)
    
    lines = []
    print("Enter SQL statement (terminate with semicolon ';' and press Enter):")
    while True:
        try:
            line = input("SQL> " if not lines else "...> ")
        except (EOFError, KeyboardInterrupt):
            print("\nCancelled.")
            return

        if not lines and line.strip().lower() == 'cancel':
            return
            
        lines.append(line)
        if ";" in line:
            break

    query = " ".join(lines).strip()
    if not query:
        return

    cursor = conn.cursor()
    try:
        cursor.execute(query)
        if cursor.description:
            col_names = [desc[0] for desc in cursor.description]
            rows = cursor.fetchall()
            print(f"\n[SUCCESS] {len(rows)} row(s) returned.\n")
            
            col_widths = [max(len(c), 10) for c in col_names]
            for row in rows[:50]:
                for i, val in enumerate(row):
                    col_widths[i] = min(max(col_widths[i], len(str(val if val is not None else "NULL"))), 35)

            header = " | ".join(f"{col_names[i]:<{col_widths[i]}}" for i in range(len(col_names)))
            print(header)
            print("-" * len(header))
            for row in rows[:50]:
                row_str = " | ".join(f"{str(row[i] if row[i] is not None else 'NULL'):<{col_widths[i]}}" for i in range(len(row)))
                print(row_str)
            if len(rows) > 50:
                print(f"... and {len(rows) - 50} more rows.")
        else:
            conn.commit()
            print(f"\n[SUCCESS] Query executed successfully. Rows affected: {cursor.rowcount}")
    except Exception as e:
        print(f"\n[ERROR] SQL Execution Failed: {e}")

def reset_database(conn):
    confirm = input("\n[WARNING] This will drop and re-create all 8 tables using schema_sqlite.sql and seed_sqlite.sql.\nAre you sure? (yes/no): ").strip().lower()
    if confirm != 'yes':
        print("Cancelled.")
        return

    conn.close()
    if os.path.exists(DB_FILE):
        try:
            os.remove(DB_FILE)
            print(f"[OK] Removed old database file: {DB_FILE}")
        except Exception as e:
            print(f"[ERROR] Could not remove DB file: {e}")
            return

    new_conn = get_db()
    with open(SCHEMA_SQL, "r", encoding="utf-8") as f:
        new_conn.executescript(f.read())
    print("[OK] Executed schema_sqlite.sql (8 tables created with constraints).")

    with open(SEED_SQL, "r", encoding="utf-8") as f:
        new_conn.executescript(f.read())
    print("[OK] Executed seed_sqlite.sql (seed data inserted).")
    print("\n[SUCCESS] Database reset and re-seeded successfully!")
    return new_conn

def main():
    if not os.path.exists(DB_FILE):
        print(f"[INFO] Database file '{DB_FILE}' not found. Initializing from SQL scripts...")
        conn = get_db()
        with open(SCHEMA_SQL, "r", encoding="utf-8") as f:
            conn.executescript(f.read())
        with open(SEED_SQL, "r", encoding="utf-8") as f:
            conn.executescript(f.read())
        print("[OK] Database initialized.")
    else:
        conn = get_db()

    while True:
        print("\n" + "=" * 55)
        print(" SMART GOVERNMENT SCHEME TRACKER - DBMS SQL TOOL")
        print("=" * 55)
        print("1. List All Database Tables & Row Counts")
        print("2. View Schema (Columns & Constraints) of a Table")
        print("3. View Records from a Table")
        print("4. Execute Custom SQL Query (SELECT / INSERT / UPDATE / DELETE)")
        print("5. Reset & Re-Seed Database from SQL Files")
        print("6. Exit")
        print("-" * 55)

        try:
            choice = input("Select an option (1-6): ").strip()
        except (EOFError, KeyboardInterrupt):
            print("\nExiting.")
            break

        if choice == "1":
            list_tables(conn)
        elif choice == "2":
            tables = list_tables(conn)
            tbl = input("\nEnter table name: ").strip()
            if tbl in tables:
                show_table_schema(conn, tbl)
            else:
                print(f"[ERROR] Table '{tbl}' not found.")
        elif choice == "3":
            tables = list_tables(conn)
            tbl = input("\nEnter table name: ").strip()
            if tbl in tables:
                limit_input = input("Enter max records to view (default 20): ").strip()
                limit = int(limit_input) if limit_input.isdigit() else 20
                view_records(conn, tbl, limit)
            else:
                print(f"[ERROR] Table '{tbl}' not found.")
        elif choice == "4":
            execute_raw_sql(conn)
        elif choice == "5":
            new_conn = reset_database(conn)
            if new_conn:
                conn = new_conn
        elif choice == "6":
            print("\nGoodbye!")
            break
        else:
            print("Invalid choice, please select 1-6.")

    conn.close()

if __name__ == "__main__":
    main()
