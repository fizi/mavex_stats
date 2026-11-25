<?php
/** MAVEX Stats settings and maintenance. */
require_once('../../class2.php');
if (!getperms('P')) { e107::redirect('admin'); exit; }

e107::lan('mavex_stats', 'admin', true);
e107::css('mavex_stats', 'css/admin.css');
require_once(e_ADMIN . 'auth.php');

$config = e107::getPlugConfig('mavex_stats');
$defaults = array(
    'tracking_enabled' => 1,
    'count_admin' => 0,
    'max_rows' => 10,
    'retention_days' => 0,
    'view_class' => defined('e_UC_ADMIN') ? e_UC_ADMIN : 254,
    'show_pages' => 1,
    'show_referrers' => 1,
    'show_browsers' => 1,
    'show_os' => 1,
    'show_devices' => 1,
    'show_screens' => 1,
    'show_peaks' => 1,
    'show_trends' => 1
);
$prefs = array_merge($defaults, (array) e107::pref('mavex_stats'));
$mes = e107::getMessage();

if (isset($_POST['mstats_save'])) {
    $new = $defaults;
    foreach (array('tracking_enabled','count_admin','show_pages','show_referrers','show_browsers','show_os','show_devices','show_screens','show_peaks','show_trends') as $key) {
        $new[$key] = isset($_POST[$key]) ? 1 : 0;
    }
    $new['max_rows'] = max(5, min(100, (int) ($_POST['max_rows'] ?? 10)));
    $allowedRetention = array(0, 90, 180, 365);
    $requestedRetention = (int) ($_POST['retention_days'] ?? 0);
    $new['retention_days'] = in_array($requestedRetention, $allowedRetention, true) ? $requestedRetention : 0;
    $new['view_class'] = max(0, (int) ($_POST['view_class'] ?? $defaults['view_class']));
    $saved = $config->setPref($new)->save(false, true, false);
    if ($saved) {
        $prefs = array_merge($defaults, $new);
        $mes->addSuccess('A MAVEX Stats beállításai sikeresen mentve.');
    } else {
        $mes->addError('A beállítások mentése nem sikerült.');
    }
}

if (isset($_POST['mstats_delete']) && isset($_POST['confirm_delete'])) {
    $deleted = e107::getDb()->db_Delete('mavex_stats_events', '1');
    if ($deleted !== false) {
        $mes->addSuccess('A statisztikai események törlése megtörtént. Az adatgyűjtés a beállítások szerint folytatódik.');
    } else {
        $mes->addError('A statisztikai adatok törlése nem sikerült.');
    }
}

$frm = e107::getForm();
$userClassDropdown = e107::getUserClass()->uc_dropdown('view_class', (int) $prefs['view_class'], 'public,guest,member,admin,classes');
$checkbox = static function($name, $label, $checked, $help = '') {
    return '<div class="checkbox"><label><input type="checkbox" name="' . $name . '" value="1"' . ($checked ? ' checked="checked"' : '') . '> ' . $label . '</label>' . ($help ? '<p class="help-block">' . $help . '</p>' : '') . '</div>';
};
$text = '<div class="mstats-dashboard">';
$text .= '<div class="mstats-titlebar"><div><div class="mstats-title-kicker">MAVEX TEREM · WEBANALITIKA</div><h2>MAVEX Stats – Beállítások</h2><div class="mstats-title-subtitle">Adatgyűjtés, hozzáférés és megjelenítési lehetőségek kezelése.</div></div><div><a class="btn btn-default" href="' . e_PLUGIN . 'mavex_stats/admin_config.php">Vissza az áttekintőhöz</a></div></div>';
$text .= e107::getMessage()->render();
$text .= $frm->open('mstats_settings', 'post', e_SELF, array('class' => 'mstats-settings-form'));
$text .= '<div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Adatgyűjtés</h3></div><div class="panel-body">';
$text .= $checkbox('tracking_enabled', 'Statisztika-adatgyűjtés engedélyezése', !empty($prefs['tracking_enabled']), 'Kikapcsolva a plugin nem rögzít új oldalmegtekintéseket.');
$text .= $checkbox('count_admin', 'Adminisztrátori látogatások számlálása', !empty($prefs['count_admin']), 'Bekapcsolva a bejelentkezett, statisztikai adminjoggal rendelkező felhasználók látogatásai is bekerülnek.');
$text .= '</div></div>';
$text .= '<div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Hozzáférés</h3></div><div class="panel-body"><div class="form-group"><label for="view_class">A statisztika oldalt elérhetik</label>' . $userClassDropdown . '<p class="help-block">A nyilvános statisztikai oldal címe: <code>' . SITEURLBASE . e_PLUGIN_ABS . 'mavex_stats/stats.php</code>. Az adminisztrációs beállítások továbbra is csak adminisztrátoroknak érhetők el.</p></div></div></div>';
$text .= '<div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Megjelenítési beállítások</h3></div><div class="panel-body">';
$text .= '<div class="form-group"><label for="max_rows">Megjelenített rekordok maximális száma</label><input class="form-control" type="number" min="5" max="100" name="max_rows" id="max_rows" value="' . (int) $prefs['max_rows'] . '"><p class="help-block">A legnézettebb oldalak és a külső hivatkozások listájára vonatkozik (5–100).</p></div>';
$retentionOptions = array(0 => LAN_MAVEX_STATS_RETENTION_OFF, 90 => LAN_MAVEX_STATS_RETENTION_90, 180 => LAN_MAVEX_STATS_RETENTION_180, 365 => LAN_MAVEX_STATS_RETENTION_365);
$retentionSelect = '<div class="form-group"><label for="retention_days">' . LAN_MAVEX_STATS_RETENTION_LABEL . '</label><select class="form-control mstats-compact-select" name="retention_days" id="retention_days">';
foreach ($retentionOptions as $days => $label) {
    $retentionSelect .= '<option value="' . (int) $days . '"' . ((int) $prefs['retention_days'] === (int) $days ? ' selected="selected"' : '') . '>' . $label . '</option>';
}
$retentionSelect .= '</select><p class="help-block">' . LAN_MAVEX_STATS_RETENTION_HELP . '</p></div>' .
    '<div class="form-group"><label>' . LAN_MAVEX_STATS_BOT_FILTER . '</label><p class="help-block">' . LAN_MAVEX_STATS_BOT_FILTER_HELP . '</p></div>';
