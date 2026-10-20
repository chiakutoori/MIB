<?php
/**
 * MIB PHOTOMAN - Post-Event Journal Documentation Archive
 * Solves Chapter 1 Specific Objective 4: Internal records of event outcomes,
 * print yields, guest counts, and equipment status.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin', '../login.php');

$pageTitle = 'Post-Event Journal Archive | MIB Photoman Admin';
$basePath = '../';
$db = getDb();

// Fetch all journal logs
$journals = $db->query("
    SELECT j.*, b.booking_ref, b.event_type, b.event_date, b.venue_name,
           u.full_name as staff_name, u.role_title
    FROM post_event_journals j
    JOIN bookings b ON j.booking_id = b.id
    JOIN users u ON j.staff_id = u.id
    ORDER BY j.logged_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Navigation Bar -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Quality Assurance & Operations</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Post-Event Journal Documentation</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Internal records of event outcomes, print yields, and equipment condition—replacing lost Messenger chat updates.</p>
        </div>

        <div class="portal-nav-pills">
          <a href="index.php" class="portal-pill">📊 Overview</a>
          <a href="bookings.php" class="portal-pill">📋 Bookings & Payments</a>
          <a href="schedules.php" class="portal-pill">👥 Crew Schedules</a>
          <a href="journals.php" class="portal-pill active">📝 Post-Event Journals</a>
          <a href="reviews.php" class="portal-pill">⭐ Customer Reviews</a>
          <a href="notifications.php" class="portal-pill">✉️ Notification Logs</a>
        </div>
      </div>

      <!-- Journals List -->
      <?php if (empty($journals)): ?>
        <div class="panel-card" style="text-align: center; padding: 48px;">
          <div style="font-size: 2.5rem; margin-bottom: 12px;">📖</div>
          <h3 style="color: var(--color-white); margin-bottom: 8px;">No Journal Entries Yet</h3>
          <p style="color: var(--color-gray-400); max-width: 500px; margin: 0 auto;">
            When suited crew finish completed events, they log prints distributed, equipment health, and observations through their Staff Portal.
          </p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
          <?php foreach ($journals as $j): ?>
            <div class="panel-card">
              <div class="panel-card-header" style="border-bottom: 1px solid var(--color-gray-200); padding-bottom: 16px; margin-bottom: 20px;">
                <div>
                  <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <strong style="font-size: 1.2rem; color: var(--color-white); letter-spacing: 0.05em; font-family: var(--font-logo);"><?= htmlspecialchars($j['booking_ref']) ?></strong>
                    <span class="badge badge-completed">Internal Journal #<?= $j['id'] ?></span>
                  </div>
                  <div style="color: var(--color-gray-400); font-size: 0.88rem;">
                    <?= htmlspecialchars($j['event_type']) ?> &bull; <?= htmlspecialchars($j['venue_name']) ?> &bull; Event Date: <?= date('M d, Y', strtotime($j['event_date'])) ?>
                  </div>
                </div>

                <div style="text-align: right;">
                  <div style="font-size: 0.85rem; color: var(--color-white); font-weight: 700;">Logged by: <?= htmlspecialchars($j['staff_name']) ?></div>
                  <div style="font-size: 0.75rem; color: var(--color-gray-500);"><?= date('M d, Y g:i A', strtotime($j['logged_at'])) ?></div>
                </div>
              </div>

              <!-- Metrics Row -->
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div style="background: rgba(9, 9, 11, 0.7); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--color-gray-200);">
                  <div style="font-size: 0.75rem; color: var(--color-gray-400); text-transform: uppercase;">Prints Distributed</div>
                  <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-white); font-family: var(--font-logo);"><?= $j['prints_distributed'] ?> 4R Prints</div>
                </div>

                <div style="background: rgba(9, 9, 11, 0.7); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--color-gray-200);">
                  <div style="font-size: 0.75rem; color: var(--color-gray-400); text-transform: uppercase;">Estimated Guests</div>
                  <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-white); font-family: var(--font-logo);"><?= $j['guest_count_estimated'] ?> Pax</div>
                </div>

                <div style="background: rgba(9, 9, 11, 0.7); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--color-gray-200);">
                  <div style="font-size: 0.75rem; color: var(--color-gray-400); text-transform: uppercase;">Equipment Condition</div>
                  <div style="font-size: 0.95rem; font-weight: 700; color: #34d399; margin-top: 6px;"><?= htmlspecialchars($j['equipment_status']) ?></div>
                </div>
              </div>

              <!-- Notes & Observations -->
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 0.9rem;">
                <div style="background: rgba(255, 255, 255, 0.02); padding: 16px; border-radius: var(--radius-sm); border-left: 3px solid var(--color-white);">
                  <strong style="color: var(--color-white); display: block; margin-bottom: 6px;">Highlights & Observations:</strong>
                  <p style="color: var(--color-gray-300); line-height: 1.5; font-size: 0.88rem;"><?= nl2br(htmlspecialchars($j['highlights_and_notes'])) ?></p>
                </div>

                <div style="background: rgba(255, 255, 255, 0.02); padding: 16px; border-radius: var(--radius-sm); border-left: 3px solid #fbbf24;">
                  <strong style="color: var(--color-white); display: block; margin-bottom: 6px;">Issues Encountered & Resolutions:</strong>
                  <p style="color: var(--color-gray-300); line-height: 1.5; font-size: 0.88rem;"><?= nl2br(htmlspecialchars($j['issues_encountered'] ?: 'None reported. Event flowed smoothly.')) ?></p>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
