<?php
/**
 * MIB PHOTOMAN - Administrator Command Center
 * 25% Scope Foundation: Manages bookings, approves down payments, monitors staff delegation & notification logs
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin', '../login.php');

$pageTitle = 'Administrator Command Center | MIB Photoman';
$basePath = '../';
$db = getDb();

// Handle Quick 1-Click DP Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_dp') {
    $bookingId = intval($_POST['booking_id']);
    $stmt = $db->prepare("UPDATE bookings SET payment_status = 'dp_confirmed', booking_status = 'confirmed' WHERE id = ?");
    $stmt->execute([$bookingId]);

    // Fetch booking details to dispatch notification
    $b = $db->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch();
    if ($b) {
        $msg = "Dear {$b['client_name']},\n\nYour fixed down payment of ₱2,000.00 for Booking Ref {$b['booking_ref']} ({$b['event_type']} on {$b['event_date']}) has been officially verified by MIB Photoman Management.\n\nYour date is now officially locked in our master calendar.";
        $db->prepare("INSERT INTO notifications (recipient_email, recipient_role, title, message, notification_type) VALUES (?, 'client', ?, ?, 'dp_verified')")
           ->execute([$b['client_email'], "Down Payment Verified - {$b['booking_ref']}", $msg]);
    }

    setFlash('success', "Booking {$b['booking_ref']} confirmed! Down payment verified and client notified via automated email.");
    header("Location: index.php");
    exit;
}

// Operational KPIs
$totalBookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE payment_status = 'dp_submitted' OR booking_status = 'pending'")->fetchColumn();
$confirmedBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();
$staffCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
$avgRating = $db->query("SELECT ROUND(AVG(rating), 1) FROM customer_reviews")->fetchColumn() ?: '5.0';

// Check for staff scheduling conflicts (same staff assigned to multiple events on the same date)
$conflicts = $db->query("
    SELECT sa.staff_id, u.full_name as staff_name, b.event_date, COUNT(*) as event_count
    FROM staff_assignments sa
    JOIN bookings b ON sa.booking_id = b.id
    JOIN users u ON sa.staff_id = u.id
    WHERE b.booking_status IN ('pending', 'confirmed')
    GROUP BY sa.staff_id, b.event_date
    HAVING COUNT(*) > 1
")->fetchAll();

// Recent Bookings
$recentBookings = $db->query("
    SELECT b.*, p.name as pkg_title
    FROM bookings b
    LEFT JOIN packages p ON b.package_id = p.id
    ORDER BY b.event_date ASC
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Admin Portal Navigation Pills -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Executive Management</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Admin Command Center</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Master operational console for bookings, staff scheduling, journals, and notification audit logs.</p>
        </div>

        <div class="portal-nav-pills">
          <a href="index.php" class="portal-pill active">📊 Overview</a>
          <a href="bookings.php" class="portal-pill">📋 Bookings & Payments</a>
          <a href="schedules.php" class="portal-pill">👥 Crew Schedules</a>
          <a href="journals.php" class="portal-pill">📝 Post-Event Journals</a>
          <a href="reviews.php" class="portal-pill">⭐ Customer Reviews</a>
          <a href="notifications.php" class="portal-pill">✉️ Notification Logs</a>
        </div>
      </div>

      <!-- Scheduling Conflict Alert (if any) -->
      <?php if (!empty($conflicts)): ?>
        <?php foreach ($conflicts as $c): ?>
          <div class="conflict-banner">
            <span style="font-size: 1.4rem;">⚠️</span>
            <div>
              <strong>SCHEDULE CONFLICT DETECTED:</strong> Staff member <strong><?= htmlspecialchars($c['staff_name']) ?></strong> is assigned to <?= $c['event_count'] ?> overlapping events on <strong><?= htmlspecialchars($c['event_date']) ?></strong>. 
              <a href="schedules.php" style="text-decoration: underline; font-weight: 700; color: #ffffff; margin-left: 8px;">Resolve in Crew Schedules &rarr;</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Operational KPIs -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-label">Total Reservations</div>
          <div class="stat-val"><?= $totalBookings ?></div>
          <div class="stat-sub">Logged in unified database</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Pending ₱2,000 DP</div>
          <div class="stat-val" style="color: #fbbf24;"><?= $pendingBookings ?></div>
          <div class="stat-sub">Awaiting verification</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Confirmed Events</div>
          <div class="stat-val" style="color: #34d399;"><?= $confirmedBookings ?></div>
          <div class="stat-sub">Master calendar slots locked</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Active Crew Roster</div>
          <div class="stat-val"><?= $staffCount ?></div>
          <div class="stat-sub">Suited shooters & operators</div>
        </div>

        <div class="stat-box">
          <div class="stat-label">Client Satisfaction</div>
          <div class="stat-val" style="color: #fbbf24;"><?= $avgRating ?> ★</div>
          <div class="stat-sub">Average service review</div>
        </div>
      </div>

      <!-- Quick Actions Grid -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 32px;">
        <a href="bookings.php" class="panel-card" style="margin-bottom: 0; padding: 20px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
          <div style="font-size: 1.5rem; margin-bottom: 8px;">💳</div>
          <strong style="color: var(--color-white); font-size: 1.05rem;">Verify Down Payments</strong>
          <p style="font-size: 0.8rem; color: var(--color-gray-400); margin-top: 4px;">Confirm submitted ₱2,000 deposits and trigger automated confirmation receipts.</p>
        </a>

        <a href="schedules.php" class="panel-card" style="margin-bottom: 0; padding: 20px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
          <div style="font-size: 1.5rem; margin-bottom: 8px;">👥</div>
          <strong style="color: var(--color-white); font-size: 1.05rem;">Delegate Staff Crew</strong>
          <p style="font-size: 0.8rem; color: var(--color-gray-400); margin-top: 4px;">Assign lead photographers and printer techs with conflict check.</p>
        </a>

        <a href="journals.php" class="panel-card" style="margin-bottom: 0; padding: 20px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
          <div style="font-size: 1.5rem; margin-bottom: 8px;">📖</div>
          <strong style="color: var(--color-white); font-size: 1.05rem;">Audit Post-Event Notes</strong>
          <p style="font-size: 0.8rem; color: var(--color-gray-400); margin-top: 4px;">Review internal prints distributed, guest attendance, and gear health.</p>
        </a>

        <a href="notifications.php" class="panel-card" style="margin-bottom: 0; padding: 20px; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
          <div style="font-size: 1.5rem; margin-bottom: 8px;">✉️</div>
          <strong style="color: var(--color-white); font-size: 1.05rem;">Notification Audit Logs</strong>
          <p style="font-size: 0.8rem; color: var(--color-gray-400); margin-top: 4px;">View live records of dispatches sent to clients and staff members.</p>
        </a>
      </div>

      <!-- Master Event Schedule & Action Queue -->
      <div class="panel-card">
        <div class="panel-card-header">
          <h2 class="panel-card-title">📅 Master Event Queue & Payment Verification</h2>
          <a href="bookings.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 14px;">View Full Database &rarr;</a>
        </div>

        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>Client / Event</th>
                <th>Date & Venue</th>
                <th>Package Specs</th>
                <th>Total / DP Status</th>
                <th>Status</th>
                <th>Quick Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentBookings as $b): ?>
                <tr>
                  <td>
                    <strong style="color: var(--color-white); letter-spacing: 0.05em;"><?= htmlspecialchars($b['booking_ref']) ?></strong>
                    <div style="font-size: 0.75rem; color: var(--color-gray-500);"><?= date('M d, Y', strtotime($b['created_at'])) ?></div>
                  </td>
                  <td>
                    <div style="font-weight: 700; color: var(--color-white);"><?= htmlspecialchars($b['client_name']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--color-gray-400);"><?= htmlspecialchars($b['event_type']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--color-gray-500);"><?= htmlspecialchars($b['client_phone']) ?></div>
                  </td>
                  <td>
                    <div><strong><?= date('M d, Y', strtotime($b['event_date'])) ?></strong> (<?= date('g:i A', strtotime($b['event_time'])) ?>)</div>
                    <div style="font-size: 0.8rem; color: var(--color-gray-400);"><?= htmlspecialchars($b['venue_name']) ?></div>
                  </td>
                  <td>
                    <div><?= htmlspecialchars($b['package_name']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--color-gray-500);"><?= $b['custom_hours'] ?> hrs &bull; <?= $b['custom_photographers'] ?> shooters, <?= $b['custom_operators'] ?> tech</div>
                  </td>
                  <td>
                    <div style="font-weight: 700;">₱<?= number_format($b['total_price'], 2) ?></div>
                    <?php if ($b['payment_status'] === 'dp_confirmed' || $b['payment_status'] === 'fully_paid'): ?>
                      <span class="badge badge-confirmed">₱2,000 Verified</span>
                    <?php else: ?>
                      <span class="badge badge-pending">DP Submitted (<?= htmlspecialchars($b['payment_reference']) ?>)</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($b['booking_status'] === 'confirmed'): ?>
                      <span class="badge badge-confirmed">Confirmed</span>
                    <?php elseif ($b['booking_status'] === 'completed'): ?>
                      <span class="badge badge-completed">Completed</span>
                    <?php else: ?>
                      <span class="badge badge-pending">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($b['payment_status'] === 'dp_submitted'): ?>
                      <form method="POST" action="index.php" style="display: inline;">
                        <input type="hidden" name="action" value="verify_dp">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn btn-accent" style="font-size: 0.75rem; padding: 6px 12px;">
                          ✓ Verify ₱2k DP
                        </button>
                      </form>
                    <?php else: ?>
                      <a href="schedules.php?booking_id=<?= $b['id'] ?>" class="btn btn-outline" style="font-size: 0.75rem; padding: 6px 12px;">
                        Manage Crew
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
