<?php
/**
 * MIB PHOTOMAN - Talent Staff Portal
 * Displays delegated assignments, call times, venue directives, and access to post-event journaling
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('staff', '../login.php');

$pageTitle = 'Talent Crew Portal | MIB Photoman';
$basePath = '../';
$db = getDb();
$user = currentUser();

// Fetch assignments for this staff member
$assignments = $db->prepare("
    SELECT sa.*, b.booking_ref, b.event_type, b.event_date, b.event_time, b.venue_name, b.venue_address,
           b.package_name, b.booking_status,
           (SELECT id FROM post_event_journals pj WHERE pj.booking_id = b.id AND pj.staff_id = sa.staff_id) as journal_id
    FROM staff_assignments sa
    JOIN bookings b ON sa.booking_id = b.id
    WHERE sa.staff_id = ?
    ORDER BY b.event_date ASC
");
$assignments->execute([$user['id']]);
$myEvents = $assignments->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Staff Header -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Talent Operations</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">MIB Crew Schedule & Deployment</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Welcome, <strong><?= htmlspecialchars($user['full_name']) ?></strong> (<?= htmlspecialchars($user['role_title']) ?>).</p>
        </div>

        <div style="display: flex; gap: 10px;">
          <a href="journal.php" class="btn btn-accent">+ Log Post-Event Journal</a>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-label">Assigned Events</div>
          <div class="stat-val"><?= count($myEvents) ?></div>
          <div class="stat-sub">Active deployments</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Next Call Time</div>
          <div class="stat-val" style="font-size: 1.3rem; margin-top: 6px;">
            <?php 
              $next = null;
              foreach ($myEvents as $ev) {
                if ($ev['booking_status'] !== 'completed') {
                  $next = $ev;
                  break;
                }
              }
              echo $next ? date('M d, g:i A', strtotime($next['event_date'] . ' ' . $next['event_time'])) : 'No pending calls';
            ?>
          </div>
          <div class="stat-sub"><?= $next ? htmlspecialchars($next['venue_name']) : 'Enjoy your rest' ?></div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Official Uniform</div>
          <div class="stat-val" style="font-size: 1.3rem; margin-top: 6px;">Suit & Tie</div>
          <div class="stat-sub">Crisp black suit required onsite</div>
        </div>
      </div>

      <!-- Assigned Schedules Cards -->
      <div class="panel-card">
        <div class="panel-card-header">
          <h2 class="panel-card-title">📅 My Assigned Event Schedules</h2>
          <span class="badge badge-completed">Verified by Management</span>
        </div>

        <?php if (empty($myEvents)): ?>
          <p style="text-align: center; color: var(--color-gray-500); padding: 32px;">No events currently assigned to your roster profile.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($myEvents as $ev): ?>
              <div style="background: rgba(9, 9, 11, 0.7); border: 1px solid var(--color-gray-200); border-radius: var(--radius-md); padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                  <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                      <strong style="font-size: 1.2rem; color: var(--color-white); letter-spacing: 0.05em; font-family: var(--font-logo);"><?= htmlspecialchars($ev['booking_ref']) ?></strong>
                      <span class="badge badge-confirmed"><?= htmlspecialchars($ev['role_in_event']) ?></span>
                      <?php if ($ev['booking_status'] === 'completed'): ?>
                        <span class="badge badge-completed">Completed</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Active Schedule</span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: var(--color-white);"><?= htmlspecialchars($ev['event_type']) ?></div>
                  </div>

                  <div style="text-align: right;">
                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--color-white);"><?= date('l, F j, Y', strtotime($ev['event_date'])) ?></div>
                    <div style="font-size: 0.9rem; color: #34d399; font-weight: 700;">Crew Call Time: <?= date('g:i A', strtotime($ev['event_time'])) ?></div>
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 0.88rem; margin-bottom: 16px;">
                  <div>
                    <span style="color: var(--color-gray-400);">Venue:</span> <strong><?= htmlspecialchars($ev['venue_name']) ?></strong><br>
                    <span style="color: var(--color-gray-500);"><?= htmlspecialchars($ev['venue_address']) ?></span>
                  </div>
                  <div>
                    <span style="color: var(--color-gray-400);">Package Deployed:</span> <strong><?= htmlspecialchars($ev['package_name']) ?></strong><br>
                    <span style="color: var(--color-gray-400);">Management Directives:</span> <em><?= htmlspecialchars($ev['notes'] ?: 'Standard MIB Protocol.') ?></em>
                  </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--color-gray-200); padding-top: 14px;">
                  <?php if ($ev['journal_id']): ?>
                    <span class="badge badge-completed" style="padding: 8px 14px;">✓ Post-Event Journal Logged</span>
                  <?php else: ?>
                    <a href="journal.php?booking_id=<?= $ev['booking_id'] ?>" class="btn btn-accent" style="font-size: 0.85rem; padding: 8px 16px;">
                      📝 Log Post-Event Journal &rarr;
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
