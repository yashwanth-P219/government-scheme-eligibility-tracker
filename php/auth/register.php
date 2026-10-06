<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Citizen Self-Registration Endpoint
 * Demonstrates prepared statements, duplicate checks, and password_hash()
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Only POST requests are accepted.', 405);
}

$input = getRequestData();

$name     = trim($input['name'] ?? '');
$email    = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = trim($input['password'] ?? '');
$phone    = trim($input['phone'] ?? '');

// Optional profile fields that may be provided during registration
$age        = !empty($input['age']) ? (int)$input['age'] : null;
$gender     = !empty($input['gender']) ? trim($input['gender']) : null;
$category   = !empty($input['category']) ? trim($input['category']) : null;
$income     = !empty($input['income']) ? (float)$input['income'] : null;
$occupation = !empty($input['occupation']) ? trim($input['occupation']) : null;
$education  = !empty($input['education']) ? trim($input['education']) : null;
$address    = !empty($input['address']) ? trim($input['address']) : null;
$district   = !empty($input['district']) ? trim($input['district']) : null;
$state      = !empty($input['state']) ? trim($input['state']) : null;

// Validation
if (empty($name) || strlen($name) < 2) {
    jsonResponse(false, null, 'Full Name is required and must be at least 2 characters long.', 400);
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, null, 'A valid email address is required.', 400);
}

if (empty($password) || strlen($password) < 6) {
    jsonResponse(false, null, 'Password must be at least 6 characters long.', 400);
}

if (empty($phone) || !preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
    jsonResponse(false, null, 'Please provide a valid 10-digit mobile phone number.', 400);
}

$pdo = getDB();

try {
    // Check if email already registered (Demonstrates UNIQUE constraint handling)
    $stmtCheck = $pdo->prepare("SELECT Citizen_ID FROM Citizen WHERE Email = :email LIMIT 1");
    $stmtCheck->execute([':email' => $email]);
    if ($stmtCheck->fetch()) {
        jsonResponse(false, null, 'An account with this email address is already registered. Please login instead.', 409);
    }

    // Hash password with Bcrypt
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Insert new citizen record
    $sql = "
        INSERT INTO Citizen (
            Name, Email, Password_Hash, Phone, Age, Gender, Category, Income,
            Occupation, Education, Address, District, State
        ) VALUES (
            :name, :email, :password_hash, :phone, :age, :gender, :category, :income,
            :occupation, :education, :address, :district, :state
        )
    ";

    $stmtInsert = $pdo->prepare($sql);
    $stmtInsert->execute([
        ':name'          => $name,
        ':email'         => $email,
        ':password_hash' => $passwordHash,
        ':phone'         => $phone,
        ':age'           => $age,
        ':gender'        => $gender,
        ':category'      => $category,
        ':income'        => $income,
        ':occupation'    => $occupation,
        ':education'     => $education,
        ':address'       => $address,
        ':district'      => $district,
        ':state'         => $state
    ]);

    $citizenId = (int)$pdo->lastInsertId();

    // Auto-login newly registered citizen
    session_regenerate_id(true);
    $_SESSION['user_id'] = $citizenId;
    $_SESSION['role'] = 'CITIZEN';
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;

    jsonResponse(true, [
        'citizen_id' => $citizenId,
        'name'       => $name,
        'email'      => $email,
        'redirect'   => 'citizen/dashboard.php'
    ], 'Citizen registration successful! Welcome to the portal.', 201);

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database registration error: ' . $e->getMessage(), 500);
}
