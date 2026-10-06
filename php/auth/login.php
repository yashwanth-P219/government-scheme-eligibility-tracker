<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Authentication Endpoint: Citizen and Officer Login
 * Uses password_verify() and PDO Prepared Statements
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Only POST requests are accepted.', 405);
}

$input = getRequestData();
$email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = trim($input['password'] ?? '');
$requestedRole = strtoupper(trim($input['role'] ?? 'CITIZEN')); // CITIZEN or OFFICER

if (empty($email) || empty($password)) {
    jsonResponse(false, null, 'Email address and password are required fields.', 400);
}

$pdo = getDB();

try {
    if ($requestedRole === 'OFFICER') {
        // Query Officer table
        $stmt = $pdo->prepare("
            SELECT o.Officer_ID, o.Department_ID, o.Name, o.Email, o.Password_Hash, o.Designation, d.Department_Name
            FROM Officer o
            JOIN Department d ON o.Department_ID = d.Department_ID
            WHERE o.Email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $officer = $stmt->fetch();

        if ($officer && password_verify($password, $officer['Password_Hash'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$officer['Officer_ID'];
            $_SESSION['role'] = 'OFFICER';
            $_SESSION['name'] = $officer['Name'];
            $_SESSION['email'] = $officer['Email'];
            $_SESSION['department_id'] = (int)$officer['Department_ID'];
            $_SESSION['department_name'] = $officer['Department_Name'];
            $_SESSION['designation'] = $officer['Designation'];

            jsonResponse(true, [
                'role'            => 'OFFICER',
                'name'            => $officer['Name'],
                'email'           => $officer['Email'],
                'designation'     => $officer['Designation'],
                'department_name' => $officer['Department_Name'],
                'redirect'        => 'officer/dashboard.php'
            ], 'Government Officer authentication successful. Redirecting to Command Center...');
        } else {
            jsonResponse(false, null, 'Invalid credentials. Please verify your officer email and password.', 401);
        }
    } else {
        // Query Citizen table
        $stmt = $pdo->prepare("
            SELECT Citizen_ID, Name, Email, Password_Hash, Age, Gender, Category, Income, Occupation, District, State
            FROM Citizen
            WHERE Email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $citizen = $stmt->fetch();

        if ($citizen && password_verify($password, $citizen['Password_Hash'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$citizen['Citizen_ID'];
            $_SESSION['role'] = 'CITIZEN';
            $_SESSION['name'] = $citizen['Name'];
            $_SESSION['email'] = $citizen['Email'];

            jsonResponse(true, [
                'role'     => 'CITIZEN',
                'name'     => $citizen['Name'],
                'email'    => $citizen['Email'],
                'redirect' => 'citizen/dashboard.php'
            ], 'Citizen login successful. Welcome to the Smart Government Welfare Portal.');
        } else {
            jsonResponse(false, null, 'Invalid email or password. Please check your credentials and try again.', 401);
        }
    }
} catch (PDOException $e) {
    jsonResponse(false, null, 'A database error occurred during authentication: ' . $e->getMessage(), 500);
}
