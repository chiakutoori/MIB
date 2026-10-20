<?php
/**
 * MIB PHOTOMAN - Home Page
 * Explains the service in plain language: what it is, how booking works, packages, reviews and FAQs
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'MIB Photoman PH | Event Photography with Instant Printed Souvenirs';
$basePath = '';

$db = getDb();
// Latest customer reviews
$reviews = $db->query("
    SELECT r.*, b.event_type, b.venue_name, u.full_name as client_name
    FROM customer_reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN users u ON r.client_id = u.id
    ORDER BY r.submitted_at DESC LIMIT 3
")->fetchAll();

$packages = $db->query("SELECT * FROM packages ORDER BY base_price ASC")->fetchAll();
$startingPrice = $packages ? min(array_column($packages, 'base_price')) : 0;

// Each package photo shows a different part of the crew photo
$packagePhotoPositions = ['0% 0%', '100% 100%', '0% 100%'];

require_once __DIR__ . '/includes/header.php';
?>

  <!-- Hero -->
  <section class="hero-section hero-has-video" id="top">
    <div class="hero-video-wrap" aria-hidden="true">
      <video class="hero-video" autoplay muted loop playsinline preload="auto" poster="assets/videos/hero-poster.jpg">
        <source src="assets/videos/hero-photography.mp4" type="video/mp4">
      </video>
      <div class="hero-video-overlay"></div>
      <div class="hero-grain"></div>
      <span class="vf-corner vf-tl"></span><span class="vf-corner vf-tr"></span>
      <span class="vf-corner vf-bl"></span><span class="vf-corner vf-br"></span>
    </div>

    <button type="button" class="hero-video-toggle" id="hero-video-toggle" aria-label="Pause background video">
      <span class="toggle-icon">❚❚</span>
    </button>

    <div class="container hero-grid">
      <div class="hero-copy">
        <h1 class="hero-title">
          Your guests go home with <span class="highlight">printed photos</span> of your event
        </h1>

        <p class="hero-subtitle">
          Our photographers, dressed in suits, walk around your event and take photos of your guests at their tables.
          We print the photos on site in minutes, as 4R prints with a magnet backing, so every guest takes home a keepsake.
        </p>

        <div class="hero-cta-group">
          <a href="#packages" class="btn btn-accent btn-lg">See Packages &amp; Prices</a>
          <a href="booking.php" class="btn btn-outline btn-lg">Book Your Date</a>
        </div>

        <div class="hero-stats">
          <div>
            <div class="hero-stat-num">₱<?= number_format($startingPrice) ?></div>
            <div class="hero-stat-label">Packages start at</div>
          </div>
          <div>
            <div class="hero-stat-num">₱2,000</div>
            <div class="hero-stat-label">Reserves your date</div>
          </div>
          <div>
            <div class="hero-stat-num">2–8 hrs</div>
            <div class="hero-stat-label">Coverage options</div>
          </div>
        </div>
      </div>

      <div class="hero-polaroid-gallery" aria-hidden="true">
        <div class="polaroid-card">
          <div class="polaroid-img-wrapper"><div class="polaroid-crop" style="background-position: 2% 3%;"></div></div>
          <span class="polaroid-caption">Say cheese!</span>
        </div>
        <div class="polaroid-card">
          <div class="polaroid-img-wrapper"><div class="polaroid-crop" style="background-position: 80% 97%;"></div></div>
          <span class="polaroid-caption">Printed in minutes</span>
        </div>
        <div class="polaroid-card">
          <div class="polaroid-img-wrapper"><div class="polaroid-crop" style="background-position: 99% 50%;"></div></div>
          <span class="polaroid-caption">Yours to keep</span>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="section" id="how-it-works">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">How it works</span>
        <h2 class="section-title">Book in four easy steps</h2>
        <p class="section-desc">You can book entirely online.</p>
      </div>

      <ol class="steps-grid">
        <li class="feature-card step-card">
          <span class="step-num">1</span>
          <h3 class="feature-title">Pick a package</h3>
          <p class="feature-text">Choose one of our packages, or build your own by picking the hours, number of photographers and print stations you need.</p>
        </li>
        <li class="feature-card step-card">
          <span class="step-num">2</span>
          <h3 class="feature-title">Fill out the booking form</h3>
          <p class="feature-text">Tell us your event date, time, venue and type of celebration. The total price updates as you go.</p>
        </li>
        <li class="feature-card step-card">
          <span class="step-num">3</span>
          <h3 class="feature-title">Pay ₱2,000 to reserve</h3>
          <p class="feature-text">Send the ₱2,000 down payment through GCash and enter your reference number. You pay the rest at the venue on the event day.</p>
        </li>
        <li class="feature-card step-card">
          <span class="step-num">4</span>
          <h3 class="feature-title">Enjoy your event</h3>
          <p class="feature-text">Once we confirm your payment, we assign your photographers. On the day, they arrive in suits, take photos and print them on site.</p>
        </li>
      </ol>
    </div>
  </section>

  <!-- What's Included -->
  <section class="section section-alt">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">What you get</span>
        <h2 class="section-title">Included at every event</h2>
        <p class="section-desc">Every package comes with these.</p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon-box">📷</div>
          <h3 class="feature-title">Photographers who come to your guests</h3>
          <p class="feature-text">No lines at a photo booth. Our photographers walk from table to table so nobody gets left out.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-box">🖨️</div>
          <h3 class="feature-title">Photos printed on the spot</h3>
          <p class="feature-text">Our print station at the venue turns photos into glossy 4R prints within minutes.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-box">🧲</div>
          <h3 class="feature-title">Magnetic keepsakes</h3>
          <p class="feature-text">Every print has a magnet backing, so guests can stick it on the fridge as a reminder of your celebration.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon-box">🎨</div>
          <h3 class="feature-title">A frame made for your event</h3>
          <p class="feature-text">We design a photo border that matches your theme and shows your names, event title or date.</p>
        </div>
      </div>

      <div class="event-types">
        <span class="event-types-label">Perfect for</span>
        <ul>
          <li>💍 Weddings</li>
          <li>🎀 Debuts &amp; Birthdays</li>
          <li>🏢 Company Parties</li>
          <li>🎓 School Balls &amp; Proms</li>
          <li>🚀 Product Launches</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Packages -->
  <section class="section" id="packages">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">Packages</span>
        <h2 class="section-title">Choose the package that fits your event</h2>
        <p class="section-desc">
          Prices are for the whole event. Reserve any package with a ₱2,000 down payment and pay the balance on the event day.
        </p>
      </div>

      <div class="packages-grid">
        <?php foreach ($packages as $i => $pkg):
          $inclusions = json_decode($pkg['inclusions_json'], true) ?: [];
          $bookUrl = 'booking.php?package=' . urlencode($pkg['slug']);
        ?>
          <article class="package-card <?= $pkg['is_popular'] ? 'popular' : '' ?>" data-package-id="<?= htmlspecialchars($pkg['slug']) ?>">
            <?php if ($pkg['is_popular']): ?><span class="popular-badge">Most popular</span><?php endif; ?>
            <div class="card-slider">
              <img class="package-photo" src="assets/images/mib-cover.jpg" alt="" style="transform-origin: <?= $packagePhotoPositions[$i % 3] ?>;">
            </div>

            <div class="package-card-body">
              <div class="package-tag"><?= htmlspecialchars($pkg['tag']) ?></div>
              <h3 class="package-name"><?= htmlspecialchars($pkg['name']) ?></h3>
              <div class="package-price">₱<?= number_format($pkg['base_price']) ?></div>
              <ul class="package-chips">
                <li><?= (int) $pkg['duration_hours'] ?> hours of coverage</li>
                <li><?= (int) $pkg['photographers'] ?> photographer<?= $pkg['photographers'] > 1 ? 's' : '' ?></li>
                <li><?= (int) $pkg['operators'] ?> print station<?= $pkg['operators'] > 1 ? 's' : '' ?></li>
              </ul>
              <p class="package-desc"><?= htmlspecialchars($pkg['description']) ?></p>

              <ul class="package-features">
                <?php foreach ($inclusions as $inc): ?>
                  <li><?= htmlspecialchars($inc) ?></li>
                <?php endforeach; ?>
              </ul>

              <a href="<?= $bookUrl ?>" class="btn <?= $pkg['is_popular'] ? 'btn-accent' : 'btn-outline' ?> package-cta">
                Book this package
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="packages-top-banner">
        <div class="packages-top-text">
          <h3>Need something different?</h3>
          <p>Build your own package: choose from 2 to 8 hours, up to 4 photographers and up to 3 print stations.</p>
        </div>
        <a href="booking.php?package=custom" class="btn btn-accent btn-lg">Build My Own Package &rarr;</a>
      </div>
    </div>
  </section>

  <!-- Reviews -->
  <?php if (!empty($reviews)): ?>
  <section class="section section-alt">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">Reviews</span>
        <h2 class="section-title">What our clients say</h2>
        <p class="section-desc">Reviews from clients who booked us for their events.</p>
      </div>

      <div class="reviews-grid">
        <?php foreach ($reviews as $rev): ?>
          <figure class="review-card">
            <div class="star-rating" aria-label="Rated <?= (int) $rev['rating'] ?> out of 5">
              <?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?>
            </div>
            <blockquote>“<?= htmlspecialchars($rev['feedback_text']) ?>”</blockquote>
            <figcaption>
              <strong><?= htmlspecialchars($rev['client_name']) ?></strong>
              <span><?= htmlspecialchars($rev['event_type']) ?> &bull; <?= htmlspecialchars($rev['venue_name']) ?></span>
            </figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- FAQ -->
  <section class="section" id="faq">
    <div class="container">
      <div class="section-header">
        <span class="section-tag">Questions</span>
        <h2 class="section-title">Frequently asked questions</h2>
      </div>

      <div class="faq-list">
        <details class="faq-item">
          <summary>What is roaming photography?</summary>
          <p>Instead of guests lining up at a photo booth, our photographers walk around the venue and take photos of guests at their tables and on the dance floor. The photos go straight to our print station, and the prints are handed to your guests.</p>
        </details>
        <details class="faq-item">
          <summary>How much do I pay to reserve a date?</summary>
          <p>A fixed ₱2,000 down payment reserves your date, whichever package you choose. You pay the remaining balance at the venue on the event day.</p>
        </details>
        <details class="faq-item">
          <summary>How do I pay the down payment?</summary>
          <p>Send ₱2,000 through GCash using the number shown on the booking form, then enter your GCash reference number in the form. We'll check the payment and confirm your booking.</p>
        </details>
        <details class="faq-item">
          <summary>Can I change the number of hours or photographers?</summary>
          <p>Yes. Choose "Build My Own Package" on the booking form to pick from 2 to 8 hours, 1 to 4 photographers and 1 to 3 print stations, plus extras. The price updates as you choose.</p>
        </details>
        <details class="faq-item">
          <summary>Where do you cover events?</summary>
          <p>We cover events across Metro Manila and nearby provinces. If your venue is farther away, contact us at hello@mibphotoman.com or 0917-888-MIB1 before booking.</p>
        </details>
        <details class="faq-item">
          <summary>How do I check my booking after I submit it?</summary>
          <p>You'll get a booking reference number right after you submit the form. Sign in, or create an account with the same email you booked with, to see your booking status and assigned photographers, and to leave a review after the event.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="section cta-section">
    <div class="container">
      <div class="cta-card">
        <h2>Ready to book your date?</h2>
        <p>Reserve your date with a <strong>₱2,000 down payment</strong> and pay the rest on the event day.</p>
        <div class="cta-buttons">
          <a href="booking.php" class="btn btn-accent btn-lg">Book Your Event</a>
          <a href="packages.php" class="btn btn-outline btn-lg">Compare Packages</a>
        </div>
      </div>
    </div>
  </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
