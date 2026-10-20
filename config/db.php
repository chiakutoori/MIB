<?php
/**
 * MIB PHOTOMAN - Database Connection & Auto-Migration
 * SQLite with PDO - Zero setup, self-initializing, production-grade schema
 */

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$dbPath = $dataDir . '/mib_photoman.sqlite';
$isFirstRun = !file_exists($dbPath);

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Auto-initialize tables and seed data on initial run or if tables are missing
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('admin', 'staff', 'client')),
        phone TEXT,
        role_title TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS packages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        tag TEXT,
        base_price REAL NOT NULL,
        duration_hours INTEGER NOT NULL,
        photographers INTEGER NOT NULL,
        operators INTEGER NOT NULL,
        description TEXT,
        inclusions_json TEXT,
        is_popular INTEGER DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_ref TEXT UNIQUE NOT NULL,
        client_id INTEGER,
        client_name TEXT NOT NULL,
        client_email TEXT NOT NULL,
        client_phone TEXT NOT NULL,
        event_type TEXT NOT NULL,
        event_date DATE NOT NULL,
        event_time TEXT NOT NULL,
        venue_name TEXT NOT NULL,
        venue_address TEXT NOT NULL,
        package_id INTEGER,
        package_name TEXT NOT NULL,
        custom_hours INTEGER DEFAULT 2,
        custom_photographers INTEGER DEFAULT 1,
        custom_operators INTEGER DEFAULT 1,
        magnet_upgraded INTEGER DEFAULT 0,
        photo_standees INTEGER DEFAULT 0,
        total_price REAL NOT NULL,
        down_payment REAL DEFAULT 2000.00,
        payment_status TEXT NOT NULL DEFAULT 'dp_submitted' CHECK(payment_status IN ('unpaid', 'dp_submitted', 'dp_confirmed', 'fully_paid')),
        booking_status TEXT NOT NULL DEFAULT 'pending' CHECK(booking_status IN ('pending', 'confirmed', 'in_progress', 'completed', 'cancelled')),
        special_requests TEXT,
        payment_reference TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS staff_assignments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_id INTEGER NOT NULL,
        staff_id INTEGER NOT NULL,
        role_in_event TEXT NOT NULL,
        assigned_by INTEGER,
        status TEXT DEFAULT 'assigned' CHECK(status IN ('assigned', 'accepted', 'declined')),
        notes TEXT,
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE(booking_id, staff_id)
    );

    CREATE TABLE IF NOT EXISTS post_event_journals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_id INTEGER NOT NULL,
        staff_id INTEGER NOT NULL,
        prints_distributed INTEGER NOT NULL DEFAULT 0,
        guest_count_estimated INTEGER DEFAULT 0,
        equipment_status TEXT NOT NULL,
        issues_encountered TEXT,
        highlights_and_notes TEXT,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS customer_reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        booking_id INTEGER NOT NULL UNIQUE,
        client_id INTEGER NOT NULL,
        rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
        photographer_rating INTEGER CHECK(photographer_rating BETWEEN 1 AND 5),
        print_quality_rating INTEGER CHECK(print_quality_rating BETWEEN 1 AND 5),
        feedback_text TEXT,
        would_recommend INTEGER DEFAULT 1,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        recipient_email TEXT NOT NULL,
        recipient_role TEXT NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        notification_type TEXT NOT NULL,
        status TEXT DEFAULT 'sent',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// Check if seeding is needed
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount == 0) {
    // 1. Seed Users
    $stmtUser = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, phone, role_title) VALUES (?, ?, ?, ?, ?, ?)");
    // Default password for all demo accounts: 'password123'
    $defaultPassHash = password_hash('password123', PASSWORD_DEFAULT);

    $users = [
        ['MIB Head Administrator', 'admin@mibphotoman.com', $defaultPassHash, 'admin', '0917-888-MIB1', 'Managing Director & Scheduler'],
        ['Marcus Vance', 'marcus@mibphotoman.com', $defaultPassHash, 'staff', '0917-555-0101', 'Lead Roaming Photographer'],
        ['Isabella Torres', 'isabella@mibphotoman.com', $defaultPassHash, 'staff', '0917-555-0102', 'High-Speed Dye-Sub Operator'],
        ['Carlo Rossi', 'carlo@mibphotoman.com', $defaultPassHash, 'staff', '0917-555-0103', 'Second Shooter & Lighting Tech'],
        ['Juan Dela Cruz', 'client@gmail.com', $defaultPassHash, 'client', '0922-333-4455', 'Wedding Host / Client']
    ];

    foreach ($users as $u) {
        $stmtUser->execute($u);
    }

    // 2. Seed Packages
    $stmtPkg = $pdo->prepare("INSERT INTO packages (name, slug, tag, base_price, duration_hours, photographers, operators, description, inclusions_json, is_popular) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $packages = [
        [
            'The Solo Agent',
            'solo',
            'Intimate Gatherings',
            7500.00,
            2,
            1,
            1,
            'Perfect for intimate birthdays, product launches, and private banquets needing premium roving photo coverage.',
            json_encode([
                '2 Hours of Unlimited 4R High-Gloss Prints',
                '1 Suited Roaming Photographer (Sony A7IV / Profoto)',
                '1 Dedicated Onsite Dye-Sublimation Operator',
                'Custom Single-Photo Layout Border & Branding',
                'Full Digital High-Res Album within 24 Hours',
                'Magnetic Backing on All Distributed Prints'
            ]),
            0
        ],
        [
            'The Executive Squad',
            'squad',
            'Most Popular Choice',
            12500.00,
            3,
            2,
            1,
            'The signature MIB Photoman experience for grand weddings, 18th debuts, and landmark celebrations.',
            json_encode([
                '3 Hours of Rapid Unlimited 4R Magnetic Prints',
                '2 Suited Roaming Photographers (Multi-Angle Coverage)',
                '1 Onsite Dye-Sublimation Station & Operator',
                'Curated Multi-Photo Custom Overlay / Frame Design',
                'Real-Time Live Cloud Gallery with QR Download',
                'Complimentary Acrylic Desk Standees for VIP Tables'
            ]),
            1
        ],
        [
            'The Syndicate Suite',
            'syndicate',
            'Grand Celebrations & Galas',
            18500.00,
            4,
            2,
            2,
            'Maximum bandwidth production designed for large-scale corporate galas, conventions, and celebrity receptions.',
            json_encode([
                '4 Hours of Continuous Zero-Wait High-Yield Prints',
                '2 Senior Suited Roaming Photographers',
                '2 High-Speed DNP Dye-Sublimation Printing Stations',
                'Dual Magnetic 4R + Customized 2x6 Photo Strip Formats',
                'Same-Night Highlight Reel & Instant Cloud Sync',
                'Personalized Velvet Keepsake Photo Album'
            ]),
            0
        ]
    ];

    foreach ($packages as $pkg) {
        $stmtPkg->execute($pkg);
    }

    // 3. Seed Realistic Bookings
    $stmtBooking = $pdo->prepare("INSERT INTO bookings (booking_ref, client_id, client_name, client_email, client_phone, event_type, event_date, event_time, venue_name, venue_address, package_id, package_name, custom_hours, custom_photographers, custom_operators, magnet_upgraded, photo_standees, total_price, down_payment, payment_status, booking_status, special_requests, payment_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Booking 1: Completed Wedding with Journal and Review
    $stmtBooking->execute([
        'MIB-2026-0891',
        5,
        'Juan Dela Cruz & Maria Santos',
        'client@gmail.com',
        '0922-333-4455',
        'Wedding Ceremony & Reception',
        date('Y-m-d', strtotime('-5 days')),
        '16:00:00',
        'The Manila Hotel - Grand Centennial Ballroom',
        'One Rizal Park, Ermita, Manila, 0913 Metro Manila',
        2,
        'The Executive Squad',
        3,
        2,
        1,
        1,
        1,
        12500.00,
        2000.00,
        'fully_paid',
        'completed',
        'Please prioritize candid table shots during speeches and VIP family portraits.',
        'GCASH-REF-8902148'
    ]);
    $b1Id = $pdo->lastInsertId();

    // Assign staff to Booking 1
    $pdo->exec("INSERT INTO staff_assignments (booking_id, staff_id, role_in_event, assigned_by, status, notes) VALUES 
        ($b1Id, 2, 'Lead Roaming Photographer', 1, 'accepted', 'Lead roaming shooter. Sony A7IV + 35mm f/1.4 GM.'),
        ($b1Id, 3, 'Printer Operator', 1, 'accepted', 'Operate DNP DS620A printer. Ensure magnetic strips pre-cut.')
    ");

    // Post-Event Journal for Booking 1
    $pdo->exec("INSERT INTO post_event_journals (booking_id, staff_id, prints_distributed, guest_count_estimated, equipment_status, issues_encountered, highlights_and_notes) VALUES 
        ($b1Id, 2, 420, 260, 'All gear functioning optimally. Minor power surge handled by UPS.', 'High guest volume at cocktail hour caused brief print queue, resolved swiftly.', 'Bride and groom specifically praised the crisp black suit attire of the roaming crew and instant magnetic hand-outs.')
    ");

    // Customer Review for Booking 1
    $pdo->exec("INSERT INTO customer_reviews (booking_id, client_id, rating, photographer_rating, print_quality_rating, feedback_text, would_recommend) VALUES 
        ($b1Id, 5, 5, 5, 5, 'Superb service by MIB Photoman! Our wedding guests were absolutely thrilled receiving instant magnetic prints at their tables. Marcus and Isabella were so polite, professional, and looked razor-sharp in their suits!', 1)
    ");

    // Booking 2: Upcoming Confirmed Event (Next week)
    $stmtBooking->execute([
        'MIB-2026-0942',
        5,
        'Samantha Reyes (18th Debut)',
        'samantha.reyes@gmail.com',
        '0918-777-2233',
        '18th Debut Celebration',
        date('Y-m-d', strtotime('+4 days')),
        '18:00:00',
        'Oasis Manila - Pavilion Hall',
        '169 Aurora Blvd, San Juan, Metro Manila',
        2,
        'The Executive Squad',
        3,
        2,
        1,
        1,
        1,
        12500.00,
        2000.00,
        'dp_confirmed',
        'confirmed',
        'Debutante color theme is Emerald Green & Silver. Custom frame borders requested.',
        'GCASH-REF-9182371'
    ]);
    $b2Id = $pdo->lastInsertId();

    // Assign Marcus and Isabella to Booking 2
    $pdo->exec("INSERT INTO staff_assignments (booking_id, staff_id, role_in_event, assigned_by, status, notes) VALUES 
        ($b2Id, 2, 'Lead Roaming Photographer', 1, 'accepted', 'Direct call time 17:00 at Pavilion Hall.'),
        ($b2Id, 3, 'Printer Operator', 1, 'accepted', 'Station setup next to entrance photo wall.')
    ");

    // Booking 3: Pending DP Verification (Needs Admin Approval)
    $stmtBooking->execute([
        'MIB-2026-0975',
        NULL,
        'TechNova Corp Gala Night (c/o Engr. Roberto Tan)',
        'roberto.tan@technova.com.ph',
        '0920-999-4411',
        'Corporate Annual Gala',
        date('Y-m-d', strtotime('+12 days')),
        '19:00:00',
        'Shangri-La The Fort - Bonifacio Hall',
        '30th Street, Bonifacio Global City, Taguig',
        3,
        'The Syndicate Suite',
        4,
        2,
        2,
        1,
        1,
        18500.00,
        2000.00,
        'dp_submitted',
        'pending',
        'Requires two printing stations on opposite sides of the ballroom. Official BIR OR requested.',
        'BDO-REF-4491029'
    ]);

    // 4. Seed Notification Logs
    $pdo->exec("INSERT INTO notifications (recipient_email, recipient_role, title, message, notification_type) VALUES 
        ('client@gmail.com', 'client', 'Booking Confirmation - MIB-2026-0891', 'Your wedding reservation at The Manila Hotel has been received. Reference: MIB-2026-0891. Fixed Down Payment of ₱2,000 recorded.', 'booking_received'),
        ('client@gmail.com', 'client', 'Fixed ₱2,000 Down Payment Verified', 'Official verification: Down payment of ₱2,000.00 for MIB-2026-0891 confirmed. Date reserved on official MIB calendar.', 'dp_verified'),
        ('marcus@mibphotoman.com', 'staff', 'Staff Assignment: Lead Photographer', 'You have been assigned as Lead Roaming Photographer for MIB-2026-0942 at Oasis Manila on ' || date('now', '+4 days') || '.', 'staff_delegation'),
        ('samantha.reyes@gmail.com', 'client', 'Event Schedule Reminder (24-Hour Notice)', 'Reminder: MIB Photoman crew call time is tomorrow at Oasis Manila. Contact: 0917-888-MIB1.', 'schedule_reminder')
    ");
}

function getDb(): PDO {
    global $pdo;
    return $pdo;
}
