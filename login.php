<?php
/**
 * MIB PHOTOMAN - Unified Portal Login & Sign-Up
 * Handles the header sign-in modal (JSON over fetch) and the standalone fallback page.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Sign In | MIB Photoman PH';
$basePath = '';
$db = getDb();
$error = null;
$authPanel = ($_GET['mode'] ?? '') === 'signup' ? 'signup' : 'login';
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function homeFor(string $role): string {
    return match ($role) {
        'admin' => 'admin/index.php',
        'staff' => 'staff/index.php',
        default => 'client/index.php',
    };
}

// Successful auth: JSON for the modal, a redirect for the plain page
function finishAuth(string $redirect, bool $wantsJson): void {
    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'redirect' => $redirect]);
    } else {
        header("Location: $redirect");
    }
    exit;
}

// Handle 1-Click Quick Demo Login
if (isset($_GET['demo'])) {
    $demoEmails = [
        'admin' => 'admin@mibphotoman.com',
        'staff' => 'marcus@mibphotoman.com',
        'client' => 'client@gmail.com',
    ];
    $demoEmail = $demoEmails[$_GET['demo']] ?? $demoEmails['admin'];

    $stmt = $db->prepare("SELECT id, full_name, email, role FROM users WHERE email = ?");
    $stmt->execute([$demoEmail]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user_id'] = $u['id'];
        setFlash('success', "Signed in as {$u['full_name']} (" . strtoupper($u['role']) . ").");
        finishAuth(homeFor($u['role']), false);
    }
}

// Handle Traditional Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $authPanel = 'login';
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            setFlash('success', "Welcome back, {$user['full_name']}!");
            finishAuth(homeFor($user['role']), $wantsJson);
        }
        $error = 'Incorrect email or password.';
    }
}

// Handle Sign-Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'signup') {
    $authPanel = 'signup';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['client', 'staff']) ? $_POST['role'] : 'client';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Please fill out your name, email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Your password must be at least 6 characters.';
    } else {
        // Check if email already registered
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'That email address is already registered. Try signing in instead.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, phone, role_title) VALUES (?, ?, ?, ?, ?, ?)");
            $title = ($role === 'staff') ? 'Crew Member' : 'Event Host';
            $stmtInsert->execute([$fullName, $email, $hash, $role, $phone, $title]);

            $_SESSION['user_id'] = $db->lastInsertId();
            setFlash('success', "Account created successfully! Welcome to MIB Photoman.");
            finishAuth(homeFor($role), $wantsJson);
        }
    }
}

if ($error && $wantsJson) {
    header('Content-Type: application/json');
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $error]);
    exit;
}

$authError = $error;
$authOld = $_POST;
$skipAuthModal = true;

require_once __DIR__ . '/includes/header.php';
?>

  <main class="auth-wrapper">
    <div class="auth-card">
      <?php require __DIR__ . '/includes/auth-panel.php'; ?>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
