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

<body class="admin-body">
    <?php kk_render_header(['section' => 'admin', 'active' => 'admin', 'mode' => 'admin']); ?>

    <main class="container admin-workspace">
        <div id="adminStatus" class="status-message" role="status" hidden></div>
        <div id="adminLoading" class="loading-state" role="status" aria-live="polite" hidden></div>

        <section id="admin-missions" class="admin-primary-page">
                    <div class="admin-page-header">
                        <div>
                            <h1>Missions</h1>
                            <p><span id="adminMissionTotal">0</span> total missions</p>
                        </div>
                        <a href="add_mission.php" class="btn-primary">
                            <span>+</span>
                            <span>Add Mission</span>
                        </a>
                    </div>

                    <div id="analyticsCards" class="analytics-grid admin-summary-grid"></div>

                    <p id="adminMissionsEmpty" class="empty-msg" hidden>No missions yet. <a href="add_mission.php">Create one now</a>.</p>

                    <div class="table-card">
                        <div class="table-card-header">
                            <strong>All Missions</strong>
                        </div>
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
                </section>

        <section id="admin-bookings" class="workspace-section admin-secondary-section">
                    <div class="dashboard-header">
                        <h2>Bookings</h2>
                    </div>

                    <form id="adminBookingFilters" class="filter-bar" style="margin-bottom: 24px;">
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
                        <button type="submit" class="btn-primary" style="align-self: flex-end; min-height: 44px;">Filter</button>
                    </form>

                    <p id="adminBookingsEmpty" class="empty-msg" hidden>No bookings found.</p>

                    <div class="table-wrap">
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
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="adminBookingsBody"></tbody>
                        </table>
                    </div>
                </section>

        <section id="admin-patients" class="workspace-section">
                    <div class="dashboard-header">
                        <h2>Patients</h2>
                    </div>
                    
                    <p id="adminPatientsEmpty" class="empty-msg" hidden>No patients yet.</p>
                    
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>City</th>
                                    <th>Bookings</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="adminPatientsBody"></tbody>
                        </table>
                    </div>
                </section>

        <section id="admin-content" class="workspace-section">
                    <div class="dashboard-header">
                        <h2>Pages & Content</h2>
                    </div>
                    <div id="contentPages" class="content-editor-list"></div>
                </section>
    </main>

    <?php kk_render_footer('admin'); ?>

    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>
