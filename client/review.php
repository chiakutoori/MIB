<?php
/**
 * MIB PHOTOMAN - Customer Service Review Form
 * Solves Chapter 1 Specific Objective 5: Client feedback for internal evaluation
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('client', '../login.php');

$pageTitle = 'Submit Service Review | MIB Photoman Client Portal';
$basePath = '../';
$db = getDb();
$user = currentUser();

$selectedBookingId = intval($_GET['booking_id'] ?? 0);
$error = null;

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = intval($_POST['booking_id'] ?? 0);
    $rating = max(1, min(5, intval($_POST['rating'] ?? 5)));
    $photoRating = max(1, min(5, intval($_POST['photographer_rating'] ?? 5)));
    $printRating = max(1, min(5, intval($_POST['print_quality_rating'] ?? 5)));
    $feedback = trim($_POST['feedback_text'] ?? '');
    $recommend = isset($_POST['would_recommend']) ? intval($_POST['would_recommend']) : 1;

    if (!$bookingId || empty($feedback)) {
        $error = 'Please provide written feedback for your event experience.';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO customer_reviews (booking_id, client_id, rating, photographer_rating, print_quality_rating, feedback_text, would_recommend)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$bookingId, $user['id'], $rating, $photoRating, $printRating, $feedback, $recommend]);

            setFlash('success', 'Thank you! Your service review and ratings have been submitted to MIB management.');
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $error = 'Failed to record review: ' . $e->getMessage();
        }
    }
}

// Fetch completed bookings for this client that haven't been reviewed yet
$eligibleBookings = $db->prepare("
    SELECT b.id, b.booking_ref, b.event_type, b.event_date, b.venue_name
    FROM bookings b
    LEFT JOIN customer_reviews cr ON b.id = cr.booking_id
    WHERE (b.client_id = ? OR b.client_email = ?)
      AND cr.id IS NULL
    ORDER BY b.event_date DESC
");
$eligibleBookings->execute([$user['id'], $user['email']]);
$unreviewed = $eligibleBookings->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

  <main class="portal-container">
    <div class="container" style="max-width: 800px;">

      <div class="portal-header">
        <div>
          <span class="section-tag">Service Evaluation</span>
          <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-logo);">Client Service Feedback</h1>
          <p style="color: var(--color-gray-400); font-size: 0.9rem;">Your ratings help MIB Photoman management evaluate staff performance and maintain elite service standards.</p>
        </div>

        <div>
          <a href="index.php" class="btn btn-outline">&larr; Return to My Bookings</a>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="flash-msg flash-error">
          <span>⚠️ <?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if (empty($unreviewed) && !$selectedBookingId): ?>
        <div class="panel-card" style="text-align: center; padding: 48px;">
          <div style="font-size: 2.5rem; margin-bottom: 12px;">🎉</div>
          <h3 style="color: var(--color-white); margin-bottom: 8px;">All Reviews Completed!</h3>
          <p style="color: var(--color-gray-400); max-width: 480px; margin: 0 auto 20px;">
            You have submitted feedback for all your completed events. We appreciate your partnership!
          </p>
          <a href="index.php" class="btn btn-accent">View My Dashboard</a>
        </div>
      <?php else: ?>

        <div class="panel-card">
          <form method="POST" action="review.php">
            
            <div class="form-group" style="margin-bottom: 24px;">
              <label class="form-label" for="booking_id">Select Event <span class="req">*</span></label>
              <select name="booking_id" id="booking_id" class="form-select" required>
                <?php foreach ($unreviewed as $ev): ?>
                  <option value="<?= $ev['id'] ?>" <?= $selectedBookingId === $ev['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['booking_ref']) ?> &bull; <?= htmlspecialchars($ev['event_type']) ?> &bull; <?= date('M d, Y', strtotime($ev['event_date'])) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Overall Experience Rating -->
            <div class="form-group" style="margin-bottom: 24px;">
              <label class="form-label">Overall Event Experience Rating <span class="req">*</span></label>
              <div style="display: flex; gap: 16px; margin-top: 8px;">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; background: rgba(9, 9, 11, 0.7); padding: 10px 16px; border-radius: var(--radius-sm); border: 1px solid var(--color-gray-200);">
                    <input type="radio" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                    <span style="font-weight: 700; color: #fbbf24;"><?= str_repeat('★', $i) ?> (<?= $i ?>/5)</span>
                  </label>
                <?php endfor; ?>
              </div>
            </div>

            <!-- Detailed Sub-Ratings -->
            <div class="form-grid" style="margin-bottom: 24px;">
              <div class="form-group">
                <label class="form-label" for="photographer_rating">Photographer Demeanor & Suited Presentation</label>
                <select name="photographer_rating" id="photographer_rating" class="form-select">
                  <option value="5">⭐⭐⭐⭐⭐ 5 - Exceptional / Razor-sharp attire</option>
                  <option value="4">⭐⭐⭐⭐ 4 - Very Good / Professional</option>
                  <option value="3">⭐⭐⭐ 3 - Satisfactory</option>
                  <option value="2">⭐⭐ 2 - Needs Improvement</option>
                  <option value="1">⭐ 1 - Unsatisfactory</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="print_quality_rating">Print & Magnetic Backing Quality</label>
                <select name="print_quality_rating" id="print_quality_rating" class="form-select">
                  <option value="5">⭐⭐⭐⭐⭐ 5 - Flawless high-gloss / instant delivery</option>
                  <option value="4">⭐⭐⭐⭐ 4 - Great color fidelity</option>
                  <option value="3">⭐⭐⭐ 3 - Good</option>
                  <option value="2">⭐⭐ 2 - Slight delay or quality defect</option>
                  <option value="1">⭐ 1 - Poor print results</option>
                </select>
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
              <label class="form-label" for="feedback_text">Detailed Qualitative Feedback & Guest Impressions <span class="req">*</span></label>
              <textarea id="feedback_text" name="feedback_text" class="form-textarea" rows="4" placeholder="Tell us about your guests' reactions to the instant roaming prints, staff courtesy, and overall value..." required></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 28px;">
              <label class="form-label">Would you recommend MIB Photoman to friends or colleagues?</label>
              <div style="display: flex; gap: 20px; margin-top: 8px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                  <input type="radio" name="would_recommend" value="1" checked>
                  <span style="color: var(--color-white); font-weight: 600;">✓ Yes, absolutely recommend</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                  <input type="radio" name="would_recommend" value="0">
                  <span style="color: var(--color-gray-400);">No</span>
                </label>
              </div>
            </div>

            <button type="submit" class="btn btn-accent btn-lg" style="width: 100%; padding: 16px; font-weight: 700;">
              ⭐ Submit Service Evaluation & Feedback &rarr;
            </button>
          </form>
        </div>

      <?php endif; ?>

    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
