# KitaKits

KitaKits is a PHP and MySQL web application for listing free cataract surgery missions, helping patients book available slots, and giving administrators a simple dashboard for managing missions and viewing bookings. Click this for [Live Demo](https://kitakits.free.nf/)

## What This Project Does

The project has two main sides:

1. **User / Patient Side**
   - Patients start on an opening page with a clear **Log In** action and **Sign Up** below it.
   - Patients must log in or create an account before booking missions.
   - Patients can view available cataract surgery missions inside the Patient Portal.
   - Patients can search, filter, and sort missions by keyword, city/area, slot availability, date, and slot count.
   - Patients can check mission details, including date, location, organizer, and available slots.
   - Patients can submit a booking request from the Patient Portal flow with basic patient profile details and companion headcount.
   - Patients can create an account and log in to a patient dashboard.
   - Patients can manage their profile details from the patient dashboard.
   - Patients can see whether a booking is still `booked` or already `confirmed`.
   - Patients can complete a medical pre-screening intake form.
   - Patients can print a confirmation slip after admin approval from the Patient Portal.
   - Patients can read supporting pages such as:
     - Patient Guide
     - FAQ
     - About Cataracts

2. **Admin Side**
   - Admin access is gated by a demo login.
   - Admin can view all missions.
   - Admin can add new missions.
   - Admin can edit mission details.
   - Admin can delete missions.
   - Admin can view mission analytics, booking counts, confirmed headcount, and completion rate.
   - Admin can filter bookings by mission, status, and mission date range.
   - Admin can confirm, reject, cancel, complete, or mark no-show bookings.
   - Admin can review patient pre-screening flags.
   - Admin can view a patient directory linked to bookings.
   - Admin can update content/advisory pages shown on the homepage.

## Project Structure

```text
KitaKits/
├── app/
│   └── config/
│       └── db.php
├── database/
│   └── kitakits_db.sql
├── public/
│   ├── admin/
│   │   ├── admin_dashboard.php
│   │   ├── add_mission.php
│   │   └── edit_mission.php
│   ├── api/
│   │   ├── admin_dashboard.php
│   │   ├── book_slot.php
│   │   ├── bookings.php
│   │   ├── delete_booking.php
│   │   └── missions.php
│   ├── assets/
│   │   ├── css/
│   │   ├── icons/
│   │   ├── images/
│   │   └── js/
│   ├── pages/
│   │   ├── about_cataracts.php
│   │   ├── book_slot.php
│   │   ├── edit_booking.php
│   │   ├── faq.php
│   │   ├── mission_details.php
│   │   ├── my_bookings.php
│   │   └── patient_guide.php
│   └── index.php
├── index.php
└── README.md
```

## Setup Instructions

1. Copy the entire `KITAKITS` folder into XAMPP `htdocs` directory.

   ```text
   C:\xampp\htdocs\KitaKits\
   ```

2. Start **Apache** and **MySQL** in the XAMPP Control Panel.

3. Open phpMyAdmin:

   ```text
   http://localhost/phpmyadmin
   ```

4. Create a new database named:

   ```text
   kitakits_db
   ```

5. Import the database:

   - Click `kitakits_db`
   - Go to the `Import` tab
   - Click `Choose File`
   - Select `database/kitakits_db.sql`
   - Click `Go`

6. Open the project in the browser:

   ```text
   http://localhost/KitaKits/
   ```

## Database Connection

The database connection is configured in `app/config/db.php`. If the project does not connect on another computer, check that the database name, username, and password match the local XAMPP setup.

Typical XAMPP settings:

```text
Host: localhost
Database: kitakits_db
Username: root
Password: empty
```
