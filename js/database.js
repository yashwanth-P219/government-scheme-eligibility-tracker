/**
 * Smart Government Scheme Eligibility and Benefit Tracker
 * National Citizen Welfare & Direct Benefit Transfer Portal
 * Government of India
 * File: js/database.js
 */

(function (global) {
    'use strict';

    // IndexedDB Configuration
    const IDB_NAME = 'GovSchemeTrackerDB';
    const IDB_VERSION = 1;
    const IDB_STORE = 'sqlite_data';
    const IDB_KEY = 'government_scheme_tracker.sqlite';

    let db = null;
    let SQL = null;
    let inTransaction = false;
    let isInitialized = false;
    let initPromise = null;

    // Helper: Open IndexedDB Promise
    function openIndexedDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(IDB_NAME, IDB_VERSION);
            req.onupgradeneeded = (e) => {
                const idb = e.target.result;
                if (!idb.objectStoreNames.contains(IDB_STORE)) {
                    idb.createObjectStore(IDB_STORE);
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    // Load binary from IndexedDB
    async function loadFromIndexedDB() {
        try {
            const idb = await openIndexedDB();
            return new Promise((resolve, reject) => {
                const tx = idb.transaction(IDB_STORE, 'readonly');
                const store = tx.objectStore(IDB_STORE);
                const req = store.get(IDB_KEY);
                req.onsuccess = () => resolve(req.result || null);
                req.onerror = () => reject(req.error);
            });
        } catch (e) {
            console.warn('[DB Service] IndexedDB load failed, falling back to fresh in-memory DB:', e);
            return null;
        }
    }

    // Save binary to IndexedDB
    async function saveToIndexedDB(binaryData) {
        try {
            const idb = await openIndexedDB();
            return new Promise((resolve, reject) => {
                const tx = idb.transaction(IDB_STORE, 'readwrite');
                const store = tx.objectStore(IDB_STORE);
                const req = store.put(binaryData, IDB_KEY);
                req.onsuccess = () => resolve(true);
                req.onerror = () => reject(req.error);
            });
        } catch (e) {
            console.error('[DB Service] IndexedDB save error:', e);
            return false;
        }
    }

    // Clear IndexedDB
    async function clearIndexedDB() {
        try {
            const idb = await openIndexedDB();
            return new Promise((resolve, reject) => {
                const tx = idb.transaction(IDB_STORE, 'readwrite');
                const store = tx.objectStore(IDB_STORE);
                const req = store.delete(IDB_KEY);
                req.onsuccess = () => resolve(true);
                req.onerror = () => reject(req.error);
            });
        } catch (e) {
            console.error('[DB Service] IndexedDB clear error:', e);
            return false;
        }
    }

    // Resolve path for wasm file relative to current page location
    function getWasmPath() {
        const isSubfolder = window.location.pathname.includes('/citizen/') || window.location.pathname.includes('/officer/');
        return isSubfolder ? '../lib/sql-wasm.wasm' : 'lib/sql-wasm.wasm';
    }

    // Initialize Database
    async function initializeDatabase() {
        if (isInitialized && db) return db;
        if (initPromise) return initPromise;

        initPromise = (async () => {
            console.log('[DB Service] Initializing SQLite WebAssembly...');

            // Ensure sql.js init function is loaded
            if (typeof window.initSqlJs !== 'function') {
                throw new Error('sql.js library not loaded. Please ensure sql-wasm.js is included in the HTML.');
            }

            const wasmPath = getWasmPath();
            try {
                SQL = await window.initSqlJs({
                    locateFile: (file) => {
                        if (file.endsWith('.wasm')) {
                            return wasmPath;
                        }
                        return file;
                    }
                });
            } catch (wasmErr) {
                console.warn('[DB Service] Local WASM load failed, attempting CDN fallback...', wasmErr);
                SQL = await window.initSqlJs({
                    locateFile: () => 'https://cdnjs.cloudflare.com/ajax/libs/sql.js/1.12.0/sql-wasm.wasm'
                });
            }

            // Step 1: Attempt to automatically load government_scheme_tracker.db from server disk (Zero Clicks!)
            let loadedFromDisk = false;
            try {
                const isSub = window.location.pathname.includes('/citizen/') || window.location.pathname.includes('/officer/');
                const dbFileUrl = (isSub ? '../' : '') + 'government_scheme_tracker.db?_nocache=' + Date.now();
                const res = await fetch(dbFileUrl, { cache: 'no-store' });
                if (res.ok) {
                    const buf = await res.arrayBuffer();
                    if (buf.byteLength >= 100) {
                        const u8 = new Uint8Array(buf);
                        const magic = String.fromCharCode(...u8.subarray(0, 15));
                        if (magic.startsWith('SQLite format 3')) {
                            console.log(`[DB Service] Automatically loaded database from server disk (${buf.byteLength} bytes).`);
                            db = new SQL.Database(u8);
                            db.exec('PRAGMA foreign_keys = ON;');
                            await saveToIndexedDB(u8);
                            loadedFromDisk = true;
                        }
                    }
                }
            } catch (netErr) {
                console.log('[DB Service] Server disk auto-load unavailable, checking browser storage...');
            }

            // Step 2: Fallback to IndexedDB or embedded scripts if disk file was not reachable
            if (!loadedFromDisk) {
                const savedBinary = await loadFromIndexedDB();
                if (savedBinary && savedBinary.length > 0) {
                    console.log(`[DB Service] Restoring existing database from IndexedDB (${savedBinary.length} bytes)...`);
                    db = new SQL.Database(savedBinary);
                    db.exec('PRAGMA foreign_keys = ON;');
                } else {
                    console.log('[DB Service] Initializing fresh SQLite database from schema and seed scripts...');
                    db = new SQL.Database();
                    db.exec('PRAGMA foreign_keys = ON;');
                    
                    if (typeof window.GOV_DB_SCHEMA === 'string' && typeof window.GOV_DB_SEED === 'string') {
                        db.exec(window.GOV_DB_SCHEMA);
                        db.exec(window.GOV_DB_SEED);
                    } else {
                        console.warn('[DB Service] Schema/Seed globals not yet loaded; using fallback initialization.');
                    }
                    
                    const exported = db.export();
                    await saveToIndexedDB(exported);
                    console.log('[DB Service] Fresh database persisted to IndexedDB.');
                }
            }

            isInitialized = true;
            window.dbInstance = db;
            console.log('[DB Service] SQLite WASM Database initialized successfully.');

            // Trigger global event
            window.dispatchEvent(new CustomEvent('gov-db-ready', { detail: { db } }));
            return db;
        })();

        return initPromise;
    }

    // Save database state to IndexedDB and automatically sync to server disk (Zero Clicks!)
    async function saveDatabase() {
        if (!db) return false;
        try {
            const data = db.export();
            // 1. Local browser persistence
            await saveToIndexedDB(data);

            // 2. Automatic disk sync via server endpoint (Zero manual clicks!)
            try {
                const isSub = window.location.pathname.includes('/citizen/') || window.location.pathname.includes('/officer/');
                const apiEndpoint = (isSub ? '../' : '') + 'api/save-database';
                fetch(apiEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/octet-stream' },
                    body: data
                }).catch(() => {});
            } catch (syncErr) {
                // Non-blocking for offline environments
            }

            return true;
        } catch (e) {
            console.error('[DB Service] Error saving database:', e);
            return false;
        }
    }

    // Execute raw SQL or prepared statement
    function executeQuery(sql, params = []) {
        if (!db) throw new Error('Database not initialized. Call initializeDatabase() first.');
        if (!params || params.length === 0) {
            db.exec(sql);
        } else {
            const stmt = db.prepare(sql);
            stmt.run(params);
            stmt.free();
        }
        if (!inTransaction) {
            saveDatabase();
        }
    }

    // Fetch all matching rows as array of plain objects
    function fetchAll(sql, params = []) {
        if (!db) throw new Error('Database not initialized. Call initializeDatabase() first.');
        const stmt = db.prepare(sql);
        if (params && params.length > 0) {
            stmt.bind(params);
        }
        const results = [];
        while (stmt.step()) {
            results.push(stmt.getAsObject());
        }
        stmt.free();
        return results;
    }

    // Fetch single row as plain object or null
    function fetchOne(sql, params = []) {
        const rows = fetchAll(sql, params);
        return rows.length > 0 ? rows[0] : null;
    }

    // Insert record helper returning last_insert_rowid
    async function insertRecord(table, data) {
        if (!db) throw new Error('Database not initialized.');
        const cols = Object.keys(data);
        const placeholders = cols.map(() => '?').join(', ');
        const values = Object.values(data);
        const sql = `INSERT INTO "${table}" (${cols.map(c => `"${c}"`).join(', ')}) VALUES (${placeholders});`;
        
        const stmt = db.prepare(sql);
        stmt.run(values);
        stmt.free();

        const idRow = fetchOne("SELECT last_insert_rowid() AS id;");
        const newId = idRow ? idRow.id : null;

        if (!inTransaction) {
            await saveDatabase();
        }
        return newId;
    }

    // Update record helper returning changes count
    async function updateRecord(table, data, whereClause, whereParams = []) {
        if (!db) throw new Error('Database not initialized.');
        const sets = Object.keys(data).map(k => `"${k}" = ?`).join(', ');
        const values = [...Object.values(data), ...(whereParams || [])];
        const sql = `UPDATE "${table}" SET ${sets} WHERE ${whereClause};`;

        const stmt = db.prepare(sql);
        stmt.run(values);
        stmt.free();

        const changesRow = fetchOne("SELECT changes() AS changes;");
        const count = changesRow ? changesRow.changes : 0;

        if (!inTransaction) {
            await saveDatabase();
        }
        return count;
    }

    // Delete record helper returning changes count
    async function deleteRecord(table, whereClause, whereParams = []) {
        if (!db) throw new Error('Database not initialized.');
        const sql = `DELETE FROM "${table}" WHERE ${whereClause};`;
        const stmt = db.prepare(sql);
        stmt.run(whereParams || []);
        stmt.free();

        const changesRow = fetchOne("SELECT changes() AS changes;");
        const count = changesRow ? changesRow.changes : 0;

        if (!inTransaction) {
            await saveDatabase();
        }
        return count;
    }

    // Transaction Management
    function beginTransaction() {
        if (!db) throw new Error('Database not initialized.');
        db.exec('BEGIN TRANSACTION;');
        inTransaction = true;
    }

    async function commitTransaction() {
        if (!db) throw new Error('Database not initialized.');
        db.exec('COMMIT;');
        inTransaction = false;
        await saveDatabase();
    }

    function rollbackTransaction() {
        if (!db) return;
        try {
            db.exec('ROLLBACK;');
        } catch (e) {
            console.warn('[DB Service] Rollback notice:', e);
        }
        inTransaction = false;
    }

    // Export database binary as .sqlite download
    function exportDatabase(filename = 'government_scheme_tracker.sqlite') {
        if (!db) throw new Error('Database not initialized.');
        const binary = db.export();
        const blob = new Blob([binary], { type: 'application/x-sqlite3' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(() => {
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 300);
        console.log(`[DB Service] Database successfully exported (${binary.length} bytes).`);
    }

    // Import database from file input
    async function importDatabase(file) {
        if (!file) throw new Error('No file provided for database import.');
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = async () => {
                try {
                    const u8 = new Uint8Array(reader.result);
                    // Validate SQLite magic header: "SQLite format 3\0"
                    const header = String.fromCharCode(...u8.slice(0, 16));
                    if (!header.startsWith('SQLite format 3')) {
                        throw new Error('Invalid SQLite file format. Header magic string mismatch.');
                    }

                    if (db) {
                        try { db.close(); } catch (e) {}
                    }
                    db = new SQL.Database(u8);
                    db.exec('PRAGMA foreign_keys = ON;');
                    await saveDatabase();
                    console.log('[DB Service] Database imported, saved to IndexedDB, and synced to disk.');
                    resolve(true);
                } catch (err) {
                    reject(err);
                }
            };
            reader.onerror = () => reject(reader.error);
            reader.readAsArrayBuffer(file);
        });
    }

    // Reset demo database to original schema and seed
    async function resetDemoData() {
        console.log('[DB Service] Resetting database to initial demo state...');
        await clearIndexedDB();
        if (db) {
            try { db.close(); } catch (e) {}
        }
        db = new SQL.Database();
        db.exec('PRAGMA foreign_keys = ON;');
        if (typeof window.GOV_DB_SCHEMA === 'string') {
            db.exec(window.GOV_DB_SCHEMA);
        }
        if (typeof window.GOV_DB_SEED === 'string') {
            db.exec(window.GOV_DB_SEED);
        }
        window.dbInstance = db;
        await saveDatabase();
        console.log('[DB Service] Reset completed.');
        return true;
    }

    // Expose Database Service Object globally
    global.GovDB = {
        initializeDatabase,
        executeQuery,
        fetchAll,
        fetchOne,
        insertRecord,
        updateRecord,
        deleteRecord,
        beginTransaction,
        commitTransaction,
        rollbackTransaction,
        saveDatabase,
        loadDatabase: loadFromIndexedDB,
        exportDatabase,
        importDatabase,
        resetDemoData,
        getRawDB: () => db,
        isReady: () => isInitialized && db !== null
    };

})(window);
