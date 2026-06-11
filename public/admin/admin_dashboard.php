<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');

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
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>Admin Dashboard</h1>
                    <p>Manage missions, approvals, patients, and content</p>
                </div>
                <div class="header-actions">
                    <nav class="header-nav">
                        <a href="../index.php">Patient Page</a>
                        <a href="logout.php">Logout</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="container admin-workspace">
        <div class="admin-note">
            <strong>Signed in</strong>
            <span><?php echo htmlspecialchars($admin['full_name'] ?? 'Admin'); ?></span>
        </div>

        <div id="adminStatus" class="status-message" role="status" hidden></div>
        <div id="adminLoading" class="loading-state" role="status" aria-live="polite" hidden></div>

        <section class="workspace-section">
            <div class="section-header">
                <h2>Mission Analytics</h2>
            </div>
            <div id="analyticsCards" class="analytics-grid"></div>
        </section>

        <section class="workspace-section">
            <div class="dashboard-header">
                <h2>All Missions</h2>
                <a href="add_mission.php" class="btn-primary">
                    <img src="../assets/icons/add.png" alt="" class="btn-icon">
                    Add New Mission
                </a>
            </div>

            <p id="adminMissionsEmpty" class="empty-msg" hidden>No missions yet. <a href="add_mission.php">Create one now</a>.</p>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mission</th>
                            <th>Date</th>
                            <th>Area</th>
                            <th>Status</th>
                            <th>Slots</th>
                            <th>Bookings</th>
                            <th>Completed</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="adminMissionsBody"></tbody>
                </table>
            </div>
        </section>

        <section class="workspace-section">
            <div class="section-header">
                <h2>Booking Directory</h2>
            </div>

            <form id="adminBookingFilters" class="filter-bar">
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
                <button type="submit" class="btn-search">Filter</button>
            </form>

            <p id="adminBookingsEmpty" class="empty-msg" hidden>No bookings found.</p>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Mission</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Headcount</th>
                            <th>Intake</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="adminBookingsBody"></tbody>
                </table>
            </div>
        </section>

        <section class="workspace-section">
            <div class="section-header">
                <h2>Patient Directory</h2>
            </div>
            <p id="adminPatientsEmpty" class="empty-msg" hidden>No patients yet.</p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
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

        <section class="workspace-section">
            <div class="section-header">
                <h2>Info Page Content</h2>
            </div>
            <div id="contentPages" class="content-editor-list"></div>
        </section>
    </main>

    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>
