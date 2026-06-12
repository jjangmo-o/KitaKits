<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

$patient = current_patient_user();
$admin_preview = !$patient && current_admin_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-my-bookings">
    <?php kk_render_header(['section' => 'pages', 'active' => 'my_bookings']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'My Bookings']]); ?>

    <main class="container workflow-page">
        <a href="../index.php" class="btn-back">Back to Missions</a>

        <section class="workflow-hero">
            <span class="workflow-hero-icon" aria-hidden="true">
                <img src="../assets/icons/book-open.svg" alt="">
            </span>
            <div>
                <h1>My Bookings</h1>
                <p>Enter the contact number you used when booking to retrieve your slots.</p>
            </div>
        </section>

        <?php if (!$patient): ?>
            <div class="preview-access-notice">
                <img src="../assets/icons/shield-check.svg" alt="" aria-hidden="true">
                <div>
                    <strong><?php echo $admin_preview ? 'Patient preview mode' : 'Preview only'; ?></strong>
                    <span>Booking records are private. Log in as a patient to retrieve and manage bookings.</span>
                </div>
                <a href="login.php" class="btn-primary compact-button">Patient Log In</a>
            </div>
        <?php endif; ?>

        <div class="make-bookings-layout">
            <aside class="make-bookings-side" aria-label="Booking lookup">
                <section class="booking-summary workflow-card">
                    <div class="workflow-card-heading">
                        <img src="../assets/icons/search.svg" alt="" aria-hidden="true">
                        <div>
                            <h2>Search Bookings</h2>
                            <p>Use the number entered during booking.</p>
                        </div>
                    </div>
                    <form id="bookingSearchForm" class="search-form" novalidate>
                        <div class="form-group">
                            <label for="contact">Contact Number</label>
                            <input
                                type="text"
                                id="contact"
                                name="contact"
                                placeholder="e.g. 09171234567"
                                pattern="[\+0-9\s\-\(\)]{7,20}"
                                autocomplete="tel"
                                value="<?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?>"
                                <?php echo $patient ? 'readonly' : 'disabled'; ?>
                                required
                            >
                        </div>
                        <button type="submit" id="bookingSearchSubmit" class="btn-primary" <?php echo $patient ? '' : 'disabled'; ?>>
                            <img src="../assets/icons/search.svg" alt="" aria-hidden="true">
                            <span>Search Bookings</span>
                        </button>
                    </form>
                    <div id="bookingSearchStatus" class="status-message" role="status" hidden></div>
                    <div id="bookingSearchLoading" class="loading-state" role="status" aria-live="polite" hidden></div>
                </section>

                <section id="bookingTips" class="booking-tips workflow-card">
                    <div class="workflow-card-heading">
                        <img src="../assets/icons/info.svg" alt="" aria-hidden="true">
                        <div>
                            <h2>Booking Tips</h2>
                            <p>Helpful reminders before your visit.</p>
                        </div>
                    </div>
                    <ul class="checklist">
                        <li>Use the exact number you used when booking</li>
                        <li>You can have multiple bookings under one number</li>
                        <li>To cancel, use the cancel button or contact the organizer</li>
                        <li>Bring your booking slip on surgery day</li>
                    </ul>
                </section>
            </aside>

            <section class="booking-results-panel" aria-label="Booking search results">
                <div id="bookingsResults">
                    <div class="make-empty-state">
                        <img src="../assets/icons/search.svg" alt="" aria-hidden="true">
                        <div>
                            <strong>Search for your bookings</strong>
                            <span>Enter your contact number on the left to get started.</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php kk_render_footer('pages'); ?>
    <?php if ($patient): ?>
        <script src="../assets/js/api.js"></script>
        <script src="../assets/js/my-bookings.js"></script>
    <?php endif; ?>
</body>
</html>
