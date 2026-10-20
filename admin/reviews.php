<?php
/**
 * MIB PHOTOMAN - Customer Service Review & Employee Evaluation
 * Solves Chapter 1 Specific Objective 5: Internal evaluation metrics and client feedback
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin', '../login.php');

$pageTitle = 'Customer Service Reviews | MIB Photoman Admin';
$basePath = '../';
$db = getDb();

// Metrics
$totalReviews = $db->query("SELECT COUNT(*) FROM customer_reviews")->fetchColumn();
$avgOverall = $db->query("SELECT ROUND(AVG(rating), 1) FROM customer_reviews")->fetchColumn() ?: '0.0';
$avgPhotographer = $db->query("SELECT ROUND(AVG(photographer_rating), 1) FROM customer_reviews")->fetchColumn() ?: '0.0';
$avgPrintQuality = $db->query("SELECT ROUND(AVG(print_quality_rating), 1) FROM customer_reviews")->fetchColumn() ?: '0.0';

// Reviews list
$reviews = $db->query("
    SELECT r.*, b.booking_ref, b.event_type, b.event_date, b.venue_name,
           u.full_name as client_name, u.email as client_email
    FROM customer_reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN users u ON r.client_id = u.id
    ORDER BY r.submitted_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Navigation Bar -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Quality Assurance & Performance</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Customer Service Reviews & Evaluation</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Client feedback gathered after event conclusion to evaluate employee performance and refine crew delegation.</p>
        </div>

        <div class="portal-nav-pills">
          <a href="index.php" class="portal-pill">📊 Overview</a>
          <a href="bookings.php" class="portal-pill">📋 Bookings & Payments</a>
          <a href="schedules.php" class="portal-pill">👥 Crew Schedules</a>
          <a href="journals.php" class="portal-pill">📝 Post-Event Journals</a>
          <a href="reviews.php" class="portal-pill active">⭐ Customer Reviews</a>
          <a href="notifications.php" class="portal-pill">✉️ Notification Logs</a>
        </div>
      </div>

      <!-- Performance Ratings Breakdown -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-label">Total Verified Reviews</div>
          <div class="stat-val"><?= $totalReviews ?></div>
          <div class="stat-sub">Submitted via client portal</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Overall Experience</div>
          <div class="stat-val" style="color: #fbbf24;"><?= $avgOverall ?> ★</div>
          <div class="stat-sub">5-Star Scale</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Photographer Professionalism</div>
          <div class="stat-val" style="color: #fbbf24;"><?= $avgPhotographer ?> ★</div>
          <div class="stat-sub">Roaming shooter rating</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Print & Magnet Quality</div>
          <div class="stat-val" style="color: #fbbf24;"><?= $avgPrintQuality ?> ★</div>
          <div class="stat-sub">Dye-sublimation fidelity</div>
        </div>
      </div>

      <!-- Reviews Feed -->
      <div class="panel-card">
        <div class="panel-card-header">
          <h2 class="panel-card-title">Recent Client Service Evaluations</h2>
          <span class="badge badge-completed">Internal Quality Record</span>
        </div>

        <?php if (empty($reviews)): ?>
          <p style="text-align: center; color: var(--color-gray-500); padding: 32px;">No customer reviews submitted yet.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($reviews as $rev): ?>
              <div style="background: rgba(9, 9, 11, 0.7); border: 1px solid var(--color-gray-200); border-radius: var(--radius-md); padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                  <div>
                    <strong style="font-size: 1.05rem; color: var(--color-white);"><?= htmlspecialchars($rev['client_name']) ?></strong>
                    <div style="font-size: 0.8rem; color: var(--color-gray-400);">
                      <?= htmlspecialchars($rev['event_type']) ?> &bull; <?= htmlspecialchars($rev['venue_name']) ?> (<?= htmlspecialchars($rev['booking_ref']) ?>)
                    </div>
                  </div>

                  <div style="text-align: right;">
                    <div class="star-rating" style="font-size: 1.1rem;">
                      <?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--color-gray-500); margin-top: 2px;">
                      <?= date('M d, Y', strtotime($rev['submitted_at'])) ?>
                    </div>
                  </div>
                </div>

                <div style="display: flex; gap: 20px; font-size: 0.82rem; color: var(--color-gray-400); margin-bottom: 12px; background: rgba(255, 255, 255, 0.03); padding: 8px 12px; border-radius: var(--radius-sm);">
                  <span>Shooter Attire & Demeanor: <strong style="color: var(--color-white);"><?= $rev['photographer_rating'] ?>/5 ★</strong></span>
                  <span>Print & Magnet Speed: <strong style="color: var(--color-white);"><?= $rev['print_quality_rating'] ?>/5 ★</strong></span>
                  <span>Recommendation: <strong style="color: #34d399;"><?= $rev['would_recommend'] ? '✓ Yes, Highly' : 'No' ?></strong></span>
                </div>

                <p style="font-style: italic; color: var(--color-gray-300); font-size: 0.92rem; line-height: 1.5;">
                  "<?= htmlspecialchars($rev['feedback_text']) ?>"
                </p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
