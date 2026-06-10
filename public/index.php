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
<body class="entry-body">
    <header class="entry-header">
        <div class="entry-brand">
            <img src="assets/images/logo.png" alt="KitaKits Logo">
            <div>
                <strong>KitaKits</strong>
                <span>Cataract mission patient portal</span>
            </div>
        </div>
        <a href="admin/admin_dashboard.php" class="entry-admin-link">Admin Login</a>
    </header>

    <main class="entry-shell">
        <section class="entry-hero" aria-label="KitaKits patient access">
            <div class="entry-copy">
                <span class="eyebrow">Patient access first</span>
                <h1>Manage your cataract mission journey in one portal.</h1>
                <p>Log in to find missions, submit booking requests, complete pre-screening, track confirmation, and print your mission-day slip.</p>
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
            <a href="pages/login.php" class="entry-primary-action">Log In</a>
            <div class="entry-divider">
                <span>New patient?</span>
            </div>
            <a href="pages/register.php" class="entry-secondary-action">Sign Up</a>
            <p>Bookings are created inside the Patient Portal after login.</p>
        </section>

        <section class="entry-assurance" aria-label="Portal capabilities">
            <div>
                <strong>Booked vs confirmed</strong>
                <span>Know if your request is only received or already secured.</span>
            </div>
            <div>
                <strong>Pre-screening included</strong>
                <span>Disclose health information before mission day.</span>
            </div>
            <div>
                <strong>Printable reference slip</strong>
                <span>Confirmed bookings generate a coordinator-friendly reference.</span>
            </div>
        </section>
    </main>
</body>
</html>
