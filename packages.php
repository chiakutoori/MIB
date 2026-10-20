<?php
/**
 * MIB PHOTOMAN - Packages & Customizer Showcase
 * Features package cards with photo sliders, "Book Now!" hover effect, and comparison matrix
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Package Offerings & Customizer | MIB Photoman PH';
$basePath = '';

$db = getDb();
$packages = $db->query("SELECT * FROM packages ORDER BY base_price ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

  <main class="section" style="padding-top: 50px;">
    <div class="container">
      <!-- Section Header -->
      <div class="section-header">
        <span class="section-tag">Curated Service Packages</span>
        <h1 class="section-title">Event Photography & Photo Souvenir Offerings</h1>
        <p class="section-desc">
          Hover over any package card to trigger the <strong>"Book Now!"</strong> action, or use our interactive customizer to tailor hours, photographers, and print stations.
        </p>
      </div>

      <!-- Prominent "Customize a Package" Banner -->
      <div class="packages-top-banner">
        <div class="packages-top-text">
          <h3>Need a Tailor-Made Setup for Your Special Day?</h3>
          <p>Configure bespoke coverage hours, multiple roaming shooters, dual printing stations, and specialized acrylic frames.</p>
        </div>
        <a href="booking.php?package=custom" class="btn btn-accent btn-lg">Customize a Package Now &rarr;</a>
      </div>

      <!-- Packages Grid with Photo Sliders & Hover "Book Now!" Effect -->
      <div class="packages-grid">
        <?php foreach ($packages as $pkg): 
          $inclusions = json_decode($pkg['inclusions_json'], true) ?: [];
        ?>
          <div class="package-card <?= $pkg['is_popular'] ? 'featured' : '' ?>">
            <!-- Interactive Photo Slider on Card -->
            <div class="card-slider">
              <div class="slider-track">
                <div class="slider-slide"><img src="assets/images/mib-cover.jpg" alt="<?= htmlspecialchars($pkg['name']) ?>"></div>
                <div class="slider-slide"><img src="assets/images/mib-banner.png" alt="<?= htmlspecialchars($pkg['name']) ?>"></div>
              </div>
              <button class="slider-prev" type="button" aria-label="Previous">&lsaquo;</button>
              <button class="slider-next" type="button" aria-label="Next">&rsaquo;</button>
              <div class="slider-dots"></div>
            </div>

            <!-- "Book Now!" Hover Overlay (Requested Feature) -->
            <a href="booking.php?package=<?= urlencode($pkg['slug']) ?>" class="book-overlay">
              <div class="book-overlay-badge">
                <span class="icon">📷</span>
                <span>BOOK NOW!</span>
              </div>
            </a>

            <div class="package-card-body">
              <div class="package-tag"><?= htmlspecialchars($pkg['tag']) ?></div>
              <h3 class="package-name"><?= htmlspecialchars($pkg['name']) ?></h3>
              <div class="package-price">₱<?= number_format($pkg['base_price'], 2) ?></div>
              <div class="package-duration">⏱️ <?= $pkg['duration_hours'] ?> Hours &bull; 👥 <?= $pkg['photographers'] ?> Shooter, <?= $pkg['operators'] ?> Operator</div>
              <p class="package-desc"><?= htmlspecialchars($pkg['description']) ?></p>

              <ul class="package-features">
                <?php foreach ($inclusions as $inc): ?>
                  <li><?= htmlspecialchars($inc) ?></li>
                <?php endforeach; ?>
              </ul>

              <div class="package-actions" style="margin-top: 24px;">
                <a href="booking.php?package=<?= urlencode($pkg['slug']) ?>" class="btn <?= $pkg['is_popular'] ? 'btn-accent' : 'btn-outline' ?>" style="width: 100%; text-align: center;">
                  Select This Package
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Comparison Matrix Table -->
      <div class="panel-card" style="margin-top: 60px;">
        <div class="panel-card-header">
          <h2 class="panel-card-title">Comprehensive Package Comparison Matrix</h2>
          <span class="badge badge-completed">All-Inclusive Standards</span>
        </div>

        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Service Specification</th>
                <th>The Solo Agent</th>
                <th>The Executive Squad</th>
                <th>The Syndicate Suite</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Base Investment Rate</strong></td>
                <td>₱7,500.00</td>
                <td><strong>₱12,500.00</strong></td>
                <td>₱18,500.00</td>
              </tr>
              <tr>
                <td><strong>Coverage Duration</strong></td>
                <td>2 Hours Continuous</td>
                <td>3 Hours Continuous</td>
                <td>4 Hours Continuous</td>
              </tr>
              <tr>
                <td><strong>Suited Roaming Photographers</strong></td>
                <td>1 Dedicated Shooter</td>
                <td>2 Multi-Angle Shooters</td>
                <td>2 Senior Master Shooters</td>
              </tr>
              <tr>
                <td><strong>High-Speed Dye-Sub Operators</strong></td>
                <td>1 Station Tech</td>
                <td>1 Station Tech</td>
                <td>2 Synchronized Station Techs</td>
              </tr>
              <tr>
                <td><strong>Instant 4R Print Yield</strong></td>
                <td>Unlimited High-Yield</td>
                <td>Unlimited High-Yield</td>
                <td>Double Output Zero-Wait</td>
              </tr>
              <tr>
                <td><strong>Full Magnetic Backings</strong></td>
                <td>Included on All Prints</td>
                <td>Included on All Prints</td>
                <td>Included on All Prints</td>
              </tr>
              <tr>
                <td><strong>Custom Themed Layout Border</strong></td>
                <td>Standard Single Frame</td>
                <td>Bespoke Multi-Photo Border</td>
                <td>Executive Border + Strip Design</td>
              </tr>
              <tr>
                <td><strong>Reservation Down Payment</strong></td>
                <td><strong>₱2,000.00 Fixed</strong></td>
                <td><strong>₱2,000.00 Fixed</strong></td>
                <td><strong>₱2,000.00 Fixed</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- FAQ Section -->
      <div class="panel-card" style="margin-top: 32px;">
        <h3 style="font-size: 1.3rem; margin-bottom: 20px; font-weight: 700;">Frequently Asked Questions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
          <div>
            <h4 style="font-size: 1rem; color: var(--color-white); margin-bottom: 8px;">How does the ₱2,000 Down Payment work?</h4>
            <p style="font-size: 0.88rem; color: var(--color-gray-400);">
              In accordance with Chapter 1 Scope & Limitations, date reservation requires a fixed ₱2,000 deposit via GCash or Bank Transfer. The balance is settled upon conclusion of the event.
            </p>
          </div>
          <div>
            <h4 style="font-size: 1rem; color: var(--color-white); margin-bottom: 8px;">What does "Photoman" mean?</h4>
            <p style="font-size: 0.88rem; color: var(--color-gray-400);">
              Instead of guests waiting in line at a stationary booth, our suited photographers roam the tables, photograph moments, and hand instant magnetic prints directly to guests.
            </p>
          </div>
          <div>
            <h4 style="font-size: 1rem; color: var(--color-white); margin-bottom: 8px;">Can I extend hours or add shooters?</h4>
            <p style="font-size: 0.88rem; color: var(--color-gray-400);">
              Yes! Use our "Customize a Package" mode on the booking page to add hours (₱1,500/hr) or additional roaming photographers (₱2,000/shooter).
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
