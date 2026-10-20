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
        <h4 class="footer-brand-name">MIB PHOTOMAN PH</h4>
        <p class="footer-desc">
          Suited photographers who walk around your event and print your guests' photos on the spot as magnetic souvenirs. Part of <strong>CSLB Events Planning</strong>, serving Metro Manila and nearby provinces.
        </p>
      </div>

      <div class="footer-col">
        <h4 class="footer-title">Quick Links</h4>
        <ul class="footer-links">
          <li><a href="<?= $basePath ?>index.php">Home</a></li>
          <li><a href="<?= $basePath ?>packages.php">Packages</a></li>
          <li><a href="<?= $basePath ?>booking.php">Book an Event</a></li>
          <li><a href="<?= $basePath ?>index.php#faq">FAQs</a></li>
          <li><a href="<?= $basePath ?>login.php" data-auth-open="login">Sign In</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-title">Events We Cover</h4>
        <ul class="footer-links">
          <li>Weddings & Nuptials</li>
          <li>18th Debuts & Birthdays</li>
          <li>Corporate Galas & Summits</li>
          <li>School & University Balls</li>
          <li>Product Launches & Openings</li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="footer-title">Contact Us</h4>
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
        <a href="<?= $basePath ?>index.php#faq">₱2,000 Reservation</a>
        <a href="#top" style="color: var(--color-white); font-weight: 600;">Back to Top &uarr;</a>
      </div>
    </div>
  </footer>

  <?php if (!isLoggedIn() && empty($skipAuthModal)): ?>
  <!-- Sign In / Create Account Modals -->
  <div class="auth-modal" id="auth-modal" hidden>
    <div class="auth-modal-backdrop" data-auth-close></div>
    <?php foreach (['login', 'signup'] as $authPanel): ?>
    <div class="auth-card auth-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="auth-title-<?= $authPanel ?>" data-auth-dialog="<?= $authPanel ?>" hidden>
      <button type="button" class="auth-modal-close" data-auth-close aria-label="Close">&times;</button>
      <?php require __DIR__ . '/auth-panel.php'; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <script src="<?= $basePath ?>assets/js/main.js"></script>
</body>
</html>
