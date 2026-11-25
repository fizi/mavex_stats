<?php
if (!defined('e107_INIT')) { exit; }

define('LAN_MAVEX_STATS_PUBLIC_BRAND', 'MAVEX TEREM');
define('LAN_MAVEX_STATS_PUBLIC_TITLE', 'MAVEX számokban');
define('LAN_MAVEX_STATS_PUBLIC_INTRO', 'A MAVEX asztalitenisz weboldal legfontosabb adatai,<br class="mstats-desktop-only"> látogatottsága és tartalmi aktivitása egy helyen.');
define('LAN_MAVEX_STATS_PUBLIC_TRAFFIC', 'Látogatottság');
define('LAN_MAVEX_STATS_PUBLIC_ACTIVITY', 'Aktivitás a weboldalon');
define('LAN_MAVEX_STATS_PUBLIC_VISITORS', 'Látogatók');
define('LAN_MAVEX_STATS_PUBLIC_PAGEVIEWS', 'Oldalmegtekintések');
define('LAN_MAVEX_STATS_PUBLIC_MEMBERS', 'Tagok');
define('LAN_MAVEX_STATS_PUBLIC_PREVIOUS_24H', 'az előző 24 órához képest');
define('LAN_MAVEX_STATS_PUBLIC_MEMBER_CHANGE', 'taglétszám-változás az előző 24 órához képest');
define('LAN_MAVEX_STATS_PUBLIC_NEW', 'Új');
define('LAN_MAVEX_STATS_PUBLIC_TRAFFIC_TREND', 'Látogatottság alakulása');
define('LAN_MAVEX_STATS_PUBLIC_CHART_DAILY', 'Napi adatok · utolsó 30 nap');
define('LAN_MAVEX_STATS_PUBLIC_CHART_MONTHLY', 'Havi adatok · utolsó 12 hónap');
define('LAN_MAVEX_STATS_PUBLIC_CHART_YEARLY', 'Éves adatok · utolsó 5 év');
define('LAN_MAVEX_STATS_PUBLIC_CHART_AND', 'és');
define('LAN_MAVEX_STATS_PUBLIC_CHART_PERIOD', 'Grafikon időszaka');
define('LAN_MAVEX_STATS_PUBLIC_DAILY', 'Napi');
define('LAN_MAVEX_STATS_PUBLIC_MONTHLY', 'Havi');
define('LAN_MAVEX_STATS_PUBLIC_YEARLY', 'Éves');
define('LAN_MAVEX_STATS_PUBLIC_DEVICES', 'Eszközök');
define('LAN_MAVEX_STATS_PUBLIC_BROWSERS', 'Böngészők');
define('LAN_MAVEX_STATS_PUBLIC_OPERATING_SYSTEMS', 'Operációs rendszerek');
define('LAN_MAVEX_STATS_PUBLIC_VISITOR_TECH', 'Látogatói technológiák');
define('LAN_MAVEX_STATS_PUBLIC_BREAKDOWN_NOTE', 'Az adatok az elmúlt 30 nap oldalmegtekintései alapján készülnek.');
define('LAN_MAVEX_STATS_PUBLIC_CONTENT_BASE', 'A MAVEX tartalmi bázisa');
define('LAN_MAVEX_STATS_PUBLIC_CONTENT_BASE_DESC', 'Hírek és szakmai fejezetek egy helyen.');
define('LAN_MAVEX_STATS_PUBLIC_NEWS', 'Hírek');
define('LAN_MAVEX_STATS_PUBLIC_NEWS_DESC', 'Friss hírek és beszámolók');
define('LAN_MAVEX_STATS_PUBLIC_PUBLISHED_NEWS', 'publikált hír');
define('LAN_MAVEX_STATS_PUBLIC_PUBLISHED_CONTENT', 'publikált tartalom');
define('LAN_MAVEX_STATS_PUBLIC_VIEW', 'Megtekintés');
define('LAN_MAVEX_STATS_PUBLIC_NO_CONTENT', 'Jelenleg nincs megjeleníthető tartalom.');
define('LAN_MAVEX_STATS_PUBLIC_FOOTER', 'Asztalitenisz hírek, tudás és közösség');
define('LAN_MAVEX_STATS_PUBLIC_INSUFFICIENT_DATA', 'Még nincs elegendő adat.');
define('LAN_MAVEX_STATS_PUBLIC_OTHER_UNKNOWN', 'Egyéb / nem azonosított');
define('LAN_MAVEX_STATS_PUBLIC_UNKNOWN', 'Nem azonosított');
define('LAN_MAVEX_STATS_PUBLIC_DEVICE_TABLET', 'Tablet');
define('LAN_MAVEX_STATS_PUBLIC_DEVICE_MOBILE', 'Mobil');
define('LAN_MAVEX_STATS_PUBLIC_DEVICE_DESKTOP', 'Asztali gép');
$months = array('jan.','febr.','márc.','ápr.','máj.','jún.','júl.','aug.','szept.','okt.','nov.','dec.');
foreach ($months as $i => $month) { define('LAN_MAVEX_STATS_PUBLIC_MONTH_' . ($i + 1), $month); }
