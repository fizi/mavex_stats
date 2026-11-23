# MAVEX Stats for e107

MAVEX Stats is an independent statistics plugin for e107 CMS. It provides a modern, self-hosted overview of website traffic through the e107 administration environment. The project was developed with AI assistance and is tested on a live e107 website.

## Features

- Page views and unique visitor estimates
- Daily, weekly and monthly traffic charts and summaries
- Traffic by weekday and hour
- Most-viewed pages and external referrer domains
- Browser, operating system, device type and screen-size breakdowns
- Configurable dashboard sections and access class
- Optional counting of administrator visits
- Known bot/crawler filtering based on User-Agent strings
- Optional automatic event retention (disabled, 90, 180 or 365 days)

## Installation / upgrade

1. Back up your existing `e107_plugins/mavex_stats/` directory and database.
2. Extract the `mavex_stats` directory from the release ZIP into `e107_plugins/`.
3. Keep the existing `mavex_stats_events` table. No schema migration is required for 0.7.9.
4. In e107 Admin > Database, run **Scan Plugin Folders** so e107 discovers the scheduled-task addon.
5. In Admin > Tools > Scheduled Tasks, enable **MAVEX Stats – automatic data retention cleanup** and schedule it daily.
6. In MAVEX Stats > Settings, choose a retention period if desired. Retention is disabled by default.

## What is stored for each event?

The plugin stores the event timestamp, a SHA-256 hash derived from a random first-party visitor identifier, the page path, the external referrer hostname, the browser User-Agent string, and the screen resolution. **It does not store IP addresses.** The random visitor identifier is held in a first-party cookie with a 180-day lifetime; the database stores its SHA-256 hash, not the raw cookie value.

The User-Agent and screen resolution are used to produce aggregate browser, operating-system, device and screen-size reports. Page paths and referrer hostnames are stored to build the corresponding reports. Site owners should review their own privacy notice and consent requirements before enabling analytics.

## Bots and crawlers

Known crawlers and automated agents are excluded by matching common bot/crawler identifiers in the request's User-Agent string. This is a practical filter, not a guarantee: some automated clients do not identify themselves, and User-Agent strings can be spoofed. The collector is JavaScript-triggered, so clients that do not execute the page's JavaScript are generally not counted.

## Data retention

Automatic retention is **disabled by default**. When enabled, the e107 scheduled task removes events older than the selected 90-, 180- or 365-day period. The task must be discovered by scanning plugin folders and then enabled in e107 Scheduled Tasks. Manual deletion of all events remains available in the plugin settings.

## Compatibility and notes

- Developed for e107 2.x; tested by the project author on e107 2.4.0.
- Uses its own `mavex_stats_events` table and does not read or modify e107's built-in statistics/log data.
- No external analytics service is required.
- The public statistics page is protected by the e107 user class selected in plugin settings.

## Feedback

Feedback, bug reports and contributions from the e107 community are welcome.
