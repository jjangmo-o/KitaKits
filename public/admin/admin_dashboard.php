<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

require_admin_page();
$admin = current_admin_user();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-body admin-dashboard-page">
    <?php kk_render_header(['section' => 'admin', 'active' => 'admin', 'mode' => 'admin']); ?>

    <main class="container admin-workspace">
        <div id="adminStatus" class="status-message" role="status" hidden></div>
        <div id="adminLoading" class="loading-state" role="status" aria-live="polite" hidden></div>

        <section id="admin-missions" class="admin-primary-page">
            <div class="admin-dashboard-hero">
                <div class="admin-dashboard-hero-copy">
                    <span class="admin-dashboard-icon" aria-hidden="true">
                        <img src="../assets/icons/layout-grid.svg" alt="">
                    </span>
                    <div>
                        <span class="admin-dashboard-eyebrow">Operations overview</span>
                        <h1>Mission Dashboard</h1>
                        <p>Manage outreach missions, patient bookings, and published content from one place.</p>
                    </div>
                </div>
                <a href="add_mission.php" class="btn-primary admin-add-mission">
                    <img src="../assets/icons/plus.svg" alt="" aria-hidden="true">
                    <span>Add Mission</span>
                </a>
            </div>

            <div class="admin-page-header admin-section-heading">
                <div>
                    <span class="admin-section-kicker">At a glance</span>
                    <h2>Mission overview</h2>
                    <p><span id="adminMissionTotal">0</span> total missions in the system</p>
                </div>
            </div>

            <div id="analyticsCards" class="analytics-grid admin-summary-grid"></div>

            <p id="adminMissionsEmpty" class="empty-msg" hidden>No missions yet. <a href="add_mission.php">Create one now</a>.</p>

            <div class="table-card">
                <div class="table-card-header admin-card-heading">
                    <div>
                        <img src="../assets/icons/calendar-purple.svg" alt="" aria-hidden="true">
                        <div>
                            <strong>All Missions</strong>
                            <span>Current schedules, capacity, and availability</span>
                        </div>
                    </div>
                </div>
                <div class="admin-table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Organizer</th>
                                    <th>Date &amp; Time</th>
                                    <th>Location</th>
                                    <th>Slots</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="adminMissionsBody"></tbody>
                        </table>
                </div>
            </div>
        </section>

        <section id="admin-bookings" class="workspace-section admin-secondary-section">
            <div class="dashboard-header admin-card-heading">
                <div>
                    <img src="../assets/icons/book-open.svg" alt="" aria-hidden="true">
                    <div>
                        <h2>Bookings</h2>
                        <span>Review patient reservations and update attendance status</span>
                    </div>
                </div>
            </div>

            <form id="adminBookingFilters" class="filter-bar admin-booking-filters">
                <div class="form-group">
                    <label for="bookingMissionFilter">Mission</label>
                    <select id="bookingMissionFilter" name="mission_id">
                        <option value="">All missions</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bookingStatusFilter">Status</label>
                    <select id="bookingStatusFilter" name="status">
                        <option value="">All statuses</option>
                        <option value="booked">Booked</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                        <option value="no_show">No-show</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dateFromFilter">From</label>
                    <input type="date" id="dateFromFilter" name="date_from">
                </div>
                <div class="form-group">
                    <label for="dateToFilter">To</label>
                    <input type="date" id="dateToFilter" name="date_to">
                </div>
                <button type="submit" class="btn-primary admin-filter-button">
                    <img src="../assets/icons/filter.svg" alt="" aria-hidden="true">
                    <span>Apply filters</span>
                </button>
            </form>

            <p id="adminBookingsEmpty" class="empty-msg" hidden>No bookings found.</p>

            <div class="table-wrap admin-table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Mission</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Headcount</th>
                            <th>Intake Review</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminBookingsBody"></tbody>
                </table>
            </div>
        </section>

        <section id="admin-patients" class="workspace-section">
            <div class="dashboard-header admin-card-heading">
                <div>
                    <img src="../assets/icons/users-purple.svg" alt="" aria-hidden="true">
                    <div>
                        <h2>Patients</h2>
                        <span>Registered patients and their booking activity</span>
                    </div>
                </div>
            </div>
                    
            <p id="adminPatientsEmpty" class="empty-msg" hidden>No patients yet.</p>
                    
            <div class="table-wrap admin-table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>City</th>
                            <th>Bookings</th>
                        </tr>
                    </thead>
                    <tbody id="adminPatientsBody"></tbody>
                </table>
            </div>
        </section>

        <section id="admin-content" class="workspace-section">
            <div class="dashboard-header admin-card-heading">
                <div>
                    <img src="../assets/icons/file-text.svg" alt="" aria-hidden="true">
                    <div>
                        <h2>Pages &amp; Content</h2>
                        <span>Maintain the patient-facing information shown across KitaKits</span>
                    </div>
                </div>
            </div>
            <div id="contentPages" class="content-editor-list"></div>
        </section>
    </main>

    <?php kk_render_footer('admin'); ?>

    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>
