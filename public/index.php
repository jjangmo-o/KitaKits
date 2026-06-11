<?php
require_once(__DIR__ . '/../app/config/db.php');
require_once(__DIR__ . '/api/_auth.php');
require_once(__DIR__ . '/includes/layout.php');

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
    <title>KitaKits | Free Cataract Surgery Missions</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="entry-body make-ui">
    <?php kk_render_header(['section' => 'root', 'active' => 'home']); ?>

    <main class="entry-shell">
        <!-- Hero Section -->
        <section class="entry-hero" aria-label="KitaKits cataract surgery missions">
            <div class="entry-copy">
                <span class="eyebrow">Free cataract surgery missions</span>
                <h1>Restore your sight.<br><span>No cost. No barriers.</span></h1>
                <p>KitaKits connects patients across the Philippines with free cataract surgery missions. Find a mission near you, book your slot, complete pre-screening, and regain your vision.</p>
                <div class="entry-hero-actions">
                    <a href="#missions" class="entry-primary-action">Browse Open Missions</a>
                    <a href="pages/about_cataracts.php" class="entry-secondary-action">Learn About Cataracts</a>
                </div>
            </div>
        </section>

        <!-- Trust Indicators -->
        <section class="entry-assurance" aria-label="Why KitaKits">
            <div>
                <span class="assurance-icon assurance-success">✓</span>
                <strong>500+</strong>
                <b>Successful Surgeries</b>
                <span>Patients who regained their sight through our missions</span>
            </div>
            <div>
                <span class="assurance-icon assurance-warning">◷</span>
                <strong>20-30 min</strong>
                <b>Surgery Duration</b>
                <span>Quick, safe outpatient procedure under local anesthesia</span>
            </div>
            <div>
                <span class="assurance-icon assurance-info">♙</span>
                <strong>15+</strong>
                <b>Partner Organizers</b>
                <span>Certified ophthalmologists and medical institutions</span>
            </div>
        </section>

        <!-- Patient Resources Section -->
        <section class="entry-resources" aria-label="Patient resources">
            <div class="section-header">
                <div>
                    <h2>Patient Resources</h2>
                    <p style="margin-top: 8px; color: var(--ink-muted);">Everything you need before, during, and after your surgery</p>
                </div>
            </div>
            <div class="resource-grid">
                <a href="pages/my_bookings.php" class="resource-card">
                    <img src="assets/icons/bookings.png" alt="">
                    <strong>My Bookings</strong>
                    <span>Retrieve and manage your booked surgery slots</span>
                </a>
                <a href="pages/patient_guide.php" class="resource-card">
                    <img src="assets/icons/guide.png" alt="">
                    <strong>Patient Guide</strong>
                    <span>Before, during, and after surgery — what to expect</span>
                </a>
                <a href="pages/faq.php" class="resource-card">
                    <img src="assets/icons/faq.png" alt="">
                    <strong>FAQ</strong>
                    <span>Answers to the most common patient questions</span>
                </a>
                <a href="pages/about_cataracts.php" class="resource-card">
                    <img src="assets/icons/about.png" alt="">
                    <strong>About Cataracts</strong>
                    <span>Learn about cataracts, symptoms, and treatment</span>
                </a>
            </div>
        </section>

        <section id="missions" class="missions-browser" aria-label="Surgery missions">
            <div class="section-header">
                <div>
                    <h2>Surgery Missions</h2>
                    <p>Find available missions, compare slots, and reserve your surgery schedule.</p>
                </div>
            </div>

            <form id="missionFilters" class="mission-filters" data-api-url="api/missions.php" data-page-prefix="pages/" data-asset-prefix="assets/">
                <input type="search" name="q" placeholder="Search by organizer, location, or date...">
                <select name="status" aria-label="Mission status">
                    <option value="available">Open</option>
                    <option value="all">All</option>
                    <option value="full">Full</option>
                </select>
                <select name="sort" aria-label="Sort missions">
                    <option value="date">Date</option>
                    <option value="slots">Slots</option>
                </select>
                <button type="submit">Search</button>
            </form>

            <div id="missionsStatus" class="status-message" role="status" hidden></div>
            <div id="missionsLoading" class="loading-state" role="status" aria-live="polite" hidden></div>
            <div id="missionsEmptyState" class="empty-state" hidden>
                <strong>No missions found</strong>
                <span>Try adjusting your search or filter.</span>
            </div>

            <div id="availableMissions" class="mission-grid"></div>

            <section id="fullyBookedSection" class="missions-browser-secondary" hidden>
                <div class="section-header">
                    <div>
                        <h2>Fully Booked Missions</h2>
                        <p>These missions are no longer accepting new booking requests.</p>
                    </div>
                </div>
                <div id="fullyBookedMissions" class="mission-grid"></div>
            </section>
        </section>
    </main>
    <?php kk_render_footer('root'); ?>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/missions.js"></script>
</body>
</html>
