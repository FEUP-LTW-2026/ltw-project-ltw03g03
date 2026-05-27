# ltw03g03

## Features

**All users:**
- [ ] Register a new account.
- [ ] Log in and out.
- [ ] Edit their profile, including name, username, password, and profile photo.

**Members:**
- [ ] Browse the schedule of available fitness classes, filtering by type, trainer, day, or time.
- [ ] Enroll in and cancel enrollment from upcoming classes, subject to capacity limits.
- [ ] View trainer profiles, including their specializations and the classes they teach.
- [ ] Check the current availability of equipment in the main training area.
- [ ] Leave ratings and reviews for classes they have attended.

**Trainers:**
- [ ] Manage their public profile, including bio, specializations, and certifications.
- [ ] View the roster of members enrolled in their classes.
- [ ] Track and manage their assigned class schedule.

**Admins:**
- [x] Manage members and trainers (create, update, and deactivate accounts).
- [x] Manage the class catalog (create, edit, and remove classes) and assign trainers to them.
- [x] Manage equipment in the main training area (add, update availability status, and remove items).
- [x] Elevate a user to admin status.
- [x] Oversee and ensure the smooth operation of the entire system.

**Extra:**
- [ ] Something extra (e.g., personal training bookings, membership plans, waitlist, ...).

## Running

    sqlite3 database/database.db < database/database.sql
    php -S localhost:9000

## Credentials

- admin/p4s5w0rd
- member/1234
- trainer/1234

## Pages for 1st delivery:

- html/register.html
- html/home.html
- html/classes.html
