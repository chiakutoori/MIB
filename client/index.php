<?php
/**
 * MIB PHOTOMAN - Client / Event Host Portal
 * Displays reserved bookings, fixed ₱2,000 DP verification status, assigned crew, and review submission link
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('client', '../login.php');

$pageTitle = 'My Event Reservations | MIB Photoman Client Portal';
$basePath = '../';
$db = getDb();
$user = currentUser();

// Fetch bookings for this client
$bookingsStmt = $db->prepare("
    SELECT b.*,
           (SELECT GROUP_CONCAT(u.full_name || ' (' || sa.role_in_event || ')', ', ') 
            FROM staff_assignments sa 
            JOIN users u ON sa.staff_id = u.id 
            WHERE sa.booking_id = b.id) as assigned_crew,
           (SELECT id FROM customer_reviews cr WHERE cr.booking_id = b.id) as review_id
    FROM bookings b
    WHERE b.client_id = ? OR b.client_email = ?
    ORDER BY b.event_date DESC
");
$bookingsStmt->execute([$user['id'], $user['email']]);
$myBookings = $bookingsStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Header -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Client Portal</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">My Event Reservations</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Welcome, <strong><?= htmlspecialchars($user['full_name']) ?></strong>. Monitor reservation verification, assigned crew, and post-event reviews.</p>
        </div>

        <div style="display: flex; gap: 10px;">
          <a href="../booking.php" class="btn btn-accent">+ Book Another Event</a>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-label">Total Bookings</div>
          <div class="stat-val"><?= count($myBookings) ?></div>
          <div class="stat-sub">Registered events</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Fixed DP Status</div>
          <div class="stat-val" style="color: #34d399; font-size: 1.4rem; margin-top: 6px;">₱2,000 / Event</div>
          <div class="stat-sub">Standard reservation fee</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Need Assistance?</div>
          <div class="stat-val" style="font-size: 1.2rem; margin-top: 6px;">0917-888-MIB1</div>
          <div class="stat-sub">CSLB Events Planning Hotline</div>
        </div>
      </div>

      <!-- Bookings Cards -->
      <div class="panel-card">
        <div class="panel-card-header">
          <h2 class="panel-card-title">📅 My Scheduled Celebrations</h2>
          <span class="badge badge-completed">Master Schedule Sync</span>
        </div>

        <?php if (empty($myBookings)): ?>
          <div style="text-align: center; padding: 48px;">
            <p style="color: var(--color-gray-400); margin-bottom: 20px;">You haven't submitted any event bookings yet.</p>
            <a href="../booking.php" class="btn btn-accent btn-lg">Book Your First Event (₱2,000 DP) &rarr;</a>
          </div>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($myBookings as $b): 
              $balance = max(0, $b['total_price'] - $b['down_payment']);
            ?>
              <div style="background: rgba(9, 9, 11, 0.7); border: 1px solid var(--color-gray-200); border-radius: var(--radius-md); padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                  <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                      <strong style="font-size: 1.2rem; color: var(--color-white); letter-spacing: 0.05em; font-family: var(--font-logo);"><?= htmlspecialchars($b['booking_ref']) ?></strong>
                      <?php if ($b['booking_status'] === 'confirmed'): ?>
                        <span class="badge badge-confirmed">Booking Confirmed</span>
                      <?php elseif ($b['booking_status'] === 'completed'): ?>
                        <span class="badge badge-completed">Event Completed</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Under Verification</span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--color-white);"><?= htmlspecialchars($b['event_type']) ?></div>
                  </div>

                  <div style="text-align: right;">
                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--color-white);"><?= date('l, F j, Y', strtotime($b['event_date'])) ?></div>
                    <div style="font-size: 0.9rem; color: #34d399; font-weight: 700;">Crew Call Time: <?= date('g:i A', strtotime($b['event_time'])) ?></div>
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 0.88rem; margin-bottom: 20px;">
                  <div>
                    <span style="color: var(--color-gray-400);">Venue Location:</span><br>
                    <strong><?= htmlspecialchars($b['venue_name']) ?></strong><br>
                    <span style="color: var(--color-gray-500);"><?= htmlspecialchars($b['venue_address']) ?></span>
                  </div>

                  <div>
                    <span style="color: var(--color-gray-400);">Package Chosen:</span><br>
                    <strong><?= htmlspecialchars($b['package_name']) ?></strong> (<?= $b['custom_hours'] ?> Hours Coverage)<br>
                    <span style="color: var(--color-gray-500);"><?= $b['custom_photographers'] ?> Roaming Shooters &bull; <?= $b['custom_operators'] ?> Dye-Sub Station</span>
                  </div>
                </div>

                <!-- Financial & Crew Status Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: rgba(255, 255, 255, 0.03); padding: 16px; border-radius: var(--radius-sm); margin-bottom: 20px;">
                  <div>
                    <span style="color: var(--color-gray-400); display: block; margin-bottom: 4px;">Assigned MIB Suited Crew:</span>
                    <?php if (!empty($b['assigned_crew'])): ?>
                      <strong style="color: #34d399;"><?= htmlspecialchars($b['assigned_crew']) ?></strong>
                    <?php else: ?>
                      <span style="color: var(--color-gray-500); font-style: italic;">Crew delegation in progress by administrator</span>
                    <?php endif; ?>
                  </div>

                  <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                      <span style="color: var(--color-gray-400);">Total Package Investment:</span>
                      <strong>₱<?= number_format($b['total_price'], 2) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                      <span style="color: var(--color-gray-400);">Fixed Down Payment:</span>
                      <span style="color: #34d399; font-weight: 700;">₱2,000.00 (<?= htmlspecialchars($b['payment_status']) ?>)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--color-gray-200); padding-top: 4px;">
                      <span style="color: var(--color-gray-400);">Remaining Balance at Venue:</span>
                      <strong>₱<?= number_format($balance, 2) ?></strong>
                    </div>
                  </div>
                </div>

                <!-- Actions Footer -->
                <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--color-gray-200); padding-top: 16px;">
                  <?php if ($b['review_id']): ?>
                    <span class="badge badge-completed" style="padding: 8px 14px;">✓ Service Review Submitted</span>
                  <?php elseif ($b['booking_status'] === 'completed'): ?>
                    <a href="review.php?booking_id=<?= $b['id'] ?>" class="btn btn-accent" style="font-size: 0.85rem; padding: 8px 18px;">
                      ⭐ Submit 5-Star Service Review &rarr;
                    </a>
                  <?php else: ?>
                    <span style="font-size: 0.8rem; color: var(--color-gray-500); align-self: center;">
                      Review unlocks after event completion.
                    </span>
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
