<?php
/**
 * MIB PHOTOMAN - Sign In / Create Account Panels
 * Shared by the header modals and the standalone login.php page.
 *
 * Optional inputs: $basePath, $authPanel ('login'|'signup'), $authError, $authOld (array of prior input)
 */
$basePath = $basePath ?? '';
$authPanel = ($authPanel ?? 'login') === 'signup' ? 'signup' : 'login';
$authError = $authError ?? null;
$authOld = $authOld ?? [];
$authAction = $basePath . 'login.php';
$old = fn(string $k) => htmlspecialchars($authOld[$k] ?? '');
$oldRole = ($authOld['role'] ?? 'client') === 'staff' ? 'staff' : 'client';
?>
<div class="auth-panel" data-auth-panel>
<?php if ($authPanel === 'login'): ?>
  <div class="auth-header">
    <img src="<?= $basePath ?>assets/images/mib-logo.jpg" alt="" class="auth-logo">
    <h2 class="auth-title" id="auth-title-login">Welcome back</h2>
    <p class="auth-subtitle">Sign in to manage bookings, crew schedules and reviews.</p>
  </div>

  <div class="auth-alert" role="alert" data-auth-error <?= $authError ? '' : 'hidden' ?>><?= htmlspecialchars($authError ?? '') ?></div>

  <form class="auth-form" method="POST" action="<?= $authAction ?>" novalidate>
    <input type="hidden" name="action" value="login">

    <div class="form-group">
      <label class="form-label" for="login-email">Email address</label>
      <input type="email" id="login-email" name="email" class="form-input" autocomplete="email" placeholder="you@example.com" required value="<?= $old('email') ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="login-password">Password</label>
      <div class="password-field">
        <input type="password" id="login-password" name="password" class="form-input" autocomplete="current-password" placeholder="Enter your password" required>
        <button type="button" class="password-toggle" data-password-toggle aria-label="Show password">Show</button>
      </div>
    </div>

    <button type="submit" class="btn btn-accent auth-submit">Sign In</button>
  </form>

  <p class="auth-switch">New to MIB Photoman? <a href="<?= $authAction ?>?mode=signup" data-auth-open="signup">Create an account</a></p>

  <div class="auth-demo">
    <div class="auth-demo-label"><span>Prototype demo accounts</span></div>
    <div class="auth-demo-buttons">
      <a href="<?= $authAction ?>?demo=admin">Admin</a>
      <a href="<?= $authAction ?>?demo=staff">Staff Crew</a>
      <a href="<?= $authAction ?>?demo=client">Client</a>
    </div>
  </div>
<?php else: ?>
  <div class="auth-header">
    <img src="<?= $basePath ?>assets/images/mib-logo.jpg" alt="" class="auth-logo">
    <h2 class="auth-title" id="auth-title-signup">Create your account</h2>
    <p class="auth-subtitle">Book events, track your ₱2,000 down payment and leave reviews.</p>
  </div>

  <div class="auth-alert" role="alert" data-auth-error <?= $authError ? '' : 'hidden' ?>><?= htmlspecialchars($authError ?? '') ?></div>

  <form class="auth-form" method="POST" action="<?= $authAction ?>" novalidate>
    <input type="hidden" name="action" value="signup">

    <fieldset class="form-group auth-role">
      <legend class="form-label">I am a…</legend>
      <div class="segmented">
        <label><input type="radio" name="role" value="client" <?= $oldRole === 'client' ? 'checked' : '' ?>><span>Client / Event Host</span></label>
        <label><input type="radio" name="role" value="staff" <?= $oldRole === 'staff' ? 'checked' : '' ?>><span>MIB Crew</span></label>
      </div>
    </fieldset>

    <div class="form-group">
      <label class="form-label" for="signup-name">Full name</label>
      <input type="text" id="signup-name" name="full_name" class="form-input" autocomplete="name" placeholder="e.g. Maria Santos" required value="<?= $old('full_name') ?>">
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label class="form-label" for="signup-email">Email address</label>
        <input type="email" id="signup-email" name="email" class="form-input" autocomplete="email" placeholder="maria@gmail.com" required value="<?= $old('email') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="signup-phone">Mobile <span class="form-optional">(optional)</span></label>
        <input type="tel" id="signup-phone" name="phone" class="form-input" autocomplete="tel" placeholder="09XX-XXX-XXXX" value="<?= $old('phone') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="signup-password">Password</label>
      <div class="password-field">
        <input type="password" id="signup-password" name="password" class="form-input" autocomplete="new-password" placeholder="At least 6 characters" minlength="6" required>
        <button type="button" class="password-toggle" data-password-toggle aria-label="Show password">Show</button>
      </div>
    </div>

    <button type="submit" class="btn btn-accent auth-submit">Create Account</button>
  </form>

  <p class="auth-switch">Already have an account? <a href="<?= $authAction ?>" data-auth-open="login">Sign in</a></p>
<?php endif; ?>
</div>
