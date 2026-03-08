# AstroLearn 🚀

**AI-Supported Academic Writing Workspace**

AstroLearn is a school-focused AI writing and learning platform that helps students understand assignments, organize thoughts, improve writing, and develop reasoning skills — without directly handing them answers.

## Core Mission

Create an AI education app that gives struggling students guided support strong enough to help them compete academically with higher-performing students, while preserving learning integrity and avoiding direct answer generation.

## Tech Stack

| Layer       | Technology                                |
|-------------|-------------------------------------------|
| Frontend    | HTML, vanilla JavaScript, TailwindCSS CDN |
| Backend     | PHP 8.x                                   |
| Database    | MySQL / MariaDB                           |
| AI Provider | Abacus.AI (OpenAI-compatible API)         |
| Hosting     | VPS with Nginx/Apache                     |

## Project Structure

```
public_html/
├── index.php              # Landing page
├── login.php              # Authentication
├── register.php           # Registration
├── dashboard.php          # Student/teacher dashboard
├── editor.php             # Document editor with AI sidebar
├── teacher/               # Teacher dashboard pages
│   ├── index.php          # Teacher overview
│   ├── class.php          # Class management
│   ├── assignment.php     # Assignment creation
│   └── flags.php          # Academic integrity flags
├── admin/                 # Admin panel
│   ├── index.php          # Admin overview + stats
│   ├── policies.php       # Global AI policy management
│   └── analytics.php      # Usage analytics
├── api/                   # JSON API endpoints
│   ├── auth.php           # Authentication API
│   ├── documents.php      # Document CRUD + autosave
│   ├── ai.php             # AI assistant (policy-gated)
│   ├── assignments.php    # Assignment management
│   ├── classes.php        # Class & enrollment management
│   ├── teacher.php        # Teacher review endpoints
│   ├── flags.php          # Policy violation listing
│   └── exports.php        # Document export
├── includes/              # Shared PHP libraries
│   ├── config.php         # App configuration
│   ├── db.php             # PDO database connection
│   ├── auth.php           # Session & authentication
│   ├── csrf.php           # CSRF protection
│   ├── helpers.php        # Utility functions
│   ├── ai_client.php      # AI provider abstraction
│   └── policies.php       # Policy engine & cheating detection
├── assets/                # Static assets
│   ├── css/
│   ├── js/
│   └── img/
└── storage/               # File storage (git-ignored)
    ├── uploads/
    ├── exports/
    └── temp/

database/
└── schema.sql             # Full database schema
```

## Setup

### 1. Database

Create a MySQL/MariaDB database and run the schema:

```bash
mysql -u root -p -e "CREATE DATABASE astrolearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p astrolearn < database/schema.sql
```

### 2. Configuration

Copy and edit the config file:

```bash
cp public_html/includes/config.php public_html/includes/config.local.php
```

Update `config.local.php` with your database credentials and AI API key.

Alternatively, set environment variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `AI_API_KEY`, `AI_API_URL`.

### 3. Web Server

Point your web server document root to `public_html/`.

## AI Assistant Modes

| Mode            | Description                                   |
|-----------------|-----------------------------------------------|
| Interpreter     | Simplifies assignment instructions             |
| Planner         | Breaks assignments into steps                  |
| Brainstorm      | Guided ideation through questions              |
| Outline         | Structures intro/body/conclusion               |
| Draft Coach     | Writing feedback without rewriting             |
| Reasoning       | Identifies weak logic and unsupported claims   |
| Reflection      | Asks students to explain their choices         |
| Grammar         | Grammar, clarity, and tone suggestions         |

## Academic Integrity

The platform includes a policy engine that:

- Detects cheating attempts (e.g., "write my essay", "give me the answer")
- Blocks requests and redirects students toward learning support
- Logs all violations with severity levels
- Notifies teachers of flagged incidents
- Supports configurable enforcement levels (strict / balanced / supportive)

## User Roles

- **Student** — Writes documents, receives AI coaching, submits assignments
- **Teacher** — Creates classes/assignments, monitors progress, reviews flags
- **Admin** — Manages global policies, views analytics, administers users

## License

All rights reserved.