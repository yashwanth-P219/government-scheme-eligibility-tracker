<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Departments Listing Endpoint
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

$pdo = getDB();

try {
    $stmt = $pdo->query("SELECT Department_ID, Department_Name, Description FROM Department ORDER BY Department_ID ASC");
    $departments = $stmt->fetchAll();
    jsonResponse(true, $departments, 'Departments loaded successfully.');
} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error: ' . $e->getMessage(), 500);
}
