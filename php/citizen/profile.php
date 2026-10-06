<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Citizen Profile Retrieval Endpoint
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';

requireCitizen(true);

$citizenId = $_SESSION['user_id'];
$pdo = getDB();

try {
    $stmt = $pdo->prepare("
        SELECT 
            Citizen_ID, Name, Email, Age, Gender, Category, Income, 
            Occupation, Education, Address, District, State, Phone, Created_At
        FROM Citizen
        WHERE Citizen_ID = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $citizenId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        jsonResponse(false, null, 'Citizen profile record not found.', 404);
    }

    // Determine profile completeness percentage for UI indicator
    $fields = ['Name', 'Email', 'Age', 'Gender', 'Category', 'Income', 'Occupation', 'Education', 'District', 'State', 'Phone'];
    $completedCount = 0;
    foreach ($fields as $field) {
        if (!empty($profile[$field]) || (isset($profile[$field]) && is_numeric($profile[$field]))) {
            $completedCount++;
        }
    }
    $completeness = round(($completedCount / count($fields)) * 100);

    $profile['completeness'] = $completeness;

    jsonResponse(true, $profile, 'Profile retrieved successfully.');

} catch (PDOException $e) {
    jsonResponse(false, null, 'Database error while loading profile: ' . $e->getMessage(), 500);
}
