<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KitaKits</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>KitaKits</h1>
                    <p>Find free cataract surgery missions near you</p>
                </div>
                <div class="header-actions" aria-label="Primary navigation">
                    <nav class="header-nav">
                        <a href="index.php">Home (Missions)</a>
                        <a href="pages/my_bookings.php">My Bookings</a>
                        <a href="pages/patient_guide.php">Patient Guide</a>
                        <a href="pages/faq.php">FAQ</a>
                        <a href="pages/about_cataracts.php">About Cataracts</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="homepage-actions">
            <a href="admin/admin_dashboard.php" class="btn-admin">
                <img src="assets/icons/settings.png" alt="" class="btn-icon">
                Admin Dashboard
            </a>
            <a href="pages/about_cataracts.php" class="btn-secondary">Read Verified Cataract Info</a>
        </div>

        <div class="trust-strip" aria-label="Cataract information summary">
            <div class="trust-item">
                <strong>Surgery is the treatment</strong>
                <span>NEI states surgery is the treatment that removes cataracts when daily life is affected.</span>
            </div>
            <div class="trust-item">
                <strong>Symptoms can be gradual</strong>
                <span>Blur, faded colors, glare, halos, and night vision trouble can develop over time.</span>
            </div>
            <div class="trust-item">
                <strong>Access matters</strong>
                <span>WHO identifies cataracts as a leading cause of vision impairment and blindness.</span>
            </div>
        </div>

        <div class="patient-resources">
            <h2 class="centered-section-title">Patient Resources</h2>
            <div class="resource-links">
                <a href="pages/my_bookings.php" class="resource-btn">
                    <img src="assets/icons/bookings.png" alt="" class="btn-icon">
                    <span>My Bookings</span>
                </a>
                <a href="pages/patient_guide.php" class="resource-btn">
                    <img src="assets/icons/guide.png" alt="" class="btn-icon">
                    <span>Patient Guide</span>
                </a>
                <a href="pages/faq.php" class="resource-btn">
                    <img src="assets/icons/faq.png" alt="" class="btn-icon">
                    <span>FAQ</span>
                </a>
                <a href="pages/about_cataracts.php" class="resource-btn">
                    <img src="assets/icons/about.png" alt="" class="btn-icon">
                    <span>About Cataracts</span>
                </a>
            </div>
        </div>

        <h2>Available Cataract Surgery Missions</h2>
        <div id="missionsStatus" class="status-message" role="status" hidden></div>
        <div id="missionsLoading" class="loading-state" role="status" aria-live="polite" hidden></div>
        <p id="missionsEmptyState" class="empty-msg" hidden>No missions available right now. Please check back soon! We're always adding new missions to help more patients.</p>

        <div id="availableMissions" class="mission-grid" aria-live="polite"></div>

        <section id="fullyBookedSection" class="fully-booked-section" hidden>
            <h2>Fully Booked Missions</h2>
            <div id="fullyBookedMissions" class="mission-grid" aria-live="polite"></div>
        </section>
    </main>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/missions.js"></script>
</body>
</html>
