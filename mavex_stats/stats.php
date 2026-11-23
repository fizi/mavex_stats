<?php
/** Front-end MAVEX Stats dashboard, protected by the configured e107 user class. */
require_once('../../class2.php');
e107::lan('mavex_stats', 'admin', true);
$statsPrefs = array_merge(array('view_class' => defined('e_UC_ADMIN') ? e_UC_ADMIN : 254), (array) e107::pref('mavex_stats'));
if (!check_class((int) $statsPrefs['view_class'])) {
    e107::redirect(e_LOGIN);
    exit;
}
e107::css('mavex_stats', 'css/admin.css');
require_once(HEADERF);
require_once(__DIR__ . '/dashboard.php');
require_once(FOOTERF);
exit;
