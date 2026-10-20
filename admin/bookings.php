<?php
/**
 * MIB PHOTOMAN - Master Bookings & Payments Management
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin', '../login.php');

$pageTitle = 'Bookings & Payments Directory | MIB Photoman Admin';
$basePath = '../';
$db = getDb();

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = intval($_POST['booking_id']);
    $action = $_POST['action'];

    if ($action === 'verify_dp') {
        $stmt = $db->prepare("UPDATE bookings SET payment_status = 'dp_confirmed', booking_status = 'confirmed' WHERE id = ?");
        $stmt->execute([$bookingId]);

        $b = $db->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch();
        if ($b) {
            $msg = "Dear {$b['client_name']},\n\nYour fixed down payment of ₱2,000.00 for Booking Ref {$b['booking_ref']} has been verified. Your event date on {$b['event_date']} is locked on the official MIB calendar.";
            $db->prepare("INSERT INTO notifications (recipient_email, recipient_role, title, message, notification_type) VALUES (?, 'client', ?, ?, 'dp_verified')")
               ->execute([$b['client_email'], "Down Payment Verified - {$b['booking_ref']}", $msg]);
        }
        setFlash('success', "Booking verified and automated confirmation sent.");
    } elseif ($action === 'mark_completed') {
        $stmt = $db->prepare("UPDATE bookings SET booking_status = 'completed', payment_status = 'fully_paid' WHERE id = ?");
        $stmt->execute([$bookingId]);

        $b = $db->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch();
        if ($b) {
            // Send client completion email notification!
            $completionMsg = "Dear {$b['client_name']},\n\nThank you for choosing MIB Photoman for your {$b['event_type']}! We hope your guests enjoyed their instant souvenir prints.\n\nYour event has been marked as completed and your remaining balance is now due at settlement.";
            $db->prepare("INSERT INTO notifications (recipient_email, recipient_role, title, message, notification_type) VALUES (?, 'client', ?, ?, 'event_completed')")
               ->execute([$b['client_email'], "Event Completed - {$b['booking_ref']}", $completionMsg]);
        }
        setFlash('success', "Event marked as completed! Automated completion notice dispatched to client.");
    }
    header("Location: bookings.php");
    exit;
}

// Filter query
$statusFilter = $_GET['status'] ?? 'all';
$query = "
    SELECT b.*, 
           (SELECT GROUP_CONCAT(u.full_name || ' (' || sa.role_in_event || ')', ', ') 
            FROM staff_assignments sa 
            JOIN users u ON sa.staff_id = u.id 
            WHERE sa.booking_id = b.id) as assigned_crew
    FROM bookings b
";

if ($statusFilter !== 'all') {
    $query .= " WHERE b.booking_status = " . $db->quote($statusFilter);
}
$query .= " ORDER BY b.event_date DESC";
$bookings = $db->query($query)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Navigation Bar -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Centralized Database</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Master Bookings Directory</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Consolidated record of all client reservations, fixed ₱2,000 deposits, and operational parameters.</p>
        </div>

        <div class="portal-nav-pills">
          <a href="index.php" class="portal-pill">📊 Overview</a>
          <a href="bookings.php" class="portal-pill active">📋 Bookings & Payments</a>
          <a href="schedules.php" class="portal-pill">👥 Crew Schedules</a>
        </div>
      </div>

      <!-- Filter Controls -->
      <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="bookings.php?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">All Bookings</a>
        <a href="bookings.php?status=pending" class="btn <?= $statusFilter === 'pending' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">Pending Review</a>
        <a href="bookings.php?status=confirmed" class="btn <?= $statusFilter === 'confirmed' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">Confirmed Dates</a>
        <a href="bookings.php?status=completed" class="btn <?= $statusFilter === 'completed' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">Completed Events</a>
      </div>

      <!-- Master Bookings Table -->
      <div class="panel-card">
        <div class="panel-card-header">
          <h2 class="panel-card-title">Reservation Records (<?= count($bookings) ?> total)</h2>
          <a href="../booking.php" class="btn btn-accent" style="font-size: 0.8rem; padding: 8px 16px;">+ Create Booking</a>
        </div>

        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>Client Details</th>
                <th>Date / Time / Venue</th>
                <th>Package Specifications</th>
                <th>Pricing & ₱2,000 DP</th>
                <th>Assigned Crew</th>
                <th>Status & Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($bookings)): ?>
                <tr>
                  <td colspan="7" style="text-align: center; padding: 32px; color: var(--color-gray-500);">No bookings found for the selected filter.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                  <tr>
                    <td>
                      <strong style="color: var(--color-white); letter-spacing: 0.05em;"><?= htmlspecialchars($b['booking_ref']) ?></strong>
                      <div style="font-size: 0.75rem; color: var(--color-gray-500);"><?= date('M d, Y', strtotime($b['created_at'])) ?></div>
                    </td>
                    <td>
                      <strong style="color: var(--color-white);"><?= htmlspecialchars($b['client_name']) ?></strong>
                      <div style="font-size: 0.8rem; color: var(--color-gray-400);"><?= htmlspecialchars($b['client_email']) ?></div>
                      <div style="font-size: 0.75rem; color: var(--color-gray-500);"><?= htmlspecialchars($b['client_phone']) ?></div>
                    </td>
                    <td>
                      <div><strong><?= date('M d, Y', strtotime($b['event_date'])) ?></strong> (<?= date('g:i A', strtotime($b['event_time'])) ?>)</div>
                      <div style="font-size: 0.8rem; color: var(--color-gray-400);"><?= htmlspecialchars($b['venue_name']) ?></div>
                      <div style="font-size: 0.75rem; color: var(--color-gray-500);"><?= htmlspecialchars($b['event_type']) ?></div>
                    </td>
                    <td>
                      <div><strong><?= htmlspecialchars($b['package_name']) ?></strong></div>
                      <div style="font-size: 0.8rem; color: var(--color-gray-400);"><?= $b['custom_hours'] ?> Hours &bull; <?= $b['custom_photographers'] ?> Shooters &bull; <?= $b['custom_operators'] ?> Station</div>
                      <?php if (!empty($b['special_requests'])): ?>
                        <div style="font-size: 0.75rem; color: #a1a1aa; font-style: italic; margin-top: 4px;">"<?= htmlspecialchars($b['special_requests']) ?>"</div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="font-weight: 700;">₱<?= number_format($b['total_price'], 2) ?></div>
                      <?php if ($b['payment_status'] === 'dp_confirmed'): ?>
                        <span class="badge badge-confirmed">₱2,000 Verified</span>
                      <?php elseif ($b['payment_status'] === 'fully_paid'): ?>
                        <span class="badge badge-completed">Fully Settled</span>
                      <?php else: ?>
                        <span class="badge badge-pending">DP Ref: <?= htmlspecialchars($b['payment_reference']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($b['assigned_crew'])): ?>
                        <div style="font-size: 0.82rem; color: var(--color-gray-300);"><?= htmlspecialchars($b['assigned_crew']) ?></div>
                        <a href="schedules.php?booking_id=<?= $b['id'] ?>" style="font-size: 0.75rem; color: var(--color-gray-500); text-decoration: underline;">Edit Crew</a>
                      <?php else: ?>
                        <span style="font-size: 0.8rem; color: #f87171;">None Assigned</span><br>
                        <a href="schedules.php?booking_id=<?= $b['id'] ?>" class="btn btn-outline" style="font-size: 0.7rem; padding: 2px 8px; margin-top: 4px;">+ Assign</a>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="display: flex; flex-direction: column; gap: 6px;">
                        <?php if ($b['payment_status'] === 'dp_submitted'): ?>
                          <form method="POST" action="bookings.php">
                            <input type="hidden" name="action" value="verify_dp">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-accent" style="font-size: 0.75rem; padding: 4px 10px; width: 100%;">
                              ✓ Verify ₱2k DP
                            </button>
                          </form>
                        <?php elseif ($b['booking_status'] === 'confirmed'): ?>
                          <form method="POST" action="bookings.php">
                            <input type="hidden" name="action" value="mark_completed">
                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-outline" style="font-size: 0.75rem; padding: 4px 10px; width: 100%;">
                              ✓ Mark Completed
                            </button>
                          </form>
                        <?php else: ?>
                          <span class="badge badge-completed">Archived</span>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
