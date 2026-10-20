<?php
/**
 * MIB PHOTOMAN - Authentication & Session Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function currentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }
    $db = getDb();
    $stmt = $db->prepare("SELECT id, full_name, email, role, phone, role_title FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cachedUser = $stmt->fetch();
    return $cachedUser;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && currentUser() !== null;
}

function hasRole(string $role): bool {
    $u = currentUser();
    return $u && $u['role'] === $role;
}

function requireLogin(string $redirect = '/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect?redirect=" . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

function requireRole(string $role, string $fallback = '/index.php'): void {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: $fallback?error=unauthorized");
        exit;
    }
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
