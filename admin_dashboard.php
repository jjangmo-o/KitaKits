<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | KitaKits</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-content">
                <h1>Admin Dashboard</h1>
                <p>Manage missions and monitor bookings</p>
            </div>
        </div>
    </header>

    <main class="container">
        <a href="index.php" class="btn-back">
            <span>&larr; </span>
            Back to Patient Page
        </a>

        <div class="admin-note">
            <strong>Admin access note</strong>
            <span>The dashboard link is currently visible for easy project navigation. It is planned by me to hide admin access from regular users for real life deployment.</span>
        </div>

        <div id="adminStatus" class="status-message" role="status" hidden></div>
        <div id="adminLoading" class="loading-state" role="status" aria-live="polite" hidden></div>

        <div class="dashboard-header">
            <h2>All Missions</h2>
            <a href="add_mission.php" class="btn-primary">
                <img src="assets/icons/add.png" alt="" class="btn-icon">
                Add New Mission
            </a>
        </div>

        <p id="adminMissionsEmpty" class="empty-msg" hidden>No missions yet. <a href="add_mission.php">Create one now</a>.</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Organizer</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Slots Left</th>
                    <th>Bookings</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="adminMissionsBody"></tbody>
        </table>

        <div class="section-header">
            <h2>All Bookings</h2>
        </div>

        <p id="adminBookingsEmpty" class="empty-msg" hidden>No bookings yet.</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Patient Name</th>
                    <th>Contact</th>
                    <th>Mission</th>
                    <th>Mission Date</th>
                </tr>
            </thead>
            <tbody id="adminBookingsBody"></tbody>
        </table>
    </main>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/admin-dashboard.js"></script>
</body>
</html>
