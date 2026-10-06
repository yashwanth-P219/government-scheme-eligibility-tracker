<?php
/**
 * Database Management System (DBMS) Course-Based Project
 * Smart Government Scheme Eligibility and Benefit Tracker
 * 
 * Session and Access Control Management Module
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Standardized JSON API response handler
 *
 * @param bool $success
 * @param mixed $data
 * @param string $message
 * @param int $statusCode
 * @return void
 */
function jsonResponse(bool $success, $data = null, string $message = '', int $statusCode = 200): void {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
    }
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

/**
 * Parse input body whether sent via multipart/form-data or application/json
 *
 * @return array
 */
function getRequestData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }
    return $_POST;
}

/**
 * Check if a user is currently authenticated
 *
 * @return bool
 */
function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if the authenticated user is a Citizen
 *
 * @return bool
 */
function isCitizen(): bool {
    return isAuthenticated() && $_SESSION['role'] === 'CITIZEN';
}

/**
 * Check if the authenticated user is a Government Officer
 *
 * @return bool
 */
function isOfficer(): bool {
    return isAuthenticated() && $_SESSION['role'] === 'OFFICER';
}

/**
 * Retrieve current user session context
 *
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isAuthenticated()) {
        return null;
    }
    return [
        'id'            => $_SESSION['user_id'],
        'role'          => $_SESSION['role'],
        'name'          => $_SESSION['name'] ?? '',
        'email'         => $_SESSION['email'] ?? '',
        'department_id' => $_SESSION['department_id'] ?? null,
        'designation'   => $_SESSION['designation'] ?? null
    ];
}

/**
 * Enforce Citizen-only access. Redirects or returns 403 JSON if unauthorized.
 *
 * @param bool $isApiCall Set true for JSON response, false for browser redirect
 * @return void
 */
function requireCitizen(bool $isApiCall = false): void {
    if (!isCitizen()) {
        if ($isApiCall) {
            jsonResponse(false, null, 'Unauthorized: Citizen access privilege required.', 403);
        } else {
            header('Location: ../login.html?error=citizen_required');
            exit;
        }
    }
}

/**
 * Enforce Officer-only access. Redirects or returns 403 JSON if unauthorized.
 *
 * @param bool $isApiCall Set true for JSON response, false for browser redirect
 * @return void
 */
function requireOfficer(bool $isApiCall = false): void {
    if (!isOfficer()) {
        if ($isApiCall) {
            jsonResponse(false, null, 'Unauthorized: Government Officer access privilege required.', 403);
        } else {
            header('Location: ../login.html?error=officer_required');
            exit;
        }
    }
}

/**
 * Generate or get existing CSRF Token
 *
 * @return string
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 *
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
