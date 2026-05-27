# KitaKits

KitaKits is a PHP and MySQL web application for listing free cataract surgery missions, helping patients book available slots, and giving administrators a simple dashboard for managing missions and viewing bookings.

## What This Project Does

The project has two main sides:

1. **User / Patient Side**
   - Patients can view available cataract surgery missions on the homepage.
   - Patients can check mission details, including date, location, organizer, and available slots.
   - Patients can book a slot by entering their name and contact number.
   - Patients can search their bookings using their contact number.
   - Patients can read supporting pages such as:
     - Patient Guide
     - FAQ
     - About Cataracts

2. **Admin Side**
   - Admin can view all missions.
   - Admin can add new missions.
   - Admin can edit mission details.
   - Admin can delete missions.
   - Admin can view all patient bookings.

## Important Note About the Admin Button

The homepage currently shows an **Admin Dashboard** button on the user side. This is intentionally visible only for easy project navigation and demonstration during checking.


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
- Bookings made in the app are saved in MySQL.
- After importing `kitakits_db.sql`, you can cross-check the database rows po through phpMyAdmin or the admin dashboard :>
- The visible admin dashboard button is for demonstration only po and rest assured that it will be secured in a real deployment. purely for demo purposes only po.
