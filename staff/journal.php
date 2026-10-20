<?php
/**
 * MIB PHOTOMAN - Post-Event Journal Entry Form
 * Allows assigned photographers and operators to log prints distributed, equipment health,
 * and operational observations directly into the database.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('staff', '../login.php');

$pageTitle = 'Log Post-Event Journal | MIB Photoman Staff';
$basePath = '../';
$db = getDb();
$user = currentUser();

$selectedBookingId = intval($_GET['booking_id'] ?? 0);
$error = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = intval($_POST['booking_id'] ?? 0);
    $prints = intval($_POST['prints_distributed'] ?? 0);
    $guests = intval($_POST['guest_count_estimated'] ?? 0);
    $equipment = trim($_POST['equipment_status'] ?? '');
    $issues = trim($_POST['issues_encountered'] ?? '');
    $highlights = trim($_POST['highlights_and_notes'] ?? '');

    if (!$bookingId || empty($equipment) || empty($highlights)) {
        $error = 'Please fill out all required journal fields.';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO post_event_journals (booking_id, staff_id, prints_distributed, guest_count_estimated, equipment_status, issues_encountered, highlights_and_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$bookingId, $user['id'], $prints, $guests, $equipment, $issues, $highlights]);

            setFlash('success', 'Post-event journal successfully submitted to the central archive!');
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $error = 'Failed to submit journal: ' . $e->getMessage();
        }
    }
}

// Fetch assigned events for dropdown
$assignedEvents = $db->prepare("
    SELECT b.id, b.booking_ref, b.event_type, b.event_date, b.venue_name
    FROM staff_assignments sa
    JOIN bookings b ON sa.booking_id = b.id
    WHERE sa.staff_id = ?
    ORDER BY b.event_date DESC
");
$assignedEvents->execute([$user['id']]);
$eventsList = $assignedEvents->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container" style="max-width: 800px;">

      <div class="portal-header">
        <div>
          <span class="section-tag">Internal Operations</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Post-Event Journal Entry</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Document print yield, attendance, and technical notes for internal management review.</p>
        </div>

        <div>
          <a href="index.php" class="btn btn-outline">&larr; Return to Schedule</a>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="flash-msg flash-error">
          <span>⚠️ <?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <div class="panel-card">
        <form method="POST" action="journal.php">
          
          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="booking_id">Select Completed Event <span class="req">*</span></label>
            <select name="booking_id" id="booking_id" class="form-select" required>
              <option value="">-- Choose Assigned Event --</option>
              <?php foreach ($eventsList as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= $selectedBookingId === $ev['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ev['booking_ref']) ?> &bull; <?= htmlspecialchars($ev['event_type']) ?> &bull; <?= date('M d, Y', strtotime($ev['event_date'])) ?> (<?= htmlspecialchars($ev['venue_name']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-grid" style="margin-bottom: 20px;">
            <div class="form-group">
              <label class="form-label" for="prints_distributed">Total 4R Magnetic Prints Distributed <span class="req">*</span></label>
              <input type="number" id="prints_distributed" name="prints_distributed" class="form-input" min="0" placeholder="e.g. 380" required value="<?= htmlspecialchars($_POST['prints_distributed'] ?? '350') ?>">
            </div>

            <div class="form-group">
              <label class="form-label" for="guest_count_estimated">Estimated Guest Attendance <span class="req">*</span></label>
              <input type="number" id="guest_count_estimated" name="guest_count_estimated" class="form-input" min="0" placeholder="e.g. 220" required value="<?= htmlspecialchars($_POST['guest_count_estimated'] ?? '200') ?>">
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="equipment_status">Equipment & Dye-Sub Station Health <span class="req">*</span></label>
            <input type="text" id="equipment_status" name="equipment_status" class="form-input" placeholder="e.g. DNP DS620A printer performed smoothly. Sony A7IV and Godox AD200 flashes normal." required value="<?= htmlspecialchars($_POST['equipment_status'] ?? 'All gear functioning optimally. Zero paper jams.') ?>">
          </div>

          <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="issues_encountered">Technical or Venue Issues Encountered</label>
            <textarea id="issues_encountered" name="issues_encountered" class="form-textarea" rows="3" placeholder="e.g. Venue power socket was far from printing booth, resolved using 15m extension reel. Or 'None reported.'"><?= htmlspecialchars($_POST['issues_encountered'] ?? '') ?></textarea>
          </div>

          <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="highlights_and_notes">Event Highlights & Client Feedback Observations <span class="req">*</span></label>
            <textarea id="highlights_and_notes" name="highlights_and_notes" class="form-textarea" rows="4" placeholder="e.g. Host and guests praised the quick table distribution. High demand for custom themed layout frames." required><?= htmlspecialchars($_POST['highlights_and_notes'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn-accent btn-lg" style="width: 100%; padding: 14px; font-weight: 700;">
            💾 Save Journal Entry to Internal Archive &rarr;
          </button>
        </form>
      </div>

    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
