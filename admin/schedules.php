<?php
/**
 * MIB PHOTOMAN - Crew Scheduling & Conflict Detection Module
 * Solves Chapter 1 Specific Objective 3: Centralized photographer/operator delegation
 * and intelligent double-booking conflict detection.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin', '../login.php');

$pageTitle = 'Staff Scheduling & Delegation | MIB Photoman Admin';
$basePath = '../';
$db = getDb();

$conflictWarning = null;

// Handle Staff Assignment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_staff') {
    $bookingId = intval($_POST['booking_id']);
    $staffId = intval($_POST['staff_id']);
    $roleInEvent = trim($_POST['role_in_event']);
    $notes = trim($_POST['notes']);

    // Fetch the target event date
    $eventStmt = $db->prepare("SELECT booking_ref, event_type, event_date, event_time, venue_name FROM bookings WHERE id = ?");
    $eventStmt->execute([$bookingId]);
    $event = $eventStmt->fetch();

    // Conflict Check: Is this staff member already assigned on this exact date?
    $conflictCheck = $db->prepare("
        SELECT b.booking_ref, b.event_type, b.venue_name, sa.role_in_event
        FROM staff_assignments sa
        JOIN bookings b ON sa.booking_id = b.id
        WHERE sa.staff_id = ? AND b.event_date = ? AND b.id != ?
    ");
    $conflictCheck->execute([$staffId, $event['event_date'], $bookingId]);
    $existingConflict = $conflictCheck->fetch();

    if ($existingConflict) {
        $conflictWarning = "ATTENTION: This staff member is ALREADY assigned to {$existingConflict['booking_ref']} ({$existingConflict['event_type']} at {$existingConflict['venue_name']}) on {$event['event_date']}!";
    }

    // Insert or update assignment
    try {
        $stmt = $db->prepare("
            INSERT INTO staff_assignments (booking_id, staff_id, role_in_event, assigned_by, status, notes)
            VALUES (?, ?, ?, ?, 'assigned', ?)
            ON CONFLICT(booking_id, staff_id) DO UPDATE SET
            role_in_event = excluded.role_in_event,
            notes = excluded.notes
        ");
        $stmt->execute([$bookingId, $staffId, $roleInEvent, $_SESSION['user_id'], $notes]);

        // Automated notification to the assigned staff member!
        $staffUser = $db->query("SELECT email, full_name FROM users WHERE id = $staffId")->fetch();
        if ($staffUser) {
            $msg = "Hello {$staffUser['full_name']},\n\nYou have been officially assigned as [{$roleInEvent}] for event {$event['booking_ref']} ({$event['event_type']}) on {$event['event_date']} ({$event['event_time']}) at {$event['venue_name']}.\n\nNotes: {$notes}\n\nPlease view your schedule in the MIB Staff Portal.";
            $db->prepare("INSERT INTO notifications (recipient_email, recipient_role, title, message, notification_type) VALUES (?, 'staff', ?, ?, 'staff_delegation')")
               ->execute([$staffUser['email'], "Staff Assignment: {$roleInEvent} ({$event['booking_ref']})", $msg]);
        }

        if ($conflictWarning) {
            setFlash('error', "Assignment saved, but CONFLICT DETECTED: " . $conflictWarning);
        } else {
            setFlash('success', "Staff successfully assigned to {$event['booking_ref']}! Automated task alert dispatched to their inbox.");
        }
    } catch (Exception $e) {
        setFlash('error', "Failed to assign staff: " . $e->getMessage());
    }

    header("Location: schedules.php" . ($bookingId ? "?booking_id=$bookingId" : ''));
    exit;
}

// Handle Remove Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_assignment') {
    $assignId = intval($_POST['assignment_id']);
    $db->prepare("DELETE FROM staff_assignments WHERE id = ?")->execute([$assignId]);
    setFlash('success', "Crew assignment removed.");
    header("Location: schedules.php");
    exit;
}

// Available events for dropdown
$events = $db->query("SELECT id, booking_ref, event_type, event_date, event_time, venue_name FROM bookings WHERE booking_status IN ('pending', 'confirmed') ORDER BY event_date ASC")->fetchAll();

// Available staff members
$staffMembers = $db->query("SELECT id, full_name, email, role_title, phone FROM users WHERE role = 'staff' ORDER BY full_name ASC")->fetchAll();

// Selected booking prefill
$prefillBookingId = intval($_GET['booking_id'] ?? 0);

// Fetch all active assignments grouped with conflicts highlighted
$assignments = $db->query("
    SELECT sa.id as assignment_id, sa.role_in_event, sa.notes, sa.status as assign_status,
           b.id as booking_id, b.booking_ref, b.event_type, b.event_date, b.event_time, b.venue_name,
           u.id as staff_id, u.full_name as staff_name, u.role_title, u.phone as staff_phone,
           (SELECT COUNT(*) FROM staff_assignments sa2 
            JOIN bookings b2 ON sa2.booking_id = b2.id 
            WHERE sa2.staff_id = sa.staff_id AND b2.event_date = b.event_date AND b2.id != b.id) as conflict_count
    FROM staff_assignments sa
    JOIN bookings b ON sa.booking_id = b.id
    JOIN users u ON sa.staff_id = u.id
    ORDER BY b.event_date ASC, b.event_time ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container">

      <!-- Navigation Bar -->
      <div class="portal-header">
        <div>
          <span class="section-tag">Human Resource & Operations</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Staff Scheduling & Conflict Detection</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Delegate suited photographers and dye-sub operators to upcoming events with automatic double-booking prevention.</p>
        </div>

        <div class="portal-nav-pills">
          <a href="index.php" class="portal-pill">📊 Overview</a>
          <a href="bookings.php" class="portal-pill">📋 Bookings & Payments</a>
          <a href="schedules.php" class="portal-pill active">👥 Crew Schedules</a>
          <a href="journals.php" class="portal-pill">📝 Post-Event Journals</a>
          <a href="reviews.php" class="portal-pill">⭐ Customer Reviews</a>
          <a href="notifications.php" class="portal-pill">✉️ Notification Logs</a>
        </div>
      </div>

      <!-- Delegation Form & Conflict Guide Layout -->
      <div style="display: grid; grid-template-columns: 1.3fr 1fr; gap: 24px; margin-bottom: 36px;">
        
        <!-- Delegation Form -->
        <div class="panel-card" style="margin-bottom: 0;">
          <h2 class="panel-card-title">Assign Crew Member to Event</h2>
          <p style="font-size: 0.85rem; color: var(--color-gray-400); margin-bottom: 20px;">
            Select an active event and assign an available suited talent. Automatically logs an email alert to the crew member.
          </p>

          <form method="POST" action="schedules.php">
            <input type="hidden" name="action" value="assign_staff">

            <div class="form-group" style="margin-bottom: 16px;">
              <label class="form-label" for="booking_id">Select Event <span class="req">*</span></label>
              <select name="booking_id" id="booking_id" class="form-select" required>
                <option value="">-- Choose an Event on Master Calendar --</option>
                <?php foreach ($events as $ev): ?>
                  <option value="<?= $ev['id'] ?>" <?= $prefillBookingId === $ev['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['booking_ref']) ?> &bull; <?= htmlspecialchars($ev['event_type']) ?> &bull; <?= date('M d, Y', strtotime($ev['event_date'])) ?> (<?= htmlspecialchars($ev['venue_name']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-grid" style="margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="staff_id">Crew Member <span class="req">*</span></label>
                <select name="staff_id" id="staff_id" class="form-select" required>
                  <option value="">-- Select Talent --</option>
                  <?php foreach ($staffMembers as $st): ?>
                    <option value="<?= $st['id'] ?>">
                      <?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['role_title']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="role_in_event">Delegated Role <span class="req">*</span></label>
                <select name="role_in_event" id="role_in_event" class="form-select" required>
                  <option value="Lead Roaming Photographer">Lead Roaming Photographer (Sony A7IV)</option>
                  <option value="Second Shooter">Second Shooter & Lighting Tech</option>
                  <option value="Printer Operator">High-Speed Dye-Sub Station Operator</option>
                  <option value="Production Assistant">Production & Keepsake Assistant</option>
                </select>
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
              <label class="form-label" for="notes">Call Time & Equipment Directives</label>
              <input type="text" id="notes" name="notes" class="form-input" placeholder="e.g. Call time 15:30. Bring 35mm f/1.4 GM and spare flash batteries. Dress code: Formal Black Suit.">
            </div>

            <button type="submit" class="btn btn-accent" style="width: 100%; padding: 12px; font-weight: 700;">
              Confirm Staff Delegation & Dispatch Notification &rarr;
            </button>
          </form>
        </div>

        <!-- Conflict Prevention Rule Box -->
        <div class="panel-card" style="margin-bottom: 0; background: rgba(9, 9, 11, 0.85);">
          <h3 style="font-size: 1.1rem; color: var(--color-white); margin-bottom: 12px; font-weight: 700;">
            🛡️ Scheduling Conflict Engine
          </h3>
          <p style="font-size: 0.85rem; color: var(--color-gray-400); margin-bottom: 16px; line-height: 1.5;">
            In Chapter 1 Problem Statement, MIB Photoman identified that coordinating assignments over Messenger led to <strong>overlooked bookings and scheduling conflicts</strong>.
          </p>
          <ul style="font-size: 0.82rem; color: var(--color-gray-300); list-style: none; display: flex; flex-direction: column; gap: 10px;">
            <li>✓ <strong>Real-Time Cross-Event Audit:</strong> The system automatically verifies whether a staff member is booked on the same date across all active reservations.</li>
            <li>✓ <strong>Visual Conflict Alert:</strong> Flags overlapping assignments with high-contrast red warning badges.</li>
            <li>✓ <strong>Centralized Messenger Replacement:</strong> Staff receive formal task notices directly into their portal and notification logs.</li>
          </ul>

          <div style="margin-top: 24px; padding: 14px; background: rgba(255, 255, 255, 0.05); border-radius: var(--radius-sm); border-left: 3px solid var(--color-white);">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-gray-400);">Active Suited Roster</div>
            <div style="font-size: 1.1rem; font-weight: 800; color: var(--color-white);"><?= count($staffMembers) ?> Qualified Personnel Available</div>
          </div>
        </div>

      </div>

      <!-- Master Assigned Crew Schedule Table -->
      <div class="panel-card">
        <div class="panel-card-header">
          <h2 class="panel-card-title">📅 Master Crew Schedule & Deployment Timeline</h2>
          <span class="badge badge-completed"><?= count($assignments) ?> Active Assignments</span>
        </div>

        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Event Date / Call Time</th>
                <th>Booking Reference</th>
                <th>Venue & Event Type</th>
                <th>Assigned Talent</th>
                <th>Role Delegated</th>
                <th>Directives / Notes</th>
                <th>Conflict Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($assignments)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; padding: 32px; color: var(--color-gray-500);">No crew members currently assigned.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($assignments as $as): ?>
                  <tr>
                    <td>
                      <strong><?= date('M d, Y', strtotime($as['event_date'])) ?></strong>
                      <div style="font-size: 0.8rem; color: var(--color-gray-400);"><?= date('g:i A', strtotime($as['event_time'])) ?></div>
                    </td>
                    <td>
                      <strong style="color: var(--color-white); letter-spacing: 0.05em;"><?= htmlspecialchars($as['booking_ref']) ?></strong>
                    </td>
                    <td>
                      <div style="color: var(--color-white); font-weight: 600;"><?= htmlspecialchars($as['venue_name']) ?></div>
                      <div style="font-size: 0.8rem; color: var(--color-gray-400);"><?= htmlspecialchars($as['event_type']) ?></div>
                    </td>
                    <td>
                      <div style="font-weight: 700; color: var(--color-white);"><?= htmlspecialchars($as['staff_name']) ?></div>
                      <div style="font-size: 0.75rem; color: var(--color-gray-400);"><?= htmlspecialchars($as['staff_phone']) ?></div>
                    </td>
                    <td>
                      <span class="badge" style="background: rgba(255, 255, 255, 0.1); color: #ffffff;"><?= htmlspecialchars($as['role_in_event']) ?></span>
                    </td>
                    <td style="max-width: 200px; font-size: 0.8rem; color: var(--color-gray-400);">
                      <?= htmlspecialchars($as['notes'] ?: 'Standard MIB Protocol.') ?>
                    </td>
                    <td>
                      <?php if ($as['conflict_count'] > 0): ?>
                        <span class="badge badge-conflict">⚠️ CONFLICT (<?= $as['conflict_count'] + 1 ?> Events)</span>
                      <?php else: ?>
                        <span class="badge badge-confirmed">✓ Clear</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form method="POST" action="schedules.php" onsubmit="return confirm('Remove this crew assignment?');">
                        <input type="hidden" name="action" value="remove_assignment">
                        <input type="hidden" name="assignment_id" value="<?= $as['assignment_id'] ?>">
                        <button type="submit" class="btn btn-outline" style="font-size: 0.7rem; padding: 4px 8px; color: #f87171; border-color: rgba(239, 68, 68, 0.4);">
                          Remove
                        </button>
                      </form>
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
