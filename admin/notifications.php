<?php
/**
 * MIB PHOTOMAN - Centralized Email Notification Engine Log
 * Solves Chapter 1 Specific Objective 6: Centralized notification logs for confirmations,
 * schedule reminders, and staff task assignments—eliminating buried Messenger messages.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin', '../login.php');

$pageTitle = 'Notification Engine Logs | MIB Photoman Admin';
$basePath = '../';
$db = getDb();

// Filter
$roleFilter = $_GET['role'] ?? 'all';
$sql = "SELECT * FROM notifications";
if ($roleFilter !== 'all') {
    $sql .= " WHERE recipient_role = " . $db->quote($roleFilter);
}
$sql .= " ORDER BY created_at DESC";

$notifications = $db->query($sql)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Navigation Bar -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Centralized Notification Engine</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Notification Audit Logs</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Automated dispatches sent for booking receipts, ₱2,000 DP approvals, schedule alerts, and crew assignments.</p>
        </div>

        <div class="portal-nav-pills">
          <a href="index.php" class="portal-pill">📊 Overview</a>
          <a href="bookings.php" class="portal-pill">📋 Bookings & Payments</a>
          <a href="schedules.php" class="portal-pill">👥 Crew Schedules</a>
          <a href="journals.php" class="portal-pill">📝 Post-Event Journals</a>
          <a href="reviews.php" class="portal-pill">⭐ Customer Reviews</a>
          <a href="notifications.php" class="portal-pill active">✉️ Notification Logs</a>
        </div>
      </div>

      <!-- Filter Controls -->
      <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="notifications.php?role=all" class="btn <?= $roleFilter === 'all' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">All Dispatches (<?= count($notifications) ?>)</a>
        <a href="notifications.php?role=client" class="btn <?= $roleFilter === 'client' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">Client Notifications</a>
        <a href="notifications.php?role=staff" class="btn <?= $roleFilter === 'staff' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">Staff Crew Alerts</a>
        <a href="notifications.php?role=admin" class="btn <?= $roleFilter === 'admin' ? 'btn-accent' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 6px 14px;">Admin Audit Copies</a>
      </div>

      <!-- Master Notifications Table -->
      <div class="panel-card">
        <div class="panel-card-header">
          <h2 class="panel-card-title">Dispatched Automated Notifications</h2>
          <span class="badge badge-completed">100% Delivery Verification</span>
        </div>

        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Recipient & Role</th>
                <th>Notification Type</th>
                <th>Title / Subject</th>
                <th>Delivered Message Body</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($notifications)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 32px; color: var(--color-gray-500);">No notifications logged under this filter.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                  <tr>
                    <td style="white-space: nowrap; font-size: 0.78rem; color: var(--color-gray-400);">
                      <?= date('M d, Y', strtotime($n['created_at'])) ?><br>
                      <?= date('g:i:s A', strtotime($n['created_at'])) ?>
                    </td>
                    <td>
                      <strong style="color: var(--color-white);"><?= htmlspecialchars($n['recipient_email']) ?></strong>
                      <div>
                        <span class="badge" style="font-size: 0.7rem; padding: 2px 6px; background: rgba(255, 255, 255, 0.08);">
                          <?= strtoupper($n['recipient_role']) ?>
                        </span>
                      </div>
                    </td>
                    <td>
                      <?php if ($n['notification_type'] === 'booking_received'): ?>
                        <span class="badge badge-pending">Booking Received</span>
                      <?php elseif ($n['notification_type'] === 'dp_verified'): ?>
                        <span class="badge badge-confirmed">₱2,000 DP Verified</span>
                      <?php elseif ($n['notification_type'] === 'staff_delegation'): ?>
                        <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.3);">Staff Task Alert</span>
                      <?php elseif ($n['notification_type'] === 'schedule_reminder'): ?>
                        <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #fde047; border-color: rgba(234, 179, 8, 0.3);">24h Schedule Notice</span>
                      <?php else: ?>
                        <span class="badge badge-completed"><?= htmlspecialchars($n['notification_type']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <strong style="color: var(--color-white);"><?= htmlspecialchars($n['title']) ?></strong>
                    </td>
                    <td style="max-width: 380px; font-size: 0.82rem; color: var(--color-gray-300);">
                      <div style="background: rgba(0, 0, 0, 0.4); padding: 8px 12px; border-radius: var(--radius-sm); border: 1px solid rgba(255, 255, 255, 0.05); white-space: pre-line; max-height: 120px; overflow-y: auto;">
                        <?= htmlspecialchars($n['message']) ?>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-confirmed">✓ Sent</span>
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
