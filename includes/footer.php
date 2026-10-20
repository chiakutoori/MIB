<?php
/**
 * MIB PHOTOMAN - Shared Footer Component
 */
$basePath = $basePath ?? '';
?>
  <!-- Main Footer (Inverted Monochromatic) -->
  <footer class="main-footer">
    <div class="container footer-grid">
      <div class="footer-col footer-brand-col">
        <img src="<?= $basePath ?>assets/images/mib-logo.jpg" alt="MIB Photoman Logo" class="footer-logo-img">
        <h4 style="font-family: var(--font-logo); font-size: 1.1rem; letter-spacing: 0.05em; margin-bottom: 8px;">MIB PHOTOMAN PH</h4>
        <p class="footer-desc">
          Onsite event roaming photography and instant high-speed dye-sublimation souvenir printing. Operating under <strong>CSLB Events Planning</strong> across Metro Manila and surrounding provinces.
        </p>
        <p style="font-size: 0.8rem; color: var(--color-gray-500); margin-top: 8px;">
          Thesis Prototype (25% Operational Scope): Web-Based Customization, Unified Database, Staff Scheduling, Post-Event Journaling, and Centralized Notification Engine.
        </p>
      </div>

      <div class="footer-col">
        <h4 class="footer-title">System Portals</h4>
        <ul class="footer-links">
          <li><a href="<?= $basePath ?>index.php">Home & Showcase</a></li>
          <li><a href="<?= $basePath ?>packages.php">Packages & Customizer</a></li>
          <li><a href="<?= $basePath ?>booking.php">Event Booking Form</a></li>
          <li><a href="<?= $basePath ?>login.php">Unified Portal Sign In</a></li>
          <li><a href="<?= $basePath ?>admin/index.php">Admin Management</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-title">Service Coverage</h4>
        <ul class="footer-links">
          <li>Weddings & Nuptials</li>
          <li>18th Debuts & Birthdays</li>
          <li>Corporate Galas & Summits</li>
          <li>School & University Balls</li>
          <li>Product Launches & Openings</li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-title">Headquarters</h4>
        <p style="font-size: 0.85rem; color: var(--color-gray-400); margin-bottom: 12px; line-height: 1.5;">
          CSLB Events Planning Office<br>
          Metro Manila, Philippines<br>
          📞 (02) 8888-MIBP / 0917-888-MIB1<br>
          ✉️ hello@mibphotoman.com
        </p>
        <div class="footer-badge">
          <span>✓ Suited & Tied Professional Crew</span>
        </div>
      </div>
    </div>

    <div class="container footer-bottom">
      <div>&copy; <?= date('Y') ?> MIB Photoman PH &bull; All Rights Reserved.</div>
      <div style="display: flex; gap: 20px;">
        <a href="<?= $basePath ?>packages.php">Terms of Service</a>
        <a href="<?= $basePath ?>booking.php">Fixed ₱2,000 DP Policy</a>
        <a href="#top" style="color: var(--color-white); font-weight: 600;">Back to Top &uarr;</a>
      </div>
    </div>
  </footer>

  <script src="<?= $basePath ?>assets/js/main.js"></script>
</body>
</html>
