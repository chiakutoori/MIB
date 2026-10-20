<?php
/**
 * MIB PHOTOMAN - Unified Portal Login & Sign-Up
 * Inverted Monochromatic Theme with 1-Click Role Demo Logins
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Portal Log In / Sign Up | MIB Photoman PH';
$basePath = '';
$db = getDb();
$error = null;

// Handle 1-Click Quick Demo Login
if (isset($_GET['demo'])) {
    $demoRole = $_GET['demo'];
    $demoEmail = 'admin@mibphotoman.com';
    $redirect = 'admin/index.php';

    if ($demoRole === 'staff') {
        $demoEmail = 'marcus@mibphotoman.com';
        $redirect = 'staff/index.php';
    } elseif ($demoRole === 'client') {
        $demoEmail = 'client@gmail.com';
        $redirect = 'client/index.php';
    }

    $stmt = $db->prepare("SELECT id, full_name, email, role FROM users WHERE email = ?");
    $stmt->execute([$demoEmail]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user_id'] = $u['id'];
        setFlash('success', "Signed in as {$u['full_name']} (" . strtoupper($u['role']) . ").");
        header("Location: $redirect");
        exit;
    }
}

// Handle Traditional Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please provide both email and password.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            setFlash('success', "Welcome back, {$user['full_name']}!");

            // Route based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } elseif ($user['role'] === 'staff') {
                header("Location: staff/index.php");
            } else {
                header("Location: client/index.php");
            }
            exit;
        } else {
            $error = 'Invalid email or password credentials.';
        }
    }
}

// Handle Sign-Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['client', 'staff']) ? $_POST['role'] : 'client';

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } else {
        // Check if email already registered
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'That email address is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, phone, role_title) VALUES (?, ?, ?, ?, ?, ?)");
            $title = ($role === 'staff') ? 'Crew Member' : 'Event Host';
            $stmtInsert->execute([$fullName, $email, $hash, $role, $phone, $title]);
            
            $_SESSION['user_id'] = $db->lastInsertId();
            setFlash('success', "Account created successfully! Welcome to MIB Photoman.");
            header("Location: " . ($role === 'staff' ? "staff/index.php" : "client/index.php"));
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

  <main class="auth-wrapper" style="padding: 60px 0 100px;">
    <div class="auth-card">
      <div class="auth-header">
        <img src="assets/images/mib-logo.jpg" alt="MIB Photoman Logo" class="auth-logo">
        <h1 class="auth-title">MIB Photoman Portal</h1>
        <p class="auth-subtitle">Access your bookings, crew schedules, and souvenir documentation</p>
      </div>

      <!-- Quick 1-Click Role Demo Evaluation Box -->
      <div class="quick-demo-box">
        <h4>⚡ Quick 1-Click Prototype Demo</h4>
        <div class="quick-demo-buttons">
          <a href="login.php?demo=admin" class="quick-demo-btn">👔 Demo Admin</a>
          <a href="login.php?demo=staff" class="quick-demo-btn">📷 Demo Staff Crew</a>
          <a href="login.php?demo=client" class="quick-demo-btn">🎉 Demo Client Host</a>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="flash-msg flash-error" style="margin-top: 16px;">
          <span>⚠️ <?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <!-- Auth Tabs -->
      <div class="auth-tabs" style="margin-top: 20px;">
        <div class="auth-tab active" data-tab="login" onclick="switchAuthTab('login')">Sign In</div>
        <div class="auth-tab" data-tab="signup" onclick="switchAuthTab('signup')">Create Account</div>
      </div>

      <div class="auth-body">
        <!-- Login Form -->
        <form id="login-form" method="POST" action="login.php">
          <input type="hidden" name="action" value="login">

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="login-email">Email Address</label>
            <input type="email" id="login-email" name="email" class="form-input" placeholder="admin@mibphotoman.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="login-password">Password</label>
            <input type="password" id="login-password" name="password" class="form-input" placeholder="••••••••" required>
            <span style="font-size: 0.75rem; color: var(--color-gray-500); margin-top: 4px; display: block;">Default demo password: <code>password123</code></span>
          </div>

          <button type="submit" class="btn btn-accent" style="width: 100%; padding: 14px; font-weight: 700;">
            Sign In to Portal &rarr;
          </button>
        </form>

        <!-- Sign Up Form (Hidden by default) -->
        <form id="signup-form" method="POST" action="login.php" style="display: none;">
          <input type="hidden" name="action" value="signup">

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="signup-role">Account Type</label>
            <select id="signup-role" name="role" class="form-select">
              <option value="client">Client / Event Host</option>
              <option value="staff">MIB Crew (Photographer / Operator)</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="signup-name">Full Name</label>
            <input type="text" id="signup-name" name="full_name" class="form-input" placeholder="e.g. Maria Santos" required>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="signup-email">Email Address</label>
            <input type="email" id="signup-email" name="email" class="form-input" placeholder="maria@gmail.com" required>
          </div>

          <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="signup-phone">Mobile Number</label>
            <input type="tel" id="signup-phone" name="phone" class="form-input" placeholder="09XX-XXX-XXXX">
          </div>

          <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="signup-password">Create Password</label>
            <input type="password" id="signup-password" name="password" class="form-input" placeholder="At least 6 characters" required>
          </div>

          <button type="submit" class="btn btn-accent" style="width: 100%; padding: 14px; font-weight: 700;">
            Create Account &rarr;
          </button>
        </form>
      </div>
    </div>
  </main>

  <script>
    function switchAuthTab(tab) {
      const tabs = document.querySelectorAll('.auth-tab');
      tabs.forEach(t => t.classList.toggle('active', t.getAttribute('data-tab') === tab));
      
      const loginForm = document.getElementById('login-form');
      const signupForm = document.getElementById('signup-form');
      
      if (tab === 'login') {
        loginForm.style.display = 'block';
        signupForm.style.display = 'none';
      } else {
        loginForm.style.display = 'none';
        signupForm.style.display = 'block';
      }
    }
  </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
