<?php
if (!defined('e107_INIT')) { exit; }

define('LAN_MAVEX_STATS_PUBLIC_BRAND', 'MAVEX TABLE TENNIS');
define('LAN_MAVEX_STATS_PUBLIC_TITLE', 'MAVEX in numbers');
define('LAN_MAVEX_STATS_PUBLIC_INTRO', 'The key figures of the MAVEX table tennis website,<br class="mstats-desktop-only"> its traffic and content activity in one place.');
define('LAN_MAVEX_STATS_PUBLIC_TRAFFIC', 'Traffic');
define('LAN_MAVEX_STATS_PUBLIC_ACTIVITY', 'Website activity');
define('LAN_MAVEX_STATS_PUBLIC_VISITORS', 'Visitors');
define('LAN_MAVEX_STATS_PUBLIC_PAGEVIEWS', 'Page views');
define('LAN_MAVEX_STATS_PUBLIC_MEMBERS', 'Members');
define('LAN_MAVEX_STATS_PUBLIC_PREVIOUS_24H', 'compared with the previous 24 hours');
define('LAN_MAVEX_STATS_PUBLIC_MEMBER_CHANGE', 'change in membership compared with the previous 24 hours');
define('LAN_MAVEX_STATS_PUBLIC_NEW', 'New');
define('LAN_MAVEX_STATS_PUBLIC_TRAFFIC_TREND', 'Traffic trend');
define('LAN_MAVEX_STATS_PUBLIC_CHART_DAILY', 'Daily data · last 30 days');
define('LAN_MAVEX_STATS_PUBLIC_CHART_MONTHLY', 'Monthly data · last 12 months');
define('LAN_MAVEX_STATS_PUBLIC_CHART_YEARLY', 'Yearly data · last 5 years');
define('LAN_MAVEX_STATS_PUBLIC_CHART_AND', 'and');
define('LAN_MAVEX_STATS_PUBLIC_CHART_PERIOD', 'Chart period');
define('LAN_MAVEX_STATS_PUBLIC_DAILY', 'Daily');
define('LAN_MAVEX_STATS_PUBLIC_MONTHLY', 'Monthly');
define('LAN_MAVEX_STATS_PUBLIC_YEARLY', 'Yearly');
define('LAN_MAVEX_STATS_PUBLIC_DEVICES', 'Devices');
define('LAN_MAVEX_STATS_PUBLIC_BROWSERS', 'Browsers');
define('LAN_MAVEX_STATS_PUBLIC_OPERATING_SYSTEMS', 'Operating systems');
define('LAN_MAVEX_STATS_PUBLIC_VISITOR_TECH', 'Visitor technologies');
define('LAN_MAVEX_STATS_PUBLIC_BREAKDOWN_NOTE', 'Based on page views from the last 30 days.');
define('LAN_MAVEX_STATS_PUBLIC_CONTENT_BASE', 'MAVEX content library');
define('LAN_MAVEX_STATS_PUBLIC_CONTENT_BASE_DESC', 'News and specialist sections in one place.');
define('LAN_MAVEX_STATS_PUBLIC_NEWS', 'News');
define('LAN_MAVEX_STATS_PUBLIC_NEWS_DESC', 'Latest news and reports');
define('LAN_MAVEX_STATS_PUBLIC_PUBLISHED_NEWS', 'published news item');
define('LAN_MAVEX_STATS_PUBLIC_PUBLISHED_CONTENT', 'published item');
define('LAN_MAVEX_STATS_PUBLIC_VIEW', 'View');
define('LAN_MAVEX_STATS_PUBLIC_NO_CONTENT', 'There is currently no content to display.');
define('LAN_MAVEX_STATS_PUBLIC_FOOTER', 'Table tennis news, knowledge and community');
define('LAN_MAVEX_STATS_PUBLIC_INSUFFICIENT_DATA', 'Not enough data yet.');
define('LAN_MAVEX_STATS_PUBLIC_OTHER_UNKNOWN', 'Other / unidentified');
define('LAN_MAVEX_STATS_PUBLIC_UNKNOWN', 'Unidentified');
define('LAN_MAVEX_STATS_PUBLIC_DEVICE_TABLET', 'Tablet');
define('LAN_MAVEX_STATS_PUBLIC_DEVICE_MOBILE', 'Mobile');
define('LAN_MAVEX_STATS_PUBLIC_DEVICE_DESKTOP', 'Desktop');
$months = array('Jan.','Feb.','Mar.','Apr.','May','Jun.','Jul.','Aug.','Sep.','Oct.','Nov.','Dec.');
foreach ($months as $i => $month) { define('LAN_MAVEX_STATS_PUBLIC_MONTH_' . ($i + 1), $month); }
