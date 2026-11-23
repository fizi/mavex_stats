<?php
/** MAVEX Stats administration dashboard. */
require_once('../../class2.php');
if (!getperms('P')) { e107::redirect('admin'); exit; }
e107::lan('mavex_stats', 'admin', true);
e107::css('mavex_stats', 'css/admin.css');
require_once(e_ADMIN . 'auth.php');
require_once(__DIR__ . '/dashboard.php');
require_once(e_ADMIN . 'footer.php');
exit;
