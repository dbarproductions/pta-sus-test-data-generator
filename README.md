# PTA SUS Test Data Generator

A WordPress plugin that quickly populates a **local or development** environment with realistic test data — users, sign-up sheets, tasks, and signups — for the [PTA Volunteer Sign-Up Sheets](https://github.com/) plugin.

> ⚠️ **This plugin is intended for development and testing only.** Do not activate it on a production site.

---

## Features

- **User generation** — creates WordPress users with `signup_sheet_manager`, `signup_sheet_author`, and `subscriber` roles, using predictable usernames (`testuser_author_1`, etc.) so they're easy to find and clean up.
- **Sheet & task generation** — creates sign-up sheets with realistic titles, task lists, dates, chair names, author assignments, and reminder days, using built-in scenario presets or fully randomised data.
- **Signup generation** — fills a configurable percentage of available spots across all generated sheets, with a configurable mix of tracked test users and randomly-named guest signups.
- **One-click cleanup** — tracks every generated ID and deletes everything (users, sheets, tasks, signups) in a single operation.

All data is created through the core plugin's model layer (`pta_sus_add_sheet()`, `pta_sus_add_task()`, `pta_sus_add_signup()`) so all hooks fire and data is structurally identical to real data.

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- [PTA Volunteer Sign-Up Sheets](https://github.com/) v6.2.0+ must be installed and active

---

## Installation

1. Clone or download this repository into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/YOUR_USERNAME/pta-sus-test-data-generator.git
   ```
2. Activate **PTA Volunteer Sign-Up Sheets** first.
3. Activate **PTA SUS Test Data Generator** in WP Admin → Plugins.
4. Navigate to **Sign-Up Sheets → Test Data Generator**.

---

## Usage

The plugin adds a **Test Data Generator** submenu under the Sign-Up Sheets admin menu with four tabs:

### Tab 1 — Users
Set how many Manager, Author, and Subscriber users to create. All generated users get the password `TestPass123!` (shown on the result screen).

### Tab 2 — Sheets & Tasks
Choose a **scenario preset**, number of sheets, tasks-per-sheet range, date range, and (for the Randomize preset) an optional sheet type override. Generated sheets are automatically assigned:
- A random tracked test author as the sheet author
- 1–3 randomly generated chair names and emails
- Reminder days: 7 days and 1 day

### Tab 3 — Signups
Set a **fill rate** (% of available spots to fill) and a **user mix** (% from tracked test users vs. randomly named guests). Signups are spread across all sheets generated in Tab 2.

### Tab 4 — Cleanup
Shows a live summary of all tracked data. Delete users, sheets (cascades to tasks and signups), or everything at once with a confirmation prompt.

---

## Scenario Presets

| Preset | Sheet Type | Description |
|---|---|---|
| **Bake Sale** | Single | Baked goods contribution slots |
| **School Carnival** | Multi-Day | Event booths across multiple days |
| **Committee Meetings** | Recurring | Weekly recurring meeting roles |
| **Volunteer Fair** | Ongoing | Open-ended opportunity table |
| **Randomize** | Random | Random titles, tasks, types, and settings |

---

## Contributing

Contributions are welcome! Here are some great ways to help:

### Adding new presets
The easiest contribution is adding a new scenario preset. Presets live in [`includes/data/presets.php`](includes/data/presets.php). Each preset is a simple PHP array with:

```php
'my_preset' => array(
    'sheet_type'   => 'Single',          // Single | Multi-Day | Recurring | Ongoing | random
    'titles'       => array( 'Title A', 'Title B' ),
    'tasks'        => array( 'Task 1', 'Task 2', 'Task 3' ),
    'need_details' => 'NO',              // YES or NO
    'details_text' => '',                // Label shown to volunteers if need_details = YES
),
```

You'll also need to add the preset to the dropdown in [`includes/class-ptg-admin.php`](includes/class-ptg-admin.php) in the `render_sheets_tab()` method.

### Other ideas
- **Extension integration** — add optional support for the Locations extension (assign generated locations to sheets/tasks) or Automated Emails (attach triggers to generated sheets)
- **Specific fill patterns** — e.g. "fully booked task", "single spot remaining"
- **CSV export** of generated data for sharing test scenarios
- **Bigger name pools** — expand `includes/data/fake-names.php` with more first/last names, or add locale-specific name sets

### Getting started
1. Fork this repository
2. Create a branch: `git checkout -b feature/my-preset`
3. Make your changes and test locally with WP_DEBUG enabled
4. Submit a pull request with a brief description of what your preset/feature covers

Please follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) for any PHP changes.

---

## File Structure

```
pta-sus-test-data-generator/
├── pta-sus-test-data-generator.php    ← Plugin bootstrap & dependency check
├── includes/
│   ├── class-ptg-admin.php            ← Admin page, tabs, forms, POST handlers
│   ├── class-ptg-user-generator.php   ← WP user creation
│   ├── class-ptg-sheet-generator.php  ← Sheet + task creation
│   ├── class-ptg-signup-generator.php ← Signup creation
│   ├── class-ptg-tracker.php          ← ID tracking & bulk delete
│   └── data/
│       ├── presets.php                ← Scenario preset definitions
│       └── fake-names.php             ← Name pools for guest signups
└── assets/
    ├── ptg-admin.css                  ← Admin UI styles
    └── ptg-admin.js                   ← Tab switching, confirmations
```

---

## License

GPL-2.0-or-later — same as WordPress itself.
