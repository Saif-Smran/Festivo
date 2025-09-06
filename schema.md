## Event Management System Database Schema

This document defines the core relational schema for the Festivo platform: user accounts, events, and event participation (attendance/registration). It is written for MySQL / MariaDB (InnoDB, utf8mb4).

---

### 1. Conventions
* Engine: InnoDB (FK + transactions)
* Charset: utf8mb4 / collation utf8mb4_unicode_ci
* Naming: snake_case table & column names; singular logical entities not enforced (tables plural here for clarity)
* Timestamps use CURRENT_TIMESTAMP defaults; application should prefer UTC

---

### 2. Schema Overview
Entity | Purpose | Notes
------ | ------- | -----
Users | Registered accounts (auth + ownership) | Password stored as Argon2 / bcrypt hash
Events | Events created by users | Creator (owner) FK to Users
EventParticipants | Many‑to‑many (user ↔ event) | Enforces uniqueness (one join per user/event)

Potential future tables (not implemented here): Categories, Tickets, Venues, Media, Reviews, Notifications.

---

### 3. Table Definitions

```sql
-- Ensure database (optional)
-- CREATE DATABASE festivo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE festivo;

-- USERS
CREATE TABLE users (
    user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    display_name  VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Argon2id / bcrypt
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    avatar_url    VARCHAR(255) NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EVENTS
CREATE TABLE events (
    event_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,           -- creator (owner)
    title        VARCHAR(150) NOT NULL,
    slug         VARCHAR(180) NOT NULL UNIQUE,    -- for SEO friendly URL
    summary      VARCHAR(255) NULL,
    description  TEXT NULL,
    category     ENUM('conference','wedding','concert','birthday','workshop','festival','other') NOT NULL DEFAULT 'other',
    location     VARCHAR(200) NULL,
    latitude     DECIMAL(10,7) NULL,
    longitude    DECIMAL(10,7) NULL,
    start_time   DATETIME NOT NULL,
    end_time     DATETIME NOT NULL,
    capacity     INT UNSIGNED NULL,               -- null = unlimited
    status       ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_events_user (user_id),
    INDEX idx_events_times (start_time, end_time),
    INDEX idx_events_category (category),
    INDEX idx_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EVENT PARTICIPANTS (Attendance / Registrations)
CREATE TABLE event_participants (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id   INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    note       VARCHAR(255) NULL,                 -- optional note / RSVP message
    joined_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ep_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    CONSTRAINT fk_ep_user  FOREIGN KEY (user_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_event_user (event_id, user_id),
    INDEX idx_ep_user (user_id),
    INDEX idx_ep_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4. Integrity & Business Rules
Rule | Enforcement
---- | ----------
One creator per event | events.user_id FK
User cannot join same event twice | UNIQUE (event_id,user_id)
Event end after start | Validate in application (or CHECK constraint in MySQL 8+)
Capacity limit (if set) | Application enforces count(event_participants) < capacity
Cascade delete events on user deletion | FK ON DELETE CASCADE
Cascade delete participants on event deletion | FK ON DELETE CASCADE

Optional MySQL 8+ CHECK example:
```sql
ALTER TABLE events
    ADD CONSTRAINT chk_events_time CHECK (end_time > start_time);
```

---

### 5. Sample Inserts (Minimal Seed)
```sql
INSERT INTO users (display_name,email,password_hash,role) VALUES
 ('Admin','admin@example.com','$2y$10$examplehashadmin','admin'),
 ('Alice Doe','alice@example.com','$2y$10$examplehashalice','user'),
 ('Bob Ray','bob@example.com','$2y$10$examplehashbob','user');

INSERT INTO events (user_id,title,slug,summary,description,category,location,start_time,end_time,status)
VALUES
 (1,'Launch Workshop','launch-workshop','Product intro','Deep dive session','workshop','Main Hall','2025-10-01 09:00:00','2025-10-01 12:00:00','published'),
 (1,'Evening Concert','evening-concert','Live music','Outdoor concert','concert','City Amphitheater','2025-10-05 18:00:00','2025-10-05 21:30:00','published');

INSERT INTO event_participants (event_id,user_id,note) VALUES
 (1,2,'Excited!'),
 (1,3,NULL),
 (2,2,'Front row please');
```

---

### 6. Common Queries
Description | Query
----------- | -----
Events created by a user | `SELECT * FROM events WHERE user_id = ? ORDER BY start_time DESC;`
Events a user joined | `SELECT e.* FROM events e JOIN event_participants ep ON e.event_id = ep.event_id WHERE ep.user_id = ? ORDER BY e.start_time;`
Participants of an event | `SELECT u.display_name,u.email FROM users u JOIN event_participants ep ON u.user_id = ep.user_id WHERE ep.event_id = ? ORDER BY u.display_name;`
Upcoming published events | `SELECT * FROM events WHERE status='published' AND start_time >= NOW() ORDER BY start_time LIMIT 20;`
Remaining capacity (example) | `SELECT e.capacity - COUNT(ep.id) AS remaining FROM events e LEFT JOIN event_participants ep ON e.event_id=ep.event_id WHERE e.event_id=? GROUP BY e.event_id;`

---

### 7. Index Rationale
* `idx_events_times` supports calendar/time-range listings.
* `idx_events_category` filters by category pages.
* `idx_events_status` supports dashboards (draft/published filtering).
* `uq_event_user` prevents duplicate joins quickly.
* Separate `idx_ep_user` & `idx_ep_event` speed reverse lookups (user’s events / event’s participants).

---

### 8. Future Extensions (Ideas)
* categories (normalized table) instead of ENUM
* tickets table (pricing tiers, availability)
* venue table (geo + capacity + address normalization)
* media_assets table (images, attachments) with S3 / local path
* notifications table (email/log dispatch state)
* waitlist support when capacity reached

---

### 9. Teardown (Development Only)
```sql
DROP TABLE IF EXISTS event_participants;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
```

---

### 10. Change Log
Version | Date | Notes
------- | ---- | -----
1.0 | 2025-09-03 | Initial cleaned & expanded schema

---

End of schema.