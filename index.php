<?php
/**
 * MIB PHOTOMAN - Home Page (PHP + Monochromatic Inverted Theme)
 * Incorporates cover photo background, video hero, thesis pillars, packages, and live reviews
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'MIB Photoman PH | Onsite Event Photography & Instant Souvenirs';
$basePath = '';

$db = getDb();
// Fetch latest customer reviews from database
$reviews = $db->query("
    SELECT r.*, b.event_type, b.venue_name, u.full_name as client_name
    FROM customer_reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN users u ON r.client_id = u.id
    ORDER BY r.submitted_at DESC LIMIT 3
")->fetchAll();

// Fetch packages
$packages = $db->query("SELECT * FROM packages ORDER BY base_price ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

  <!-- Hero Section with Background Video & Viewfinder Reticles -->
  <section class="hero-section hero-has-video" id="top">
    <div class="hero-video-wrap" aria-hidden="true">
      <video class="hero-video" autoplay muted loop playsinline preload="auto" poster="assets/videos/hero-poster.jpg">
        <source src="assets/videos/hero-photography.mp4" type="video/mp4">
      </video>
      <div class="hero-video-overlay"></div>
      <div class="hero-grain"></div>
      <span class="vf-corner vf-tl"></span><span class="vf-corner vf-tr"></span>
      <span class="vf-corner vf-bl"></span><span class="vf-corner vf-br"></span>
    </div>

    <button type="button" class="hero-video-toggle" id="hero-video-toggle" aria-label="Pause background video">
      <span class="toggle-icon">❚❚</span>
      <span class="toggle-label">Pause</span>
    </button>

    <div class="container hero-content">
      <div class="hero-tag">
        <span class="hero-tag-badge">CSLB Events Planning</span>
        <span>A Web-Based Package Customization & Scheduling System</span>
      </div>

      <h1 class="hero-title">
        ELEVATE YOUR EVENT WITH <span style="text-decoration: underline; text-decoration-color: var(--color-gray-400);">SUITED</span> ROAMING PHOTOGRAPHY
      </h1>

      <p class="hero-description">
        Say goodbye to scattered Messenger chats and lost Google forms. Book professional suited photographers, customize coverage parameters in real time, and receive instant 4R magnetic prints on site.
      </p>

      <div class="hero-buttons">
        <a href="packages.php" class="btn btn-accent btn-lg">Explore Packages</a>
        <a href="booking.php" class="btn btn-outline btn-lg">Book Event (₱2,000 DP)</a>
      </div>

      <!-- Live Operational Metrics (25% Thesis Scope) -->
      <div class="hero-metrics">
        <div class="metric-item">
          <div class="metric-num">100%</div>
          <div class="metric-label">Online Booking Workflow</div>
        </div>
        <div class="metric-item">
          <div class="metric-num">0%</div>
          <div class="metric-label">Data Duplication Loss</div>
        </div>
        <div class="metric-item">
          <div class="metric-num">24h</div>
          <div class="metric-label">Automated Schedule Alerts</div>
        </div>
        <div class="metric-item">
          <div class="metric-num">₱2,000</div>
          <div class="metric-label">Fixed Reservation DP</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Section: Four Operational Pillars of the System -->
  <section class="section">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">System Architecture & Scope</span>
        <h2 class="section-title">Engineered to Eliminate Messenger-Based Bottlenecks</h2>
        <p class="section-desc">
          Replacing unorganized group chats with an automated, centralized web management ecosystem under CSLB Events Planning.
        </p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">⚡</div>
          <h3 class="feature-title">Package Customization & Calculator</h3>
          <p class="feature-desc">
            Clients dynamically select coverage hours (2-8 hrs), number of roving photographers, and high-speed printing stations with instant live computation.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🗄️</div>
          <h3 class="feature-title">Centralized SQLite / PDO Database</h3>
          <p class="feature-desc">
            Consolidates client records, booking dates, down payment receipts, and staff allocations in one unified relational schema—preventing data loss.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📅</div>
          <h3 class="feature-title">Crew Scheduling & Conflict Engine</h3>
          <p class="feature-desc">
            Administrators assign photographers and dye-sub operators with real-time schedule conflict validation to prevent double-booked talent.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📝</div>
          <h3 class="feature-title">Post-Event Journaling & Reviews</h3>
          <p class="feature-desc">
            Internal post-event logs document total prints distributed and equipment health, paired with 5-star customer feedback for staff performance evaluation.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Section: Curated Packages with Slider & Hover "Book Now!" Effect -->
  <section class="section" style="background: rgba(9, 9, 11, 0.65);">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">Curated Offerings</span>
        <h2 class="section-title">Signature MIB Photoman Packages</h2>
        <p class="section-desc">
          Every package includes sharply suited personnel, high-gloss 4R dye-sublimation prints, custom themed layout borders, and magnetic backings.
        </p>
      </div>

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
                  <li>✓ <?= htmlspecialchars($inc) ?></li>
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

      <!-- Customize a Package Banner -->
      <div class="packages-top-banner" style="margin-top: 48px;">
        <div class="packages-top-text">
          <h3>Need a Tailor-Made Configuration for Your Celebration?</h3>
          <p>Configure custom coverage duration, multiple roving shooters, dual high-speed printing booths, and acrylic desk standees.</p>
        </div>
        <a href="booking.php?package=custom" class="btn btn-accent btn-lg">Launch Package Customizer &rarr;</a>
      </div>
    </div>
  </section>

  <!-- Section: Live Customer Reviews from Database -->
  <?php if (!empty($reviews)): ?>
  <section class="section">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">Quality Monitoring</span>
        <h2 class="section-title">Verified Customer Service Feedback</h2>
        <p class="section-desc">
          Actual feedback logged into our central evaluation database following completed event deployments.
        </p>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
        <?php foreach ($reviews as $rev): ?>
          <div class="panel-card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
              <div class="star-rating">
                <?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?>
              </div>
              <span class="badge badge-completed">Verified Host</span>
            </div>
            <p style="font-style: italic; color: var(--color-gray-600); margin-bottom: 16px; font-size: 0.95rem;">
              "<?= htmlspecialchars($rev['feedback_text']) ?>"
            </p>
            <div style="border-top: 1px solid var(--color-gray-200); padding-top: 12px; font-size: 0.85rem;">
              <div style="font-weight: 700; color: var(--color-white);"><?= htmlspecialchars($rev['client_name']) ?></div>
              <div style="color: var(--color-gray-400);"><?= htmlspecialchars($rev['event_type']) ?> &bull; <?= htmlspecialchars($rev['venue_name']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Ready to Book CTA -->
  <section class="section" style="padding-bottom: 90px;">
    <div class="container">
      <div class="panel-card" style="text-align: center; padding: 48px 24px; background: linear-gradient(135deg, rgba(22, 22, 26, 0.95), rgba(39, 39, 42, 0.9));">
        <h2 style="font-size: 2rem; font-weight: 800; font-family: var(--font-logo); margin-bottom: 16px;">
          SECURE YOUR DATE ON THE MIB MASTER SCHEDULE
        </h2>
        <p style="max-width: 650px; margin: 0 auto 28px; color: var(--color-gray-400); font-size: 1.05rem;">
          Lock in your celebration with a standardized <strong>₱2,000.00 down payment</strong>. Automated email receipt and schedule booking reference dispatched instantly.
        </p>
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
          <a href="booking.php" class="btn btn-accent btn-lg">Open Event Booking Form</a>
          <a href="packages.php" class="btn btn-outline btn-lg">Compare All Packages</a>
        </div>
      </div>
    </div>
  </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
