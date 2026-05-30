# ltw03g03

## Features

**All users:**
- [x] Register a new account.
- [x] Log in and out.
- [x] Edit their profile, including name, username, password, and profile photo.

**Members:**
- [x] Browse the schedule of available fitness classes, filtering by type, trainer, day, or time.
- [x] Enroll in and cancel enrollment from upcoming classes, subject to capacity limits.
- [x] View trainer profiles, including their specializations and the classes they teach.
- [x] Check the current availability of equipment in the main training area.
- [x] Leave ratings and reviews for classes they have attended.

**Trainers:**
- [x] Manage their public profile, including bio, specializations, and certifications.
- [x] View the roster of members enrolled in their classes.
- [x] Track and manage their assigned class schedule.

**Admins:**
- [x] Manage members and trainers (create, update, and deactivate accounts).
- [x] Manage the class catalog (create, edit, and remove classes) and assign trainers to them.
- [x] Manage equipment in the main training area (add, update availability status, and remove items).
- [x] Elevate a user to admin status.
- [x] Oversee and ensure the smooth operation of the entire system.

**Extra:**
- [x] Personal Training Bookings: Members can browse trainer availability and book one-on-one personal training sessions.
- [x] Membership Plans: Tiered membership plans (basic, pro, elite) with different access levels. Members can subscribe to or upgrade their plan.
- [x] Equipment Reservation: Members can reserve a specific piece of equipment for a time slot in the main training area.
- [x] Class Waitlist: When a class is full, members can join a waitlist and are automatically enrolled when a spot opens.
- [x] Member Progress Tracking: Members can log workouts, set fitness goals, and track progress over time.
- [x] Notification System: In-app notifications for class reminders, booking confirmations, and waitlist updates.
- [x] Trainer Analytics Dashboard: Trainers can view attendance records and class rosters with enrollment details.
- [x] Admin Analytics Dashboard: Admins can view gym-wide metrics including most popular classes, equipment usage, and member retention.
- [x] Disputes and Feedback: Members can report issues and admins can manage and respond to dispute reports.
- [x] REST API: Public API endpoints for querying class schedules, trainer profiles, and equipment availability.

## Running

    sqlite3 database/database.db < database/database.sql
    php -S localhost:9000

## Credentials

- admin/p4s5w0rd
- member/1234
- trainer/1234