$text .= $retentionSelect;
$text .= '<div class="row"><div class="col-sm-6">';
$text .= $checkbox('show_pages', 'Legnézettebb oldalak', !empty($prefs['show_pages']));
$text .= $checkbox('show_referrers', 'Külső hivatkozások', !empty($prefs['show_referrers']));
$text .= $checkbox('show_browsers', 'Böngészők', !empty($prefs['show_browsers']));
$text .= $checkbox('show_os', 'Operációs rendszerek', !empty($prefs['show_os']));
$text .= '</div><div class="col-sm-6">';
$text .= $checkbox('show_devices', 'Eszköztípusok', !empty($prefs['show_devices']));
$text .= $checkbox('show_screens', 'Képernyőméretek', !empty($prefs['show_screens']));
$text .= $checkbox('show_peaks', 'Heti és óránkénti forgalmi grafikonok', !empty($prefs['show_peaks']));
$text .= $checkbox('show_trends', 'Heti és havi összesítések', !empty($prefs['show_trends']));
$text .= '</div></div></div></div>';
$text .= '<div class="text-right"><button type="submit" name="mstats_save" value="1" class="btn btn-primary">Beállítások mentése</button></div>';
$text .= $frm->close();
$text .= '<div class="panel mstats-panel mstats-danger-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Statisztikák törlése</h3></div><div class="panel-body"><p>Az összes eddig rögzített oldalmegtekintés és látogatói esemény véglegesen törlődik. A beállítások és a plugin működése nem változik.</p>';
$text .= $frm->open('mstats_delete_form', 'post', e_SELF, array('onsubmit' => "return confirm('Biztosan törlöd az összes MAVEX Stats adatot? A művelet nem vonható vissza.');"));
$text .= '<div class="mstats-delete-confirm"><label><input type="checkbox" name="confirm_delete" value="1" required> <span>Megértettem, hogy az összes statisztikai adat törlődik.</span></label><button type="submit" name="mstats_delete" value="1" class="btn btn-danger">Összes statisztika törlése</button></div>'; 
$text .= $frm->close() . '</div></div>';
$text .= '<div class="alert alert-info">Az eseményadatok egyetlen közös táblában tárolódnak, ezért külön kategóriák törlése nem biztonságos: például a böngészőadatok külön törlése a látogatási események többi adatát is érintené. Jelenleg ezért a teljes statisztikai adathalmaz törlése érhető el.</div>';
$text .= '</div>';

