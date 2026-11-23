<?php
/** MAVEX Stats left-hand administration navigation. */
if (!defined('e107_INIT')) { exit; }

e107::lan('mavex_stats', 'admin', true);

$menuTitle = LAN_PLUGIN_MAVEX_STATS_NAME;
$currentPage = basename(e_SELF);

// e107 legacy admin navigation expects one associative entry per menu item.
// Each entry has a scalar text and link; arrays here produce URLs ending in "Array".
$var = array();
$var['admin_menu_01']['text'] = LAN_MAVEX_STATS_DASHBOARD;
$var['admin_menu_01']['link'] = 'admin_config.php';
$var['admin_menu_02']['text'] = LAN_MAVEX_STATS_SETTINGS_MENU;
$var['admin_menu_02']['link'] = 'admin_settings.php';

$activePage = ($currentPage === 'admin_settings.php') ? 'admin_menu_02' : 'admin_menu_01';
show_admin_menu($menuTitle, $activePage, $var);
