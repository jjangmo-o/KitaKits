<?php
require_once(__DIR__ . '/../app/config/db.php');
require_once(__DIR__ . '/api/_auth.php');

if (current_patient_id()) {
    header('Location: pages/patient_portal.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KitaKits | Patient Access</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="entry-body make-ui">
    <header class="entry-header">
        <div class="entry-brand">
            <img src="assets/images/logo.png" alt="KitaKits Logo">
            <div>
                <strong>KitaKits</strong>
                <span>Free cataract surgery missions</span>
            </div>
        </div>
        <nav class="entry-nav" aria-label="Patient resources">
            <a href="pages/patient_guide.php">Patient Guide</a>
            <a href="pages/faq.php">FAQ</a>
            <a href="pages/about_cataracts.php">About Cataracts</a>
        </nav>
        <a href="admin/admin_dashboard.php" class="entry-admin-link">Admin</a>
    </header>

    <main class="entry-shell">
        <section class="entry-hero" aria-label="KitaKits patient access">
            <div class="entry-copy">
                <span class="eyebrow">Free cataract surgery missions</span>
                <h1>Restore your sight. No cost. No barriers.</h1>
                <p>KitaKits connects patients across the Philippines with outreach programs. Log in to find a mission, book your slot, complete pre-screening, and print your confirmation slip.</p>
                <div class="entry-hero-actions">
                    <a href="pages/login.php" class="entry-primary-action">Log In</a>
                    <a href="pages/register.php" class="entry-secondary-action">Register</a>
                </div>
            </div>

            <div class="entry-visual" aria-hidden="true">
                <div class="entry-visual-top">
                    <img src="assets/images/logo.png" alt="">
                    <span>KK-2026-00002</span>
                </div>
                <div class="journey-line">
                    <span class="journey-dot active"></span>
                    <span class="journey-dot"></span>
                    <span class="journey-dot"></span>
                    <span class="journey-dot"></span>
                </div>
                <div class="journey-cards">
                    <div class="journey-card active">
                        <img src="assets/icons/book.png" alt="">
                        <span>Find Mission</span>
                    </div>
                    <div class="journey-card">
                        <img src="assets/icons/bookings.png" alt="">
                        <span>Book Slot</span>
                    </div>
                    <div class="journey-card">
                        <img src="assets/icons/guide.png" alt="">
                        <span>Pre-screen</span>
                    </div>
                    <div class="journey-card">
                        <img src="assets/icons/faq.png" alt="">
                        <span>Print Slip</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="entry-actions-panel" aria-label="Patient login and signup">
            <span class="eyebrow">Start here</span>
            <h2>Patient access</h2>
            <p>Bookings are created inside the Patient Portal after login.</p>
            <div class="entry-panel-actions">
                <a href="pages/login.php" class="entry-primary-action">Log In</a>
                <a href="pages/register.php" class="entry-secondary-action">Sign Up</a>
            </div>
        </section>

        <section class="entry-assurance" aria-label="Portal capabilities">
            <div>
                <strong>500+</strong>
                <span>Successful surgery journeys supported by missions.</span>
            </div>
            <div>
                <strong>20-30 min</strong>
                <span>Typical outpatient surgery duration under local anesthesia.</span>
            </div>
            <div>
                <strong>15+</strong>
                <span>Partner organizers and local medical teams.</span>
            </div>
        </section>

        <section class="entry-resources" aria-label="Patient resources">
            <div class="section-header">
                <div>
                    <span class="eyebrow">Patient resources</span>
                    <h2>Everything you need before mission day</h2>
                </div>
            </div>
            <div class="resource-grid">
                <a href="pages/patient_guide.php" class="resource-card">
                    <img src="assets/icons/guide.png" alt="">
                    <strong>Patient Guide</strong>
                    <span>Before, during, and after surgery.</span>
                </a>
                <a href="pages/faq.php" class="resource-card">
                    <img src="assets/icons/faq.png" alt="">
                    <strong>FAQ</strong>
                    <span>Answers to common patient questions.</span>
                </a>
                <a href="pages/about_cataracts.php" class="resource-card">
                    <img src="assets/icons/about.png" alt="">
                    <strong>About Cataracts</strong>
                    <span>Symptoms, treatment, and recovery basics.</span>
                </a>
                <a href="pages/login.php" class="resource-card">
                    <img src="assets/icons/bookings.png" alt="">
                    <strong>My Bookings</strong>
                    <span>Track status and print confirmed slips.</span>
                </a>
            </div>
        </section>
    </main>
</body>
</html>