$mstatsSettingsTranslations = array(
    'A MAVEX Stats beállításai sikeresen mentve.' => LAN_MAVEX_STATS_SETTINGS_SAVED,
    'A beállítások mentése nem sikerült.' => LAN_MAVEX_STATS_SETTINGS_SAVE_FAILED,
    'A statisztikai események törlése megtörtént. Az adatgyűjtés a beállítások szerint folytatódik.' => LAN_MAVEX_STATS_DELETE_SUCCESS,
    'A statisztikai adatok törlése nem sikerült.' => LAN_MAVEX_STATS_DELETE_FAILED,
    'MAVEX Stats – Beállítások' => LAN_MAVEX_STATS_SETTINGS_TITLE,
    'Adatgyűjtés, hozzáférés és megjelenítési lehetőségek kezelése.' => LAN_MAVEX_STATS_SETTINGS_SUBTITLE,
    'Vissza az áttekintőhöz' => LAN_MAVEX_STATS_BACK_TO_DASHBOARD,
    'Adatgyűjtés' => LAN_MAVEX_STATS_TRACKING,
    'Statisztika-adatgyűjtés engedélyezése' => LAN_MAVEX_STATS_TRACKING_ENABLE,
    'Kikapcsolva a plugin nem rögzít új oldalmegtekintéseket.' => LAN_MAVEX_STATS_TRACKING_HELP,
    'Adminisztrátori látogatások számlálása' => LAN_MAVEX_STATS_COUNT_ADMIN,
    'Bekapcsolva a bejelentkezett, statisztikai adminjoggal rendelkező felhasználók látogatásai is bekerülnek.' => LAN_MAVEX_STATS_COUNT_ADMIN_HELP,
    'Hozzáférés' => LAN_MAVEX_STATS_ACCESS,
    'A statisztika oldalt elérhetik' => LAN_MAVEX_STATS_VIEW_CLASS_LABEL,
    'A nyilvános statisztikai oldal címe: ' => LAN_MAVEX_STATS_PUBLIC_STATS_URL_PREFIX,
    '. Az adminisztrációs beállítások továbbra is csak adminisztrátoroknak érhetők el.' => LAN_MAVEX_STATS_PUBLIC_STATS_URL_SUFFIX,
    'Megjelenítési beállítások' => LAN_MAVEX_STATS_DISPLAY_SETTINGS,
    'Megjelenített rekordok maximális száma' => LAN_MAVEX_STATS_MAX_ROWS,
    'A legnézettebb oldalak és a külső hivatkozások listájára vonatkozik (5–100).' => LAN_MAVEX_STATS_MAX_ROWS_HELP,
    'Legnézettebb oldalak' => LAN_MAVEX_STATS_TOP_PAGES,
    'Külső hivatkozások' => LAN_MAVEX_STATS_REFERRERS,
    'Böngészők' => LAN_MAVEX_STATS_BROWSERS,
    'Operációs rendszerek' => LAN_MAVEX_STATS_OPERATING_SYSTEMS,
    'Eszköztípusok' => LAN_MAVEX_STATS_DEVICE_TYPES,
    'Képernyőméretek' => LAN_MAVEX_STATS_SCREEN_SIZES,
    'Heti és óránkénti forgalmi grafikonok' => LAN_MAVEX_STATS_PEAK_CHARTS,
    'Heti és havi összesítések' => LAN_MAVEX_STATS_TREND_SUMMARIES,
    'Beállítások mentése' => LAN_MAVEX_STATS_SAVE_SETTINGS,
    'Statisztikák törlése' => LAN_MAVEX_STATS_DELETE_STATS,
    'Az összes eddig rögzített oldalmegtekintés és látogatói esemény véglegesen törlődik. A beállítások és a plugin működése nem változik.' => LAN_MAVEX_STATS_DELETE_WARNING,
    'Biztosan törlöd az összes MAVEX Stats adatot? A művelet nem vonható vissza.' => LAN_MAVEX_STATS_DELETE_CONFIRM,
    'Megértettem, hogy az összes statisztikai adat törlődik.' => LAN_MAVEX_STATS_DELETE_ACK,
    'Összes statisztika törlése' => LAN_MAVEX_STATS_DELETE_BUTTON,
    'Az eseményadatok egyetlen közös táblában tárolódnak, ezért külön kategóriák törlése nem biztonságos: például a böngészőadatok külön törlése a látogatási események többi adatát is érintené. Jelenleg ezért a teljes statisztikai adathalmaz törlése érhető el.' => LAN_MAVEX_STATS_DELETE_INFO,
    'Beállítások' => LAN_MAVEX_STATS_SETTINGS_MENU
);
$text = preg_replace_callback('/>([^<>]+)</u', static function ($matches) use ($mstatsSettingsTranslations) {
    $nodeText = $matches[1];
    $trimmed = trim($nodeText);
    if ($trimmed !== '' && array_key_exists($trimmed, $mstatsSettingsTranslations)) {
        $leading = substr($nodeText, 0, strlen($nodeText) - strlen(ltrim($nodeText)));
        $trailing = substr($nodeText, strlen(rtrim($nodeText)));
        return '>' . $leading . $mstatsSettingsTranslations[$trimmed] . $trailing . '<';
    }
    return $matches[0];
}, $text);

e107::getRender()->tablerender(LAN_MAVEX_STATS_SETTINGS_TITLE, $text);
require_once(e_ADMIN . 'footer.php');
exit;
