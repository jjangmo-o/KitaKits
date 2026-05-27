<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | KitaKits</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>My Bookings</h1>
                    <p>Track your cataract surgery reservations</p>
                </div>
                <div class="header-actions" aria-label="Primary navigation">
                    <nav class="header-nav">
                        <a href="index.php">Home (Missions)</a>
                        <a href="my_bookings.php">My Bookings</a>
                        <a href="patient_guide.php">Patient Guide</a>
                        <a href="faq.php">FAQ</a>
                        <a href="about_cataracts.php">About Cataracts</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <a href="index.php" class="btn-back">
            <span>&larr; </span>
            Back to Missions
        </a>

        <div class="booking-info-box">
            <h2>Find Your Bookings</h2>
            <p>Enter the contact number you used when booking to view your reservations.</p>

            <form method="GET" action="" class="search-form" id="bookingSearchForm">
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input
                        type="text"
                        id="contact"
                        name="contact"
                        placeholder="e.g., 09123456789"
                        pattern="[\+0-9\s\-\(\)]{7,20}"
                        required
                        aria-label="Enter your contact number to search bookings"
                    >
                </div>
                <button type="submit" class="btn-search" id="bookingSearchSubmit">Search My Bookings</button>
            </form>
        </div>

        <div id="bookingSearchStatus" class="status-message" role="status" hidden></div>
        <div id="bookingSearchLoading" class="loading-state" role="status" aria-live="polite" hidden></div>
        <div id="bookingsResults" class="bookings-list" aria-live="polite"></div>

        <div class="booking-tips" id="bookingTips" hidden>
            <h3>Important Tips for Your Surgery</h3>
            <ul>
                <li><strong>Confirm Your Attendance:</strong> Contact the organizer if you cannot attend</li>
                <li><strong>Required Documents:</strong> Bring your valid ID and any existing medical records</li>
                <li><strong>Arrive Early:</strong> Come 30 minutes early for registration and health screening</li>
                <li><strong>Follow Pre-Surgery Instructions:</strong> Adhere to fasting and medication guidelines provided</li>
                <li><strong>Bring Support:</strong> Have a family member or friend accompany you</li>
                <li><strong>Post-Surgery Care:</strong> Follow all aftercare instructions provided by medical staff</li>
            </ul>
        </div>
    </main>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/my-bookings.js"></script>
</body>
</html>