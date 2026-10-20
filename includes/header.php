<?php
/**
 * MIB PHOTOMAN - Shared Header Component
 * Inverted Monochromatic Theme with Viewfinder HUD
 */

require_once __DIR__ . '/auth.php';

$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$pageTitle = $pageTitle ?? 'MIB Photoman PH | Onsite Event Photography & Instant Souvenirs';
$basePath = $basePath ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css">
  <script>document.documentElement.classList.add("js");</script>
</head>
<body class="is-loading">

  <!-- Page Loader (Camera Viewfinder HUD) -->
  <div id="page-loader" class="page-loader" role="status" aria-live="polite" aria-label="Loading MIB Photoman">
    <span class="vf-corner vf-tl"></span><span class="vf-corner vf-tr"></span>
    <span class="vf-corner vf-bl"></span><span class="vf-corner vf-br"></span>
    <div class="loader-hud loader-hud-top"><span class="rec-dot"></span> REC &nbsp;&bull;&nbsp; 4K &nbsp;&bull;&nbsp; AF-C</div>
    <div class="loader-hud loader-hud-bottom">ISO 400 &nbsp; f/2.8 &nbsp; 1/125s &nbsp; &plusmn;0.0EV</div>

    <div class="loader-center">
      <svg class="loader-lens" viewBox="0 0 120 120" aria-hidden="true">
        <circle class="lens-ring-outer" cx="60" cy="60" r="56"/>
        <circle class="lens-ring-mid" cx="60" cy="60" r="46"/>
        <g class="lens-iris">
          <circle cx="60" cy="60" r="34"/>
          <line x1="94.0" y1="60.0" x2="30.6" y2="77.0"/>
          <line x1="77.0" y1="89.4" x2="30.6" y2="43.0"/>
          <line x1="43.0" y1="89.4" x2="60.0" y2="26.0"/>
          <line x1="26.0" y1="60.0" x2="89.4" y2="43.0"/>
          <line x1="43.0" y1="30.6" x2="89.4" y2="77.0"/>
          <line x1="77.0" y1="30.6" x2="60.0" y2="94.0"/>
        </g>
      </svg>
      <div class="loader-brand">MIB PHOTOMAN PH</div>
      <div class="loader-tagline">Focusing on your moments&hellip;</div>
      <div class="loader-progress"><span id="loader-bar"></span></div>
      <div class="loader-count"><span id="loader-count">0</span>%</div>
    </div>
    <div class="loader-flash"></div>
  </div>

  <!-- Top Announcement Bar (Inverted Monochromatic) -->
  <div class="top-bar">
    <div class="container">
      <div class="top-bar-left">
        <span class="top-bar-badge">CSLB Events Planning</span>
        <span>Event photography with instant printed souvenirs</span>
      </div>
      <div class="top-bar-right">
        <span>📧 hello@mibphotoman.com</span>
        <span>📱 @mibphotomanph</span>
        <?php if ($user): ?>
          <span style="color: var(--color-white); font-weight: 600;">Signed in as: <?= htmlspecialchars($user['full_name']) ?> (<?= strtoupper($user['role']) ?>)</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Header & Navigation -->
  <header class="main-header">
    <div class="container navbar">
      <a href="<?= $basePath ?>index.php" class="brand-link">
        <img src="<?= $basePath ?>assets/images/mib-logo.jpg" alt="MIB Photoman Logo" class="brand-logo-img">
        <div class="brand-text">
          <div class="brand-title">
            MIB PHOTOMAN PH
            <span class="verified-dot" title="Verified Service"></span>
          </div>
          <span class="brand-tagline">Event Photography &bull; Instant Prints</span>
        </div>
      </a>

      <nav>
        <ul class="nav-links">
          <li><a href="<?= $basePath ?>index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
          <li><a href="<?= $basePath ?>packages.php" class="nav-link <?= $currentPage === 'packages.php' ? 'active' : '' ?>">Packages</a></li>
          <li><a href="<?= $basePath ?>booking.php" class="nav-link <?= $currentPage === 'booking.php' ? 'active' : '' ?>">Book an Event</a></li>
          
          <?php if (!$user): ?>
            <li class="nav-signin"><a href="<?= $basePath ?>login.php" class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>" data-auth-open="login">Sign In</a></li>
          <?php elseif ($user['role'] === 'admin'): ?>
            <li><a href="<?= $basePath ?>admin/index.php" class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false ? 'active' : '' ?>">Admin Panel</a></li>
          <?php elseif ($user['role'] === 'staff'): ?>
            <li><a href="<?= $basePath ?>staff/index.php" class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/staff/') !== false ? 'active' : '' ?>">Crew Schedule</a></li>
          <?php elseif ($user['role'] === 'client'): ?>
            <li><a href="<?= $basePath ?>client/index.php" class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/client/') !== false ? 'active' : '' ?>">My Bookings</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <div class="nav-actions">
        <?php if (!$user): ?>
          <a href="<?= $basePath ?>packages.php" class="btn btn-accent">Book Now!</a>
          <a href="<?= $basePath ?>login.php" class="btn btn-outline" data-auth-open="login">Sign In</a>
        <?php else: ?>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="<?= $basePath ?>admin/index.php" class="btn btn-accent">Admin Dashboard</a>
          <?php elseif ($user['role'] === 'staff'): ?>
            <a href="<?= $basePath ?>staff/index.php" class="btn btn-accent">My Assignments</a>
          <?php else: ?>
            <a href="<?= $basePath ?>client/index.php" class="btn btn-accent">My Bookings</a>
          <?php endif; ?>
          <a href="<?= $basePath ?>logout.php" class="btn btn-outline">Sign Out</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php
  // Flash messages display
  $flash = getFlash();
  if ($flash):
  ?>
  <div class="container" style="padding-top: 20px;">
    <div class="flash-msg <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>">
      <div><?= htmlspecialchars($flash['message']) ?></div>
      <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer;">&times;</button>
    </div>
  </div>
  <?php endif; ?>
