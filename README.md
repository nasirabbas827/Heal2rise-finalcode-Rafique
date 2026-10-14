# Heal2Rise Book

Heal2rise-finalcode-Rafique is a support platform for case management, counselling, and NGO coordination.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `admins`, `cases`, `case_counselor`, `closure_requests`, `counseling_sessions`, `counselors`, `ngos`, `skill_programs`, `team_members`, `users`.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Admin | [`admin/add_team.php`](admin/add_team.php), [`admin/admin_home.php`](admin/admin_home.php), [`admin/admin_login.php`](admin/admin_login.php), [`admin/admin_navbar.php`](admin/admin_navbar.php), [`admin/edit_team.php`](admin/edit_team.php) |
| Counselor | [`counselor/dashboard.php`](counselor/dashboard.php), [`counselor/manage_programs.php`](counselor/manage_programs.php), [`counselor/manage_sessions.php`](counselor/manage_sessions.php), [`counselor/view_case.php`](counselor/view_case.php) |
| Root Pages | [`login.php`](login.php), [`ngo_register.php`](ngo_register.php), [`register.php`](register.php) |
| Ngo | [`ngo/dashboard.php`](ngo/dashboard.php), [`ngo/ngo_profile.php`](ngo/ngo_profile.php), [`ngo/view_case_details.php`](ngo/view_case_details.php), [`ngo/view_cases.php`](ngo/view_cases.php) |
| Team Members | [`team_members/add_counselor.php`](team_members/add_counselor.php), [`team_members/closure_request.php`](team_members/closure_request.php), [`team_members/edit_counselor.php`](team_members/edit_counselor.php), [`team_members/recommend_counselor.php`](team_members/recommend_counselor.php), [`team_members/staff_dashboard.php`](team_members/staff_dashboard.php) |
| User | [`user/dashboard.php`](user/dashboard.php), [`user/edit_case.php`](user/edit_case.php), [`user/submit_case.php`](user/submit_case.php), [`user/user_profile.php`](user/user_profile.php), [`user/user_sessions.php`](user/user_sessions.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/Heal2rise-finalcode-Rafique.git
cd Heal2rise-finalcode-Rafique
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/h2r_db.sql`](Database/h2r_db.sql) into your local database server.
3. Set your local database connection details in [`admin/config.php`](admin/config.php), [`config.php`](config.php), [`counselor/config.php`](counselor/config.php), [`ngo/config.php`](ngo/config.php), [`team_members/config.php`](team_members/config.php), [`user/config.php`](user/config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`Database`](Database) | Database scripts |
| [`config.php`](config.php) | Application/database configuration |
| [`css`](css) | Stylesheets |
| [`images`](images) | Image assets |
| [`index.php`](index.php) | Web entry page |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
