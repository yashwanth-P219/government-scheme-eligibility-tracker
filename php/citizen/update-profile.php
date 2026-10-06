<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Citizen Profile Update Endpoint
 * Validates inputs and updates demographic and socioeconomic data
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireCitizen(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method not allowed. Use POST to update profile.', 405);
}

$input = getRequestData();
$citizenId = $_SESSION['user_id'];

$name       = trim($input['name'] ?? '');
$phone      = trim($input['phone'] ?? '');
$age        = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : null;
$gender     = !empty($input['gender']) ? trim($input['gender']) : null;
$category   = !empty($input['category']) ? trim($input['category']) : null;
$income     = isset($input['income']) && $input['income'] !== '' ? (float)$input['income'] : null;
$occupation = !empty($input['occupation']) ? trim($input['occupation']) : null;
$education  = !empty($input['education']) ? trim($input['education']) : null;
$address    = !empty($input['address']) ? trim($input['address']) : null;
$district   = !empty($input['district']) ? trim($input['district']) : null;
$state      = !empty($input['state']) ? trim($input['state']) : null;

// Validation
if (empty($name) || strlen($name) < 2) {
    jsonResponse(false, null, 'Full Name is required and must be at least 2 characters long.', 400);
}

if ($age !== null && ($age < 0 || $age > 125)) {
    jsonResponse(false, null, 'Age must be a valid number between 0 and 125.', 400);
}

if ($income !== null && $income < 0) {
    jsonResponse(false, null, 'Annual Income cannot be negative.', 400);
}

if (!empty($phone) && !preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
    jsonResponse(false, null, 'Phone number format is invalid.', 400);
}

$pdo = getDB();

try {
    $sql = "
        UPDATE Citizen SET
            Name = :name,
            Phone = :phone,
            Age = :age,
            Gender = :gender,
            Category = :category,
            Income = :income,
            Occupation = :occupation,
            Education = :education,
            Address = :address,
            District = :district,
            State = :state
        WHERE Citizen_ID = :citizen_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'        => $name,
        ':phone'       => $phone,
        ':age'         => $age,
        ':gender'      => $gender,
        ':category'    => $category,
        ':income'      => $income,
        ':occupation'  => $occupation,
        ':education'   => $education,
        ':address'     => $address,
        ':district'    => $district,
        ':state'       => $state,
        ':citizen_id'  => $citizenId
    ]);

    // Update session name if changed
    $_SESSION['name'] = $name;

    jsonResponse(true, null, 'Citizen profile successfully updated in database!');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error updating profile: ' . $e->getMessage(), 500);
}
