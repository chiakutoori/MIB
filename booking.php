<?php
/**
 * MIB PHOTOMAN - Event Booking & Package Customizer Form
 * 25% Scope Foundation: Replaces manual Google Forms / Messenger distribution with unified DB insertion,
 * real-time pricing calculation, fixed ₱2,000 down payment, and automated email notification dispatch.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Event Booking & Package Customizer | MIB Photoman PH';
$basePath = '';
$db = getDb();

// Pre-fill selected package from URL if present
$selectedPackageSlug = $_GET['package'] ?? 'squad';

$successBooking = null;
$error = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = trim($_POST['client_name'] ?? '');
    $clientEmail = trim($_POST['client_email'] ?? '');
    $clientPhone = trim($_POST['client_phone'] ?? '');
    $eventType = trim($_POST['event_type'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $venueName = trim($_POST['venue_name'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $packageSlug = trim($_POST['package_slug'] ?? 'custom');
    
    $customHours = max(2, min(8, intval($_POST['custom_hours'] ?? 3)));
    $customPhotographers = max(1, min(4, intval($_POST['custom_photographers'] ?? 2)));
    $customOperators = max(1, min(3, intval($_POST['custom_operators'] ?? 1)));
    $magnetUpgraded = isset($_POST['magnet_upgraded']) ? 1 : 0;
    $photoStandees = isset($_POST['photo_standees']) ? 1 : 0;
    
    $specialRequests = trim($_POST['special_requests'] ?? '');
    $paymentReference = trim($_POST['payment_reference'] ?? '');

    if (empty($clientName) || empty($clientEmail) || empty($clientPhone) || empty($eventDate) || empty($venueName)) {
        $error = 'Please fill in all mandatory event and contact fields.';
    } elseif (empty($paymentReference)) {
        $error = 'Please provide your ₱2,000 Down Payment reference number to secure the date.';
    } else {
        // Compute price server-side for integrity
        $package = null;
        if ($packageSlug !== 'custom') {
            $stmt = $db->prepare("SELECT * FROM packages WHERE slug = ?");
            $stmt->execute([$packageSlug]);
            $package = $stmt->fetch();
        }

        if ($package && $packageSlug !== 'custom') {
            $packageName = $package['name'];
            $packageId = $package['id'];
            $basePrice = floatval($package['base_price']);
            
            // Add extras if user extended beyond package defaults
            $extraHours = max(0, $customHours - intval($package['duration_hours'])) * 1500;
            $extraShooters = max(0, $customPhotographers - intval($package['photographers'])) * 2000;
            $extraOperators = max(0, $customOperators - intval($package['operators'])) * 2500;
            $addonStandees = $photoStandees ? 1500 : 0;
            $totalPrice = $basePrice + $extraHours + $extraShooters + $extraOperators + $addonStandees;
        } else {
            $packageName = 'Bespoke Custom Package';
            $packageId = NULL;
            $totalPrice = 5000 + ($customHours * 1500) + ($customPhotographers * 2000) + ($customOperators * 2500);
            if ($photoStandees) $totalPrice += 1500;
            if ($magnetUpgraded) $totalPrice += 1000;
        }

        // Generate unique booking ref: MIB-2026-XXXX
        $bookingRef = 'MIB-' . date('Y') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));

        // Client user ID if logged in
        $clientId = null;
        if (isLoggedIn()) {
            $user = currentUser();
            $clientId = $user['id'];
        }

        try {
            // 1. Insert Booking Record
            $stmtInsert = $db->prepare("
                INSERT INTO bookings (
                    booking_ref, client_id, client_name, client_email, client_phone,
                    event_type, event_date, event_time, venue_name, venue_address,
                    package_id, package_name, custom_hours, custom_photographers, custom_operators,
                    magnet_upgraded, photo_standees, total_price, down_payment, payment_status,
                    booking_status, special_requests, payment_reference
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtInsert->execute([
                $bookingRef, $clientId, $clientName, $clientEmail, $clientPhone,
                $eventType, $eventDate, $eventTime, $venueName, $venueAddress,
                $packageId, $packageName, $customHours, $customPhotographers, $customOperators,
                $magnetUpgraded, $photoStandees, $totalPrice, 2000.00, 'dp_submitted',
                'pending', $specialRequests, $paymentReference
            ]);

            $newBookingId = $db->lastInsertId();

            // 2. Automated Central Notification Engine: Log simulated email dispatch
            $notificationMessage = "Dear {$clientName},\n\nYour event booking request for {$eventType} on {$eventDate} ({$eventTime}) at {$venueName} has been received and logged in the MIB Master Database.\n\nBooking Reference: {$bookingRef}\nPackage: {$packageName}\nTotal Investment: ₱" . number_format($totalPrice, 2) . "\nFixed Down Payment: ₱2,000.00 (Reference: {$paymentReference})\nStatus: Down Payment Submitted (Pending Admin Audit)\n\nThank you for choosing MIB Photoman under CSLB Events Planning.\nContact: 0917-888-MIB1";

            $stmtNotify = $db->prepare("
                INSERT INTO notifications (recipient_email, recipient_role, title, message, notification_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtNotify->execute([
                $clientEmail,
                'client',
                "Booking Acknowledgment - {$bookingRef}",
                $notificationMessage,
                'booking_received'
            ]);

            // Notify Admin as well
            $stmtNotify->execute([
                'admin@mibphotoman.com',
                'admin',
                "New Booking Received: {$bookingRef} ({$clientName})",
                "New booking submitted by {$clientName} for {$eventDate} at {$venueName}. DP Ref: {$paymentReference}. Please review and verify down payment.",
                'booking_received'
            ]);

            $successBooking = [
                'id' => $newBookingId,
                'ref' => $bookingRef,
                'name' => $clientName,
                'email' => $clientEmail,
                'date' => $eventDate,
                'time' => $eventTime,
                'venue' => $venueName,
                'package' => $packageName,
                'total' => $totalPrice,
                'dp_ref' => $paymentReference
            ];

        } catch (Exception $e) {
            $error = 'Database error while saving booking: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

  <main class="section" style="padding-top: 40px;">
    <div class="container">

      <?php if ($successBooking): ?>
        <!-- Booking Success Confirmation Screen -->
        <div class="panel-card" style="max-width: 780px; margin: 20px auto 60px; text-align: center; border: 2px solid var(--color-white);">
          <div style="font-size: 3rem; margin-bottom: 12px;">✅</div>
          <span class="badge badge-confirmed" style="font-size: 0.9rem; padding: 6px 16px;">Reservation Logged in Central Database</span>
          <h1 style="font-size: 2rem; font-weight: 800; font-family: var(--font-logo); margin: 16px 0 8px;">
            BOOKING REQUEST CONFIRMED!
          </h1>
          <p style="color: var(--color-gray-400); max-width: 600px; margin: 0 auto 24px;">
            Your reservation has been submitted and stored in the unified database. An automated booking confirmation email has been logged by the notification engine.
          </p>

          <div style="background: rgba(9, 9, 11, 0.9); border: 1px dashed var(--color-gray-300); border-radius: var(--radius-md); padding: 24px; text-align: left; margin-bottom: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-gray-200); padding-bottom: 12px; margin-bottom: 16px;">
              <span style="font-size: 0.85rem; color: var(--color-gray-400); text-transform: uppercase;">Official Reference Code</span>
              <strong style="font-size: 1.3rem; color: var(--color-white); letter-spacing: 0.08em; font-family: var(--font-logo);"><?= htmlspecialchars($successBooking['ref']) ?></strong>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 0.9rem;">
              <div><span style="color: var(--color-gray-500);">Client Name:</span> <strong><?= htmlspecialchars($successBooking['name']) ?></strong></div>
              <div><span style="color: var(--color-gray-500);">Notification Email:</span> <strong><?= htmlspecialchars($successBooking['email']) ?></strong></div>
              <div><span style="color: var(--color-gray-500);">Event Date:</span> <strong><?= htmlspecialchars($successBooking['date']) ?> (<?= htmlspecialchars($successBooking['time']) ?>)</strong></div>
              <div><span style="color: var(--color-gray-500);">Venue:</span> <strong><?= htmlspecialchars($successBooking['venue']) ?></strong></div>
              <div><span style="color: var(--color-gray-500);">Package Chosen:</span> <strong><?= htmlspecialchars($successBooking['package']) ?></strong></div>
              <div><span style="color: var(--color-gray-500);">Total Investment:</span> <strong>₱<?= number_format($successBooking['total'], 2) ?></strong></div>
              <div><span style="color: var(--color-gray-500);">Fixed Down Payment:</span> <span class="badge badge-pending">₱2,000.00 Recorded</span></div>
              <div><span style="color: var(--color-gray-500);">Payment Ref:</span> <code><?= htmlspecialchars($successBooking['dp_ref']) ?></code></div>
            </div>
          </div>

          <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="client/index.php" class="btn btn-accent btn-lg">View in Client Portal</a>
            <a href="admin/index.php" class="btn btn-outline btn-lg">Admin Audit View</a>
            <a href="index.php" class="btn btn-outline btn-lg">Return to Home</a>
          </div>
        </div>

      <?php else: ?>

        <!-- Page Title -->
        <div style="margin-bottom: 32px;">
          <span class="section-tag">Direct Online Reservation</span>
          <h1 class="section-title" style="margin-bottom: 8px;">Event Booking & Package Customization Form</h1>
          <p class="section-desc">
            Configure your service parameters, specify event logistics, and secure your schedule date with a standardized ₱2,000 down payment.
          </p>
        </div>

        <?php if ($error): ?>
          <div class="flash-msg flash-error" style="margin-bottom: 24px;">
            <span>⚠️ <?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <!-- Booking Layout Grid -->
        <div class="booking-layout">
          <!-- Form Container -->
          <form id="event-booking-form" method="POST" action="booking.php" class="booking-form-box">
            
            <!-- Step 1: Event & Contact Details -->
            <div class="form-step active" data-step="1">
              <div class="step-header">
                <span class="step-number">1</span>
                <div>
                  <h3 class="step-title">Event & Client Information</h3>
                  <p class="step-desc">Provide primary event logistics to reserve your slot on the master calendar.</p>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label" for="client_name">Full Name / Host Contact <span class="req">*</span></label>
                  <input type="text" id="client_name" name="client_name" class="form-input" placeholder="e.g. Juan Dela Cruz" required value="<?= htmlspecialchars($_POST['client_name'] ?? ($user ? $user['full_name'] : '')) ?>">
                </div>

                <div class="form-group">
                  <label class="form-label" for="client_email">Email Address (For Notifications) <span class="req">*</span></label>
                  <input type="email" id="client_email" name="client_email" class="form-input" placeholder="e.g. juan@gmail.com" required value="<?= htmlspecialchars($_POST['client_email'] ?? ($user ? $user['email'] : '')) ?>">
                </div>

                <div class="form-group">
                  <label class="form-label" for="client_phone">Mobile Number <span class="req">*</span></label>
                  <input type="tel" id="client_phone" name="client_phone" class="form-input" placeholder="09XX-XXX-XXXX" required value="<?= htmlspecialchars($_POST['client_phone'] ?? ($user ? $user['phone'] : '')) ?>">
                </div>

                <div class="form-group">
                  <label class="form-label" for="event_type">Type of Event <span class="req">*</span></label>
                  <select id="event_type" name="event_type" class="form-select" required>
                    <option value="Wedding Reception" <?= ($_POST['event_type'] ?? '') === 'Wedding Reception' ? 'selected' : '' ?>>Wedding Reception</option>
                    <option value="18th Debut Celebration" <?= ($_POST['event_type'] ?? '') === '18th Debut Celebration' ? 'selected' : '' ?>>18th Debut Celebration</option>
                    <option value="Corporate Gala / Summit" <?= ($_POST['event_type'] ?? '') === 'Corporate Gala / Summit' ? 'selected' : '' ?>>Corporate Gala / Summit</option>
                    <option value="Birthday / Milestone" <?= ($_POST['event_type'] ?? '') === 'Birthday / Milestone' ? 'selected' : '' ?>>Birthday / Milestone</option>
                    <option value="School Ball / Commencement" <?= ($_POST['event_type'] ?? '') === 'School Ball / Commencement' ? 'selected' : '' ?>>School Ball / Commencement</option>
                    <option value="Other Special Event" <?= ($_POST['event_type'] ?? '') === 'Other Special Event' ? 'selected' : '' ?>>Other Special Event</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label" for="event_date">Event Date <span class="req">*</span></label>
                  <input type="date" id="event_date" name="event_date" class="form-input" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['event_date'] ?? date('Y-m-d', strtotime('+7 days'))) ?>">
                </div>

                <div class="form-group">
                  <label class="form-label" for="event_time">Crew Call Time <span class="req">*</span></label>
                  <input type="time" id="event_time" name="event_time" class="form-input" required value="<?= htmlspecialchars($_POST['event_time'] ?? '16:00') ?>">
                </div>

                <div class="form-group full-width">
                  <label class="form-label" for="venue_name">Venue Name & Ballroom <span class="req">*</span></label>
                  <input type="text" id="venue_name" name="venue_name" class="form-input" placeholder="e.g. The Manila Hotel - Centennial Ballroom" required value="<?= htmlspecialchars($_POST['venue_name'] ?? '') ?>">
                </div>

                <div class="form-group full-width">
                  <label class="form-label" for="venue_address">Venue Full Address & City <span class="req">*</span></label>
                  <input type="text" id="venue_address" name="venue_address" class="form-input" placeholder="e.g. One Rizal Park, Ermita, Manila" required value="<?= htmlspecialchars($_POST['venue_address'] ?? '') ?>">
                </div>
              </div>
            </div>

            <!-- Step 2: Service Configuration & Customizer -->
            <div class="form-step" data-step="2" style="margin-top: 36px;">
              <div class="step-header">
                <span class="step-number">2</span>
                <div>
                  <h3 class="step-title">Package Selection & Interactive Customizer</h3>
                  <p class="step-desc">Pick a curated preset or dynamically adjust hours, photographers, and print stations.</p>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Base Service Package</label>
                <div class="package-radio-group">
                  <label class="package-radio-card <?= $selectedPackageSlug === 'solo' ? 'selected' : '' ?>">
                    <input type="radio" name="package_slug" value="solo" <?= $selectedPackageSlug === 'solo' ? 'checked' : '' ?>>
                    <div class="radio-card-body">
                      <span class="badge">Intimate</span>
                      <h4>The Solo Agent</h4>
                      <div class="price">₱7,500</div>
                      <p>2 Hrs &bull; 1 Roaming Shooter &bull; 1 Operator</p>
                    </div>
                  </label>

                  <label class="package-radio-card <?= $selectedPackageSlug === 'squad' ? 'selected' : '' ?>">
                    <input type="radio" name="package_slug" value="squad" <?= $selectedPackageSlug === 'squad' ? 'checked' : '' ?>>
                    <div class="radio-card-body">
                      <span class="badge badge-confirmed">Most Popular</span>
                      <h4>The Executive Squad</h4>
                      <div class="price">₱12,500</div>
                      <p>3 Hrs &bull; 2 Multi-Angle Shooters &bull; 1 Operator</p>
                    </div>
                  </label>

                  <label class="package-radio-card <?= $selectedPackageSlug === 'syndicate' ? 'selected' : '' ?>">
                    <input type="radio" name="package_slug" value="syndicate" <?= $selectedPackageSlug === 'syndicate' ? 'checked' : '' ?>>
                    <div class="radio-card-body">
                      <span class="badge">Grand Gala</span>
                      <h4>The Syndicate Suite</h4>
                      <div class="price">₱18,500</div>
                      <p>4 Hrs &bull; 2 Master Shooters &bull; 2 Stations</p>
                    </div>
                  </label>

                  <label class="package-radio-card <?= $selectedPackageSlug === 'custom' ? 'selected' : '' ?>">
                    <input type="radio" name="package_slug" value="custom" <?= $selectedPackageSlug === 'custom' ? 'checked' : '' ?>>
                    <div class="radio-card-body">
                      <span class="badge badge-completed">Tailor-Made</span>
                      <h4>Custom Configuration</h4>
                      <div class="price">Custom Rate</div>
                      <p>Fully bespoke configuration</p>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Customizer Controls -->
              <div class="customizer-box" style="background: rgba(9, 9, 11, 0.7); border: 1px solid var(--color-gray-200); border-radius: var(--radius-md); padding: 24px;">
                <h4 style="font-size: 1rem; color: var(--color-white); margin-bottom: 16px; font-family: var(--font-logo);">
                  ⚙️ Fine-Tune Operational Parameters
                </h4>

                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-label" for="custom_hours">Coverage Duration (Hours): <strong id="hours-display">3</strong> hrs</label>
                    <input type="range" id="custom_hours" name="custom_hours" min="2" max="8" step="1" value="3" class="form-range">
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--color-gray-500); margin-top: 4px;">
                      <span>2 hrs</span><span>4 hrs</span><span>6 hrs</span><span>8 hrs</span>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="form-label" for="custom_photographers">Suited Roaming Photographers: <strong id="shooters-display">2</strong></label>
                    <input type="range" id="custom_photographers" name="custom_photographers" min="1" max="4" step="1" value="2" class="form-range">
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--color-gray-500); margin-top: 4px;">
                      <span>1 Shooter</span><span>2 Shooters</span><span>3 Shooters</span><span>4 Shooters</span>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="form-label" for="custom_operators">Dye-Sub Printing Stations: <strong id="operators-display">1</strong></label>
                    <input type="range" id="custom_operators" name="custom_operators" min="1" max="3" step="1" value="1" class="form-range">
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--color-gray-500); margin-top: 4px;">
                      <span>1 Station</span><span>2 Stations</span><span>3 Stations</span>
                    </div>
                  </div>

                  <div class="form-group" style="display: flex; flex-direction: column; justify-content: center; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.88rem; cursor: pointer;">
                      <input type="checkbox" name="magnet_upgraded" id="magnet_upgraded" value="1" checked>
                      <span>Full Magnetic Backings Included (All 4R Prints)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.88rem; cursor: pointer;">
                      <input type="checkbox" name="photo_standees" id="photo_standees" value="1">
                      <span>Add Acrylic VIP Desk Standees (+₱1,500)</span>
                    </label>
                  </div>
                </div>

                <div class="form-group full-width" style="margin-top: 16px;">
                  <label class="form-label" for="special_requests">Special Requests, Theme Notes, or Layout Preferences</label>
                  <textarea id="special_requests" name="special_requests" class="form-textarea" rows="3" placeholder="e.g. Color motif: Emerald & Gold. Please take candid table shots during principal sponsor speeches."></textarea>
                </div>
              </div>
            </div>

            <!-- Step 3: Fixed ₱2,000 Down Payment Verification -->
            <div class="form-step" data-step="3" style="margin-top: 36px;">
              <div class="step-header">
                <span class="step-number">3</span>
                <div>
                  <h3 class="step-title">Fixed ₱2,000 Down Payment Processing</h3>
                  <p class="step-desc">Standard reservation policy under CSLB Events Planning (Chapter 1 Scope & Limitations).</p>
                </div>
              </div>

              <div class="dp-payment-box" style="background: rgba(22, 22, 26, 0.95); border: 1px solid var(--color-gray-200); border-radius: var(--radius-md); padding: 24px; margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid var(--color-gray-200); padding-bottom: 16px; margin-bottom: 16px;">
                  <div>
                    <span style="font-size: 0.8rem; color: var(--color-gray-400); text-transform: uppercase;">Standard Reservation Fee</span>
                    <div style="font-size: 1.8rem; font-weight: 800; color: var(--color-white); font-family: var(--font-logo);">₱2,000.00 FIXED</div>
                  </div>
                  <span class="badge badge-confirmed">Secures Master Calendar Slot</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 0.88rem; margin-bottom: 20px;">
                  <div style="background: rgba(9, 9, 11, 0.8); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--color-gray-200);">
                    <strong style="color: var(--color-white); display: block; margin-bottom: 6px;">📱 GCash Transfer</strong>
                    <div>Account Name: <strong>CSLB Events / MIB Photoman</strong></div>
                    <div>GCash Number: <strong>0917-888-6421</strong></div>
                    <div style="font-size: 0.78rem; color: var(--color-gray-400); margin-top: 4px;">Attach reference number below.</div>
                  </div>

                  <div style="background: rgba(9, 9, 11, 0.8); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--color-gray-200);">
                    <strong style="color: var(--color-white); display: block; margin-bottom: 6px;">🏦 BDO Bank Deposit / Online</strong>
                    <div>Account Name: <strong>CSLB Events Planning</strong></div>
                    <div>Account Number: <strong>0089-2041-9981</strong></div>
                    <div style="font-size: 0.78rem; color: var(--color-gray-400); margin-top: 4px;">Branch: Ortigas Emerald.</div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label" for="payment_reference">Transaction / GCash Reference Number <span class="req">*</span></label>
                  <input type="text" id="payment_reference" name="payment_reference" class="form-input" placeholder="e.g. GCASH-9912048 or BDO-01829" required value="<?= htmlspecialchars($_POST['payment_reference'] ?? '') ?>">
                  <span style="font-size: 0.78rem; color: var(--color-gray-500); margin-top: 4px; display: block;">
                    Entered reference will be cross-checked by the MIB administrator. An email receipt is logged automatically upon submit.
                  </span>
                </div>
              </div>

              <!-- Submit Button -->
              <div style="text-align: right; margin-top: 24px;">
                <button type="submit" class="btn btn-accent btn-lg" style="width: 100%; padding: 18px; font-size: 1.1rem; letter-spacing: 0.05em;">
                  🔒 CONFIRM BOOKING & RECORD ₱2,000 DOWN PAYMENT &rarr;
                </button>
              </div>
            </div>

          </form>

          <!-- Sticky Real-Time Pricing Summary Sidebar -->
          <aside class="booking-sidebar">
            <div class="summary-card">
              <div class="summary-header">
                <h3 class="summary-title">Investment Summary</h3>
                <span class="badge" id="summary-badge">Real-Time</span>
              </div>

              <div class="summary-body">
                <div class="summary-row">
                  <span class="summary-label">Selected Tier:</span>
                  <span class="summary-val" id="summary-pkg-name">The Executive Squad</span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Coverage Duration:</span>
                  <span class="summary-val" id="summary-hours">3 Hours</span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Photographer Crew:</span>
                  <span class="summary-val" id="summary-photographers">2 Roaming Shooters</span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Printing Stations:</span>
                  <span class="summary-val" id="summary-operators">1 High-Speed Station</span>
                </div>
                <div class="summary-row">
                  <span class="summary-label">Magnetic 4R Prints:</span>
                  <span class="summary-val">Unlimited Included</span>
                </div>
                <div class="summary-row" id="row-standees" style="display: none;">
                  <span class="summary-label">Acrylic Standees:</span>
                  <span class="summary-val">+₱1,500.00</span>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-row total-row">
                  <span class="summary-label" style="font-size: 1rem; color: var(--color-white);">Total Investment:</span>
                  <span class="summary-val" id="summary-total-price" style="font-size: 1.4rem; color: var(--color-white); font-family: var(--font-logo);">₱12,500.00</span>
                </div>

                <div class="summary-row" style="margin-top: 12px; background: rgba(255, 255, 255, 0.05); padding: 10px; border-radius: var(--radius-sm);">
                  <span class="summary-label">Fixed Reservation DP:</span>
                  <span class="summary-val" style="color: #34d399; font-weight: 700;">₱2,000.00</span>
                </div>

                <div class="summary-row" style="font-size: 0.8rem; color: var(--color-gray-400); margin-top: 6px;">
                  <span class="summary-label">Remaining Balance at Venue:</span>
                  <span class="summary-val" id="summary-balance">₱10,500.00</span>
                </div>

                <div style="margin-top: 20px; font-size: 0.75rem; color: var(--color-gray-500); line-height: 1.4;">
                  🛡️ Submitting this form directly records your booking in the central database and dispatches an automated acknowledgment notification.
                </div>
              </div>
            </div>
          </aside>
        </div>

      <?php endif; ?>

    </div>
  </main>

  <!-- Interactive Pricing Computation Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const pkgRadios = document.querySelectorAll('input[name="package_slug"]');
      const hoursSlider = document.getElementById('custom_hours');
      const shootersSlider = document.getElementById('custom_photographers');
      const operatorsSlider = document.getElementById('custom_operators');
      const standeesCheck = document.getElementById('photo_standees');

      const hoursDisplay = document.getElementById('hours-display');
      const shootersDisplay = document.getElementById('shooters-display');
      const operatorsDisplay = document.getElementById('operators-display');

      const summaryPkg = document.getElementById('summary-pkg-name');
      const summaryHours = document.getElementById('summary-hours');
      const summaryPhotographers = document.getElementById('summary-photographers');
      const summaryOperators = document.getElementById('summary-operators');
      const summaryTotal = document.getElementById('summary-total-price');
      const summaryBalance = document.getElementById('summary-balance');
      const rowStandees = document.getElementById('row-standees');

      function calculate() {
        if (!hoursSlider) return;

        let selectedSlug = 'squad';
        pkgRadios.forEach(r => {
          if (r.checked) selectedSlug = r.value;
          const card = r.closest('.package-radio-card');
          if (card) card.classList.toggle('selected', r.checked);
        });

        const hours = parseInt(hoursSlider.value);
        const shooters = parseInt(shootersSlider.value);
        const operators = parseInt(operatorsSlider.value);
        const hasStandees = standeesCheck && standeesCheck.checked;

        if (hoursDisplay) hoursDisplay.textContent = hours;
        if (shootersDisplay) shootersDisplay.textContent = shooters;
        if (operatorsDisplay) operatorsDisplay.textContent = operators;

        let total = 0;
        let pkgTitle = 'The Executive Squad';

        if (selectedSlug === 'solo') {
          pkgTitle = 'The Solo Agent';
          const base = 7500;
          const extraH = Math.max(0, hours - 2) * 1500;
          const extraS = Math.max(0, shooters - 1) * 2000;
          const extraO = Math.max(0, operators - 1) * 2500;
          total = base + extraH + extraS + extraO;
        } else if (selectedSlug === 'squad') {
          pkgTitle = 'The Executive Squad';
          const base = 12500;
          const extraH = Math.max(0, hours - 3) * 1500;
          const extraS = Math.max(0, shooters - 2) * 2000;
          const extraO = Math.max(0, operators - 1) * 2500;
          total = base + extraH + extraS + extraO;
        } else if (selectedSlug === 'syndicate') {
          pkgTitle = 'The Syndicate Suite';
          const base = 18500;
          const extraH = Math.max(0, hours - 4) * 1500;
          const extraS = Math.max(0, shooters - 2) * 2000;
          const extraO = Math.max(0, operators - 2) * 2500;
          total = base + extraH + extraS + extraO;
        } else {
          pkgTitle = 'Bespoke Custom Package';
          total = 5000 + (hours * 1500) + (shooters * 2000) + (operators * 2500);
        }

        if (hasStandees) {
          total += 1500;
          if (rowStandees) rowStandees.style.display = 'flex';
        } else {
          if (rowStandees) rowStandees.style.display = 'none';
        }

        const balance = Math.max(0, total - 2000);

        if (summaryPkg) summaryPkg.textContent = pkgTitle;
        if (summaryHours) summaryHours.textContent = hours + ' Hours';
        if (summaryPhotographers) summaryPhotographers.textContent = shooters + ' Roaming ' + (shooters > 1 ? 'Shooters' : 'Shooter');
        if (summaryOperators) summaryOperators.textContent = operators + ' Dye-Sub ' + (operators > 1 ? 'Stations' : 'Station');
        if (summaryTotal) summaryTotal.textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
        if (summaryBalance) summaryBalance.textContent = '₱' + balance.toLocaleString('en-US', {minimumFractionDigits: 2});
      }

      // Sync radio changes with slider defaults
      pkgRadios.forEach(r => {
        r.addEventListener('change', () => {
          if (r.value === 'solo') {
            hoursSlider.value = 2;
            shootersSlider.value = 1;
            operatorsSlider.value = 1;
          } else if (r.value === 'squad') {
            hoursSlider.value = 3;
            shootersSlider.value = 2;
            operatorsSlider.value = 1;
          } else if (r.value === 'syndicate') {
            hoursSlider.value = 4;
            shootersSlider.value = 2;
            operatorsSlider.value = 2;
          }
          calculate();
        });
      });

      if (hoursSlider) hoursSlider.addEventListener('input', calculate);
      if (shootersSlider) shootersSlider.addEventListener('input', calculate);
      if (operatorsSlider) operatorsSlider.addEventListener('input', calculate);
      if (standeesCheck) standeesCheck.addEventListener('change', calculate);

      calculate();
    });
  </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
