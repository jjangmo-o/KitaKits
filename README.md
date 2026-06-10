# KitaKits

KitaKits is a PHP and MySQL web application for listing free cataract surgery missions, helping patients book available slots, and giving administrators a simple dashboard for managing missions and viewing bookings.

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

## Demo Admin Login

The homepage shows an **Admin Dashboard** button for project checking. The dashboard is now gated.

```text
Email: admin@kitakits.local
Password: admin123
```

This is only for local demo use. Replace the seeded admin password hash before production use.

## Demo Patient Login

The patient dashboard is available from **Patient Portal** on the homepage.

```text
Contact: 09111111111
Password: patient123
```

Other seeded patient contacts also use `patient123` after importing the latest SQL. The active patient flow is now opening page -> login/sign-up -> Patient Portal.


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

The root `index.php` redirects to `public/index.php` so the app still opens from the project root in XAMPP. Main patient pages live in `public/pages/`, admin pages live in `public/admin/`, API endpoints live in `public/api/`, and the database connection is kept outside the public folder in `app/config/db.php`.

## Database

The project uses MySQL through XAMPP. The database file is:

```text
database/kitakits_db.sql
```

This SQL file should be imported into phpMyAdmin so the app can access the required tables and rows.

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

## More Notes to take po 

- The project should be run through XAMPP, not by opening the PHP files directly.
- The `database/kitakits_db.sql` file must be imported before testing database-related features.
- If you already imported an older SQL version, re-import the updated `database/kitakits_db.sql` so the new tables, views, triggers, and seed statuses match the app.
- Bookings made in the app are saved in MySQL.
- New bookings are saved as `booked` requests first. Admin confirmation changes them to `confirmed`, and the SQL triggers update slot counts and status history.
- Printable slips appear only for `confirmed` bookings. The slip contains the `KK-YYYY-XXXXX` reference number, mission name/date/address, patient name/contact, booking status, headcount, and day-of instructions.
- Patient Portal, pre-screening, and printable slips all read from the same `patients`, `bookings`, `medical_intake_forms`, and `missions` records.
- After importing `kitakits_db.sql`, you can cross-check the database rows po through phpMyAdmin or the admin dashboard :>
- The visible admin dashboard button is for demonstration only po and rest assured that it should be secured further in a real deployment.
