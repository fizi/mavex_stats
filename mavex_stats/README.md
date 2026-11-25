# MAVEX Stats for e107

MAVEX Stats is a self-hosted statistics plugin for e107 CMS. It collects basic website traffic events and provides a detailed dashboard in the e107 administration area, plus an optional public-facing statistics page.

## Features

### Statistics collection
- Page views and estimated unique visitors
- First-party visitor identifier; only its SHA-256 hash is stored in the event table
- No IP-address storage
- User-Agent based browser, operating-system and device summaries
- Screen-size and external-referrer reporting in the administration dashboard
- Known bot/crawler filtering based on User-Agent strings

### Administration dashboard
- Traffic summaries and charts
- Traffic by weekday and hour
- Most-viewed pages and external referrer domains
- Browser, operating-system, device and screen-size breakdowns
- Configurable dashboard sections and access class
- Optional counting of administrator visits
- Optional automatic event retention (disabled, 90, 180 or 365 days)

### Public statistics page
- Separate public overview at `e107_plugins/mavex_stats/stats.php`
- Visitor, page-view and registered-member KPI cards
- Daily (30 days), monthly (12 months) and yearly (5 years) traffic chart views
- Device, browser and operating-system summaries
- Content-library cards based on published e107 news and visible page chapters
- Hungarian and English frontend language files (`*_front.php`)
- Public-page access controlled by the user class selected in plugin settings

## Installation / upgrade

1. Back up the existing `e107_plugins/mavex_stats/` directory and the database.
2. Extract the `mavex_stats` directory from the release ZIP into `e107_plugins/`, replacing the existing plugin files.
3. Keep the existing `mavex_stats_events` table and its event data. Do not drop the table during an upgrade.
4. In e107 Admin > Database, run **Scan Plugin Folders** so e107 discovers the scheduled-task addon.
5. In Admin > Tools > Scheduled Tasks, enable **MAVEX Stats – automatic data retention cleanup** and schedule it daily if automatic retention is required.
6. In MAVEX Stats > Settings, review the tracking, access-class and retention settings.
7. Open the public page at `e107_plugins/mavex_stats/stats.php` and verify its access using an account in the configured user class.

The 0.8.1 public-page release does not require a database schema migration. Preserve the existing event table and data.

## Privacy and stored data

The plugin does not store IP addresses. It stores the event timestamp, a SHA-256 hash derived from a random first-party visitor identifier, the page path (excluding activation and password-reset query strings), the external referrer hostname, the browser User-Agent string, and screen resolution. The raw visitor identifier is held in a first-party cookie with a 180-day lifetime; only its hash is stored in the database.

The User-Agent and screen resolution are used to produce aggregate browser, operating-system, device and screen-size reports. Page paths and referrer hostnames are stored to build the corresponding reports. Site owners should review their own privacy notice and consent requirements before enabling analytics.

## Bots and crawlers

Known crawlers and automated agents are excluded by matching common bot/crawler identifiers in the request's User-Agent string. This is a practical filter, not a guarantee: some automated clients do not identify themselves, and User-Agent strings can be spoofed. The collector is JavaScript-triggered, so clients that do not execute the page's JavaScript are generally not counted.

## Data retention

Automatic retention is disabled by default. When enabled, the e107 scheduled task removes events older than the selected 90-, 180- or 365-day period. The task must be discovered by scanning plugin folders and then enabled in e107 Scheduled Tasks. Manual deletion of all events remains available in the plugin settings.

## Compatibility and notes

- Developed for e107 2.x; tested by the project author on e107 2.4.0.
- Uses its own `mavex_stats_events` table and does not read or modify e107's built-in statistics/log data.
- No external analytics service is required.
- The detailed dashboard remains in the administration area; the public `stats.php` page is a separate, simplified overview.
- No e107 core files need to be modified.

## Feedback

Feedback, bug reports and contributions from the e107 community are welcome.
