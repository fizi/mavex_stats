<?php
/** Shared MAVEX Stats dashboard renderer. Entry points enforce access. */
if (!defined('e107_INIT')) { exit; }
$sql = e107::getDb();
$mstatsPrefs = array_merge(array(
    'tracking_enabled' => 1, 'count_admin' => 0, 'max_rows' => 10,
    'view_class' => defined('e_UC_ADMIN') ? e_UC_ADMIN : 254,
    'show_pages' => 1, 'show_referrers' => 1, 'show_browsers' => 1,
    'show_os' => 1, 'show_devices' => 1, 'show_screens' => 1,
    'show_peaks' => 1, 'show_trends' => 1
), (array) e107::pref('mavex_stats'));
$mstatsMaxRows = max(5, min(100, (int) $mstatsPrefs['max_rows']));
$period = isset($_GET['period']) ? (string) $_GET['period'] : '7';
$allowedPeriods = array('today', '7', '30', '90', 'all');
if (!in_array($period, $allowedPeriods, true)) { $period = '7'; }
$now = time();
$todayStart = strtotime('today', $now);
$periodStart = $period === 'today' ? $todayStart : ($period === 'all' ? 0 : strtotime('-' . ((int) $period - 1) . ' days', $todayStart));
$periodEnd = $now;
$where = 'event_time >= ' . (int) $periodStart . ' AND event_time <= ' . (int) $periodEnd;

/* Independent calendar navigation for the main traffic chart. */
$chartView = isset($_GET['chart_view']) ? (string) $_GET['chart_view'] : 'month';
if (!in_array($chartView, array('day', 'month', 'year'), true)) { $chartView = 'month'; }
$chartDateInput = isset($_GET['chart_date']) ? (string) $_GET['chart_date'] : date('Y-m-d', $now);
$chartDateObject = DateTime::createFromFormat('!Y-m-d', $chartDateInput);
if (!$chartDateObject || $chartDateObject->format('Y-m-d') !== $chartDateInput) {
    $chartDateObject = new DateTime(date('Y-m-d', $now));
}
$chartDateObject->setTime(0, 0, 0);
if ($chartView === 'month') { $chartDateObject->modify('first day of this month'); }
elseif ($chartView === 'year') { $chartDateObject->setDate((int) $chartDateObject->format('Y'), 1, 1); }
$chartToday = new DateTime(date('Y-m-d', $now));
$chartStartObject = clone $chartDateObject;
$chartEndObject = clone $chartDateObject;
if ($chartView === 'day') {
    $chartEndObject->setTime(23, 59, 59);
    $chartHeading = $chartDateObject->format('Y. m. d.');
    $chartBucketCount = 24;
    $chartSqlKey = 'HOUR(FROM_UNIXTIME(event_time))';
} elseif ($chartView === 'month') {
    $chartEndObject->modify('last day of this month')->setTime(23, 59, 59);
    $chartHeading = $chartDateObject->format('Y. m.');
    $chartBucketCount = (int) $chartDateObject->format('t');
    $chartSqlKey = 'DATE(FROM_UNIXTIME(event_time))';
} else {
    $chartEndObject->setDate((int) $chartDateObject->format('Y'), 12, 31)->setTime(23, 59, 59);
    $chartHeading = $chartDateObject->format('Y');
    $chartBucketCount = 12;
    $chartSqlKey = "DATE_FORMAT(FROM_UNIXTIME(event_time), '%Y-%m')";
}
$chartStart = $chartStartObject->getTimestamp();
$chartEnd = min($chartEndObject->getTimestamp(), $now);
$chartWhere = 'event_time >= ' . (int) $chartStart . ' AND event_time <= ' . (int) $chartEnd;
$chartCanGoNext = $chartView === 'day'
    ? $chartDateObject->format('Y-m-d') < $chartToday->format('Y-m-d')
    : ($chartView === 'month'
        ? $chartDateObject->format('Y-m') < $chartToday->format('Y-m')
        : (int) $chartDateObject->format('Y') < (int) $chartToday->format('Y'));
$chartPrevDate = clone $chartDateObject;
$chartNextDate = clone $chartDateObject;
if ($chartView === 'day') { $chartPrevDate->modify('-1 day'); $chartNextDate->modify('+1 day'); }
elseif ($chartView === 'month') { $chartPrevDate->modify('-1 month'); $chartNextDate->modify('+1 month'); }
else { $chartPrevDate->modify('-1 year'); $chartNextDate->modify('+1 year'); }
$chartUrl = static function ($view, $date) use ($period) {
    return e_SELF . '?period=' . rawurlencode($period) . '&chart_view=' . rawurlencode($view) . '&chart_date=' . rawurlencode($date);
};

$totalEvents = 0;
$periodEvents = 0;
$periodVisitors = 0;
$topPages = array();
$topReferrers = array();
$browserStats = array();
$deviceStats = array();
$osStats = array();
$screenStats = array();
$dailyStats = array();
$weeklyTrend = array();
$monthlyTrend = array();
$comparison = array('views' => null, 'visitors' => null, 'label' => '');
$trackingStarted = '';
$dbError = false;

try {
    if ($sql->db_Select('mavex_stats_events', 'COUNT(*) AS total_events')) {
        $row = $sql->db_Fetch();
        $totalEvents = (int) ($row['total_events'] ?? 0);
    }
    if ($sql->db_Select('mavex_stats_events', 'COUNT(*) AS total_events', $where)) {
        $row = $sql->db_Fetch();
        $periodEvents = (int) ($row['total_events'] ?? 0);
    }
    if ($sql->db_Select('mavex_stats_events', 'COUNT(DISTINCT visitor_hash) AS total_visitors', $where)) {
        $row = $sql->db_Fetch();
        $periodVisitors = (int) ($row['total_visitors'] ?? 0);
    }
    $comparison = array('views' => null, 'visitors' => null, 'label' => '');
    if ($period !== 'all') {
        if ($period === 'today') {
            $previousStart = $periodStart - 86400;
            $previousEnd = $periodStart - 1;
        } else {
            $periodSeconds = max(1, $periodEnd - $periodStart);
            $previousStart = $periodStart - $periodSeconds - 1;
            $previousEnd = $periodStart - 1;
        }
        $previousWhere = 'event_time >= ' . (int) $previousStart . ' AND event_time <= ' . (int) $previousEnd;
        if ($sql->db_Select('mavex_stats_events', 'COUNT(*) AS total_events', $previousWhere)) {
            $row = $sql->db_Fetch();
            $previousViews = (int) ($row['total_events'] ?? 0);
        } else { $previousViews = 0; }
        if ($sql->db_Select('mavex_stats_events', 'COUNT(DISTINCT visitor_hash) AS total_visitors', $previousWhere)) {
            $row = $sql->db_Fetch();
            $previousVisitors = (int) ($row['total_visitors'] ?? 0);
        } else { $previousVisitors = 0; }
        $comparison = array('views' => $previousViews, 'visitors' => $previousVisitors, 'label' => 'Előző azonos hosszúságú időszakhoz képest');
    }
    if ($sql->db_Select('mavex_stats_events', 'MIN(event_time) AS first_event')) {
        $row = $sql->db_Fetch();
        if (!empty($row['first_event'])) { $trackingStarted = date('Y-m-d H:i', (int) $row['first_event']); }
    }
    $sql->db_Select('mavex_stats_events', 'page_path, COUNT(*) AS page_views, COUNT(DISTINCT visitor_hash) AS unique_visitors', $where . ' GROUP BY page_path ORDER BY page_views DESC LIMIT ' . $mstatsMaxRows);
    while ($row = $sql->db_Fetch()) {
        $topPages[] = array('path' => (string) $row['page_path'], 'views' => (int) $row['page_views'], 'visitors' => (int) $row['unique_visitors']);
    }
    $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $currentHost = preg_replace('/:\d+$/', '', $currentHost);
    $hostVariants = array_filter(array_unique(array($currentHost, preg_replace('/^www\./', '', $currentHost), 'www.' . preg_replace('/^www\./', '', $currentHost))));
    $hostSql = array();
    foreach ($hostVariants as $hostVariant) { $hostSql[] = "'" . addslashes($hostVariant) . "'"; }
    $excludeOwnHost = $hostSql ? ' AND referrer_host NOT IN (' . implode(',', $hostSql) . ')' : '';
    $sql->db_Select('mavex_stats_events', 'referrer_host, COUNT(*) AS visits', $where . " AND referrer_host <> ''" . $excludeOwnHost . ' GROUP BY referrer_host ORDER BY visits DESC LIMIT ' . $mstatsMaxRows);
    while ($row = $sql->db_Fetch()) {
        $topReferrers[] = array('host' => (string) $row['referrer_host'], 'visits' => (int) $row['visits']);
    }
    // Peak weekday and hour summaries for the selected reporting period.
    $weekdayStats = array();
    $hourStats = array();
    $sql->db_Select('mavex_stats_events', 'DAYOFWEEK(FROM_UNIXTIME(event_time)) AS weekday_num, COUNT(*) AS hits', $where . ' GROUP BY weekday_num ORDER BY weekday_num ASC');
    while ($row = $sql->db_Fetch()) {
        $weekdayStats[(int) $row['weekday_num']] = (int) $row['hits'];
    }
    $sql->db_Select('mavex_stats_events', 'HOUR(FROM_UNIXTIME(event_time)) AS hour_num, COUNT(*) AS hits', $where . ' GROUP BY hour_num ORDER BY hour_num ASC');
    while ($row = $sql->db_Fetch()) {
        $hourStats[(int) $row['hour_num']] = (int) $row['hits'];
    }

    // Main chart buckets: hours for a day, days for a month, months for a year.
    $chartSql = $chartSqlKey . ' AS bucket_key, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors';
    $sql->db_Select('mavex_stats_events', $chartSql, $chartWhere . ' GROUP BY bucket_key ORDER BY bucket_key ASC');
    $chartMap = array();
    while ($row = $sql->db_Fetch()) {
        $chartMap[(string) $row['bucket_key']] = array('views' => (int) $row['views'], 'visitors' => (int) $row['visitors']);
    }
    $dailyStats = array();
    for ($i = 0; $i < $chartBucketCount; $i++) {
        if ($chartView === 'day') {
            $key = (string) $i;
            $label = sprintf('%02d', $i);
        } elseif ($chartView === 'month') {
            $bucketDate = clone $chartDateObject;
            $bucketDate->modify('+' . $i . ' days');
            $key = $bucketDate->format('Y-m-d');
            $label = $bucketDate->format('m.d');
        } else {
            $bucketDate = clone $chartDateObject;
            $bucketDate->modify('+' . $i . ' months');
            $key = $bucketDate->format('Y-m');
            $label = $bucketDate->format('m.');
        }
        $dailyStats[] = array('day' => $key, 'label' => $label, 'views' => $chartMap[$key]['views'] ?? 0, 'visitors' => $chartMap[$key]['visitors'] ?? 0);
    }
    // Weekly and monthly trend summaries from the latest 180 days.
    $trendStart = strtotime('-179 days', $todayStart);
    $trendWhere = 'event_time >= ' . (int) $trendStart . ' AND event_time <= ' . (int) $now;
    $sql->db_Select('mavex_stats_events', 'DATE(FROM_UNIXTIME(event_time)) AS event_day, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors', $trendWhere . ' GROUP BY event_day ORDER BY event_day ASC');
    $weeklyTrend = array();
    $monthlyTrend = array();
    while ($row = $sql->db_Fetch()) {
        $dayTs = strtotime((string) $row['event_day']);
        $weekKey = date('o-W', $dayTs);
        $monthKey = date('Y-m', $dayTs);
        if (!isset($weeklyTrend[$weekKey])) { $weeklyTrend[$weekKey] = array('views' => 0, 'visitors' => array()); }
        if (!isset($monthlyTrend[$monthKey])) { $monthlyTrend[$monthKey] = array('views' => 0, 'visitors' => array()); }
        $weeklyTrend[$weekKey]['views'] += (int) $row['views'];
        $monthlyTrend[$monthKey]['views'] += (int) $row['views'];
    }
    // Use distinct visitor counts grouped by period for accurate rollups.
    $sql->db_Select('mavex_stats_events', 'visitor_hash, event_time', $trendWhere);
    while ($row = $sql->db_Fetch()) {
        $dayTs = (int) $row['event_time'];
        $visitorHash = (string) $row['visitor_hash'];
        $weekKey = date('o-W', $dayTs);
        $monthKey = date('Y-m', $dayTs);
        if ($visitorHash !== '') {
            $weeklyTrend[$weekKey]['visitors'][$visitorHash] = true;
            $monthlyTrend[$monthKey]['visitors'][$visitorHash] = true;
        }
    }
    foreach ($weeklyTrend as $key => $item) { $weeklyTrend[$key]['visitors'] = count($item['visitors']); }
    foreach ($monthlyTrend as $key => $item) { $monthlyTrend[$key]['visitors'] = count($item['visitors']); }

    // Browser, device and screen-size summaries use fields already collected by the plugin.
    $sql->db_Select('mavex_stats_events', 'user_agent, screen_size, COUNT(*) AS hits', $where . ' GROUP BY user_agent, screen_size');
    while ($row = $sql->db_Fetch()) {
        $ua = (string) ($row['user_agent'] ?? '');
        $screenSize = (string) ($row['screen_size'] ?? '');
        $hits = (int) ($row['hits'] ?? 0);

        if (preg_match('/Edg\//i', $ua)) { $browser = 'Microsoft Edge'; }
        elseif (preg_match('/OPR\//i', $ua)) { $browser = 'Opera'; }
        elseif (preg_match('/Firefox\//i', $ua)) { $browser = 'Firefox'; }
        elseif (preg_match('/Chrome\//i', $ua) && !preg_match('/Edg\//i', $ua)) { $browser = 'Google Chrome'; }
        elseif (preg_match('/Safari\//i', $ua) && !preg_match('/Chrome\//i', $ua)) { $browser = 'Safari'; }
        elseif (preg_match('/MSIE|Trident\//i', $ua)) { $browser = 'Internet Explorer'; }
        else { $browser = 'Egyéb / nem azonosított'; }
        $browserStats[$browser] = ($browserStats[$browser] ?? 0) + $hits;

        if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua) || (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua))) { $device = 'Tablet'; }
        elseif (preg_match('/Mobile|iPhone|iPod|Windows Phone/i', $ua) || (preg_match('/Android/i', $ua) && preg_match('/Mobile/i', $ua))) { $device = 'Mobil'; }
        elseif ($ua !== '') { $device = 'Asztali gép'; }
        else { $device = 'Nem azonosított'; }
        $deviceStats[$device] = ($deviceStats[$device] ?? 0) + $hits;

        if (preg_match('/Windows NT 10/i', $ua)) { $os = 'Windows 10/11'; }
        elseif (preg_match('/Windows NT 6\.3/i', $ua)) { $os = 'Windows 8.1'; }
        elseif (preg_match('/Windows NT 6\.1/i', $ua)) { $os = 'Windows 7'; }
        elseif (preg_match('/Android/i', $ua)) { $os = 'Android'; }
        elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) { $os = 'iOS / iPadOS'; }
        elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) { $os = 'macOS'; }
        elseif (preg_match('/Linux/i', $ua)) { $os = 'Linux'; }
        elseif (preg_match('/CrOS/i', $ua)) { $os = 'ChromeOS'; }
        elseif ($ua !== '') { $os = 'Egyéb / nem azonosított'; }
        else { $os = 'Nem azonosított'; }
        $osStats[$os] = ($osStats[$os] ?? 0) + $hits;

        if (preg_match('/^\d{1,5}x\d{1,5}$/', $screenSize)) {
            $screenStats[$screenSize] = ($screenStats[$screenSize] ?? 0) + $hits;
        }
    }
    arsort($browserStats);
    arsort($deviceStats);
    arsort($osStats);
    arsort($screenStats);
} catch (Throwable $e) {
    $dbError = true;
    e107::getMessage()->addError(LAN_MAVEX_STATS_DB_ERROR);
}

$fmt = static function ($number) { return number_format((int) $number, 0, ',', ' '); };
$h = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$periodLabels = array('today' => 'Ma', '7' => '7 nap', '30' => '30 nap', '90' => '90 nap', 'all' => 'Összes');
$periodLinks = '<nav class="mstats-periods btn-group" aria-label="Időszak kiválasztása">';
foreach ($periodLabels as $key => $label) {
    $active = $period === $key ? ' is-active' : '';
    $current = $period === $key ? ' aria-current="page"' : '';
    $periodLinks .= '<a class="btn btn-sm ' . ($period === $key ? 'btn-primary' : 'btn-default') . ' mstats-period' . $active . '"' . $current . ' href="' . e_SELF . '?period=' . rawurlencode($key) . '">' . $label . '</a>';
}
$periodLinks .= '</nav>';

$metrics = array(
    array('Egyedi látogatók', $fmt($periodVisitors), 'fa-users'),
    array('Oldalmegtekintések', $fmt($periodEvents), 'fa-eye'),
    array('Összes megtekintés', $fmt($totalEvents), 'fa-bar-chart')
);
$cards = '';
foreach ($metrics as $metric) {
    $cards .= '<div class="col-xs-12 col-sm-4"><div class="panel mstats-kpi"><div class="panel-body"><div class="mstats-kpi-icon"><i class="' . $metric[2] . '"></i></div><div class="mstats-kpi-label">' . $metric[0] . '</div><div class="mstats-kpi-value">' . $metric[1] . '</div></div></div></div>';
}

$chartWidth = 1000;
$chartHeight = 230;
$chartLeft = 58;
$chartRight = 24;
$chartTop = 20;
$chartBottom = 42;
$chartPlotWidth = $chartWidth - $chartLeft - $chartRight;
$chartPlotHeight = $chartHeight - $chartTop - $chartBottom;
$chartMaxValue = 1;
foreach ($dailyStats as $day) {
    $chartMaxValue = max($chartMaxValue, $day['views'], $day['visitors']);
}
$chartScaleMax = max(4, (int) (ceil($chartMaxValue / 4) * 4));
$chartPointX = static function ($index, $count) use ($chartLeft, $chartPlotWidth) {
    return $count <= 1 ? $chartLeft + ($chartPlotWidth / 2) : $chartLeft + ($index * ($chartPlotWidth / ($count - 1)));
};
$chartPointY = static function ($value) use ($chartTop, $chartPlotHeight, $chartScaleMax) {
    return $chartTop + $chartPlotHeight - (($value / $chartScaleMax) * $chartPlotHeight);
};
$viewPoints = array();
$visitorPoints = array();
$chartLabels = '';
$viewDots = '';
$visitorDots = '';
$dayCount = count($dailyStats);
$labelEvery = max(1, (int) ceil($dayCount / 12));
foreach ($dailyStats as $index => $day) {
    $x = $chartPointX($index, $dayCount);
    $viewY = $chartPointY($day['views']);
    $visitorY = $chartPointY($day['visitors']);
    $viewPoints[] = round($x, 2) . ',' . round($viewY, 2);
    $visitorPoints[] = round($x, 2) . ',' . round($visitorY, 2);
    $title = $h($day['label'] . ': ' . $fmt($day['views']) . ' oldalmegtekintés, ' . $fmt($day['visitors']) . ' egyedi látogató');
    $viewDots .= '<circle cx="' . round($x, 2) . '" cy="' . round($viewY, 2) . '" r="3.5" fill="#438cf5"><title>' . $title . '</title></circle>';
    $visitorDots .= '<circle cx="' . round($x, 2) . '" cy="' . round($visitorY, 2) . '" r="3.5" fill="#20b8a6"><title>' . $title . '</title></circle>';
    if ($index % $labelEvery === 0 || $index === $dayCount - 1) {
        $chartLabels .= '<text x="' . round($x, 2) . '" y="' . ($chartHeight - 22) . '" text-anchor="middle" fill="#b9c1ca" font-size="11">' . $h($day['label']) . '</text>';
    }
}
$chart = '<svg class="mstats-line-chart" viewBox="0 0 ' . $chartWidth . ' ' . $chartHeight . '" role="img" aria-label="Napi oldalmegtekintések és egyedi látogatók vonaldiagramja" xmlns="http://www.w3.org/2000/svg">';
for ($tick = 0; $tick <= 4; $tick++) {
    $value = $chartScaleMax - ($tick * ($chartScaleMax / 4));
    $y = $chartTop + ($tick * ($chartPlotHeight / 4));
    $chart .= '<line x1="' . $chartLeft . '" y1="' . round($y, 2) . '" x2="' . ($chartWidth - $chartRight) . '" y2="' . round($y, 2) . '" stroke="#555d66" stroke-width="1" />';
    $chart .= '<text x="' . ($chartLeft - 12) . '" y="' . round($y + 4, 2) . '" text-anchor="end" fill="#b9c1ca" font-size="11">' . $fmt($value) . '</text>';
}
$chart .= '<line x1="' . $chartLeft . '" y1="' . ($chartTop + $chartPlotHeight) . '" x2="' . ($chartWidth - $chartRight) . '" y2="' . ($chartTop + $chartPlotHeight) . '" stroke="#747d87" stroke-width="1" />';
if ($viewPoints) {
    $chart .= '<polyline points="' . implode(' ', $viewPoints) . '" fill="none" stroke="#438cf5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />';
    $chart .= '<polyline points="' . implode(' ', $visitorPoints) . '" fill="none" stroke="#20b8a6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />';
}
$chart .= $viewDots . $visitorDots . $chartLabels . '</svg>';

$changeLabel = static function ($current, $previous) {
    if ($previous === null) { return ''; }
    if ((int) $previous === 0) { return (int) $current > 0 ? 'Új' : '0%'; }
    $change = (($current - $previous) / $previous) * 100;
    return ($change > 0 ? '+' : '') . number_format($change, 1, ',', ' ') . '%';
};
$weeklyRows = '';
foreach (array_slice(array_reverse($weeklyTrend, true), 0, 8, true) as $key => $item) {
    $weeklyRows .= '<tr><td>' . $h($key) . '</td><td class="text-right">' . $fmt($item['views']) . '</td><td class="text-right">' . $fmt($item['visitors']) . '</td></tr>';
}
$monthlyRows = '';
foreach (array_slice(array_reverse($monthlyTrend, true), 0, 6, true) as $key => $item) {
    $monthlyRows .= '<tr><td>' . $h(date('Y. m.', strtotime($key . '-01'))) . '</td><td class="text-right">' . $fmt($item['views']) . '</td><td class="text-right">' . $fmt($item['visitors']) . '</td></tr>';
}
if ($weeklyRows === '') { $weeklyRows = '<tr><td colspan="3" class="text-muted text-center">Még nincs elegendő adat.</td></tr>'; }
if ($monthlyRows === '') { $monthlyRows = '<tr><td colspan="3" class="text-muted text-center">Még nincs elegendő adat.</td></tr>'; }
$comparisonHtml = '';
if ($comparison['views'] !== null) {
    $comparisonHtml = '<div class="mstats-comparison-note">Előző időszakhoz képest: oldalmegtekintés <strong>' . $h($changeLabel($periodEvents, $comparison['views'])) . '</strong> · egyedi látogató <strong>' . $h($changeLabel($periodVisitors, $comparison['visitors'])) . '</strong></div>';
}
$pageRows = '';
if ($topPages) {
    $max = max(array_column($topPages, 'views'));
    foreach ($topPages as $page) {
        $width = $max > 0 ? round(($page['views'] / $max) * 100) : 0;
        $pageRows .= '<tr><td><code>' . $h($page['path']) . '</code><div class="mstats-bar"><span style="width:' . $width . '%"></span></div></td><td class="text-right">' . $fmt($page['views']) . '</td><td class="text-right">' . $fmt($page['visitors']) . '</td></tr>';
    }
} else { $pageRows = '<tr><td colspan="3" class="text-muted text-center">' . LAN_MAVEX_STATS_NO_DATA . '</td></tr>'; }

$weekdayNames = array(1 => 'Vasárnap', 2 => 'Hétfő', 3 => 'Kedd', 4 => 'Szerda', 5 => 'Csütörtök', 6 => 'Péntek', 7 => 'Szombat');
$buildColumnChart = static function ($labels, $values, $ariaLabel) use ($fmt) {
    $count = count($labels);
    $width = 760;
    $height = 250;
    $left = 48;
    $right = 14;
    $top = 22;
    $bottom = 48;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    $maxValue = max(1, $values ? max($values) : 0);
    $scaleMax = (int) (ceil($maxValue / 4) * 4);
    if ($scaleMax < 4) { $scaleMax = 4; }
    $step = $scaleMax / 4;
    $slot = $plotWidth / max(1, $count);
    $barWidth = min(42, $slot * 0.58);
    $svg = '<svg class="mstats-column-chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '" xmlns="http://www.w3.org/2000/svg">';
    for ($tick = 0; $tick <= 4; $tick++) {
        $value = $scaleMax - ($tick * $step);
        $y = $top + ($tick * ($plotHeight / 4));
        $svg .= '<line x1="' . $left . '" y1="' . $y . '" x2="' . ($width - $right) . '" y2="' . $y . '" stroke="#555d66" stroke-width="1" />';
        $svg .= '<text x="' . ($left - 9) . '" y="' . ($y + 4) . '" text-anchor="end" fill="#b9c1ca" font-size="11">' . $fmt($value) . '</text>';
    }
    foreach ($values as $index => $value) {
        $value = (int) $value;
        $barHeight = $value > 0 ? ($value / $scaleMax) * $plotHeight : 0;
        $x = $left + ($index * $slot) + (($slot - $barWidth) / 2);
        $y = $top + $plotHeight - $barHeight;
        $label = (string) $labels[$index];
        $svg .= '<g><title>' . htmlspecialchars($label . ': ' . $fmt($value) . ' megtekintés', ENT_QUOTES, 'UTF-8') . '</title>';
        if ($value > 0) {
            $svg .= '<rect x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($barHeight, 2) . '" rx="2" fill="#287cf0" />';
            $svg .= '<text x="' . round($x + ($barWidth / 2), 2) . '" y="' . max(13, round($y - 6, 2)) . '" text-anchor="middle" fill="#e5eaf0" font-size="11">' . $fmt($value) . '</text>';
        }
        $svg .= '<text x="' . round($x + ($barWidth / 2), 2) . '" y="' . ($height - 17) . '" text-anchor="middle" fill="#b9c1ca" font-size="' . ($count > 12 ? '9' : '10') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</text>';
        $svg .= '</g>';
    }
    $svg .= '<line x1="' . $left . '" y1="' . ($top + $plotHeight) . '" x2="' . ($width - $right) . '" y2="' . ($top + $plotHeight) . '" stroke="#747d87" stroke-width="1" />';
    $svg .= '</svg>';
    return $svg;
};

$weekdayLabels = array();
$weekdayValues = array();
foreach ($weekdayNames as $dayNo => $dayName) {
    $weekdayLabels[] = $dayName;
    $weekdayValues[] = (int) ($weekdayStats[$dayNo] ?? 0);
}
$hourLabels = array();
$hourValues = array();
for ($hour = 0; $hour < 24; $hour++) {
    $hourLabels[] = sprintf('%02d', $hour);
    $hourValues[] = (int) ($hourStats[$hour] ?? 0);
}
$weekdayChart = $buildColumnChart($weekdayLabels, $weekdayValues, 'Forgalom a hét napjai szerint');
$hourChart = $buildColumnChart($hourLabels, $hourValues, 'Forgalom a nap 24 órájában');
$peakWeekday = $weekdayStats ? array_search(max($weekdayStats), $weekdayStats, true) : null;
$peakHour = $hourStats ? array_search(max($hourStats), $hourStats, true) : null;
$peakSummary = '<div class="mstats-peak-summary">';
$peakSummary .= '<span>Legforgalmasabb nap: <strong>' . ($peakWeekday !== null ? $h($weekdayNames[$peakWeekday]) : '—') . '</strong></span>';
$peakSummary .= '<span>Legforgalmasabb óra: <strong>' . ($peakHour !== null ? sprintf('%02d:00–%02d:59', $peakHour, $peakHour) : '—') . '</strong></span>';
$peakSummary .= '</div>';

$refRows = '';
if ($topReferrers) {
    foreach ($topReferrers as $ref) {
        $refRows .= '<tr><td>' . $h($ref['host']) . '</td><td class="text-right">' . $fmt($ref['visits']) . '</td></tr>';
    }
} else { $refRows = '<tr><td colspan="2" class="text-muted text-center">Nincs külső hivatkozás adat.</td></tr>'; }

$chartViewLabels = array('day' => 'Nap', 'month' => 'Hónap', 'year' => 'Év');
$chartViewLinks = '<div class="mstats-chart-views btn-group" role="group" aria-label="Grafikon nézete">';
foreach ($chartViewLabels as $viewKey => $viewLabel) {
    $viewActive = $chartView === $viewKey;
    // Changing granularity starts at the current day/month/year, not at the
    // first day of the previously selected year or month.
    $chartViewLinks .= '<a class="btn btn-sm ' . ($viewActive ? 'btn-primary' : 'btn-default') . '" href="' . $h($chartUrl($viewKey, date('Y-m-d', $now))) . '"' . ($viewActive ? ' aria-current="page"' : '') . '>' . $viewLabel . '</a>';
}
$chartViewLinks .= '</div>';
$chartStepName = $chartView === 'day' ? 'nap' : ($chartView === 'month' ? 'hónap' : 'év');
$chartNavigation = '<div class="mstats-chart-navigation">' . $chartViewLinks;
$chartNavigation .= '<div class="mstats-chart-stepper"><a class="btn btn-sm btn-default" href="' . $h($chartUrl($chartView, $chartPrevDate->format('Y-m-d'))) . '" aria-label="Előző ' . $chartStepName . '">&lsaquo; Előző ' . $chartStepName . '</a>';
$chartNavigation .= '<strong>' . $h($chartHeading) . '</strong>';
$chartCurrentHeading = $chartView === 'day' ? date('Y-m-d', $now) : ($chartView === 'month' ? date('Y-m-01', $now) : date('Y-01-01', $now));
$chartCurrentUrl = $chartUrl($chartView, $chartCurrentHeading);
if ($chartView === 'day') {
    $isCurrentChartPeriod = $chartDateObject->format('Y-m-d') === date('Y-m-d', $now);
} elseif ($chartView === 'month') {
    $isCurrentChartPeriod = $chartDateObject->format('Y-m') === date('Y-m', $now);
} else {
    $isCurrentChartPeriod = $chartDateObject->format('Y') === date('Y', $now);
}
if (!$isCurrentChartPeriod) {
    $chartNavigation .= '<a class="btn btn-sm btn-default" href="' . $h($chartCurrentUrl) . '">Aktuális</a>';
}
if ($chartCanGoNext) {
    $chartNavigation .= '<a class="btn btn-sm btn-default" href="' . $h($chartUrl($chartView, $chartNextDate->format('Y-m-d'))) . '" aria-label="Következő ' . $chartStepName . '">Következő ' . $chartStepName . ' &rsaquo;</a>';
} else {
    $chartNavigation .= '<button type="button" class="btn btn-sm btn-default" disabled>Következő ' . $chartStepName . ' &rsaquo;</button>';
}
$chartNavigation .= '</div></div>';

$text = '<div class="mstats-dashboard">';
$text .= '<div class="mstats-titlebar"><div><div class="mstats-title-kicker">MAVEX TEREM · WEBANALITIKA</div><h2>' . LAN_MAVEX_STATS_DASHBOARD . '</h2><div class="mstats-title-subtitle">' . LAN_MAVEX_STATS_DASHBOARD_INTRO . '</div></div><div class="mstats-title-meta"><span class="label label-default">' . LAN_MAVEX_STATS_TRACKING_SINCE . ': ' . ($trackingStarted ? $h($trackingStarted) : LAN_MAVEX_STATS_NOT_STARTED) . '</span></div></div>';
$text .= '<div class="well mstats-toolbar"><div><div class="mstats-section-kicker">KIMUTATÁSI IDŐSZAK</div><div class="mstats-current-period">' . $periodLabels[$period] . '</div></div>' . $periodLinks . '</div>';
$text .= '<div class="row mstats-kpi-row">' . $cards . '</div>' . $comparisonHtml;
$chartCaption = 'Bontásban látható az oldalmegtekintések és az egyedi látogatók száma.';
$chartTitle = 'Látogatottság';
$text .= '<section class="panel mstats-panel mstats-chart-panel"><div class="panel-heading mstats-panel-heading"><div><div class="mstats-section-kicker">FORGALOM IDŐSZAK SZERINT</div><h3>' . $chartTitle . '</h3></div><span class="mstats-chart-legend"><i class="mstats-legend-views"></i> Oldalmegtekintések &nbsp; <i class="mstats-legend-visitors"></i> Egyedi látogatók</span></div><div class="panel-body">' . $chartNavigation . '<div class="mstats-line-chart-wrap">' . $chart . '</div><div class="mstats-chart-caption">' . $chartCaption . '</div></div></section>';
if (!empty($mstatsPrefs['show_peaks'])) { $text .= '<div class="row mstats-peak-row"><div class="col-xs-12">' . $peakSummary . '</div>';
$text .= '<div class="col-xs-12 col-md-6"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Forgalom a hét napjai szerint</h3></div><div class="panel-body mstats-chart-panel-body">' . $weekdayChart . '</div></div></div>';
$text .= '<div class="col-xs-12 col-md-6"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Forgalom a nap 24 órájában</h3></div><div class="panel-body mstats-chart-panel-body">' . $hourChart . '</div></div></div></div>';
 }
$text .= '<div class="row mstats-tables-row">';
if (!empty($mstatsPrefs['show_pages'])) { $text .= '<div class="col-xs-12 col-lg-7"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>' . LAN_MAVEX_STATS_TOP_PAGES . '</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>' . LAN_MAVEX_STATS_PAGE . '</th><th class="text-right">' . LAN_MAVEX_STATS_PAGE_VIEWS . '</th><th class="text-right">Egyedi látogatók</th></tr></thead><tbody>' . $pageRows . '</tbody></table></div></div></div>';
 }
if (!empty($mstatsPrefs['show_referrers'])) { $text .= '<div class="col-xs-12 col-lg-5"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Külső hivatkozások</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>Domain</th><th class="text-right">Látogatás</th></tr></thead><tbody>' . $refRows . '</tbody></table></div></div></div>';
 }
$text .= '</div>';
$makeBreakdownRows = static function ($items, $fmt, $h) {
    if (!$items) { return '<tr><td colspan="2" class="text-muted text-center">Még nincs elegendő adat.</td></tr>'; }
    $rows = '';
    foreach ($items as $label => $count) {
        $rows .= '<tr><td>' . $h($label) . '</td><td class="text-right">' . $fmt($count) . '</td></tr>';
    }
    return $rows;
};
$browserRows = $makeBreakdownRows($browserStats, $fmt, $h);
$deviceRows = $makeBreakdownRows($deviceStats, $fmt, $h);
$screenRows = $makeBreakdownRows(array_slice($screenStats, 0, 6, true), $fmt, $h);
$text .= '<div class="row mstats-breakdown-row">';
if (!empty($mstatsPrefs['show_browsers'])) { $text .= '<div class="col-xs-12 col-md-3"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Böngészők</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>Böngésző</th><th class="text-right">Megtekintés</th></tr></thead><tbody>' . $browserRows . '</tbody></table></div></div></div>'; }
if (!empty($mstatsPrefs['show_os'])) { $osRows = $makeBreakdownRows($osStats, $fmt, $h); $text .= '<div class="col-xs-12 col-md-3"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Operációs rendszerek</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>Rendszer</th><th class="text-right">Megtekintés</th></tr></thead><tbody>' . $osRows . '</tbody></table></div></div></div>'; }
if (!empty($mstatsPrefs['show_devices'])) { $text .= '<div class="col-xs-12 col-md-3"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Eszköztípusok</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>Eszköz</th><th class="text-right">Megtekintés</th></tr></thead><tbody>' . $deviceRows . '</tbody></table></div></div></div>'; }
if (!empty($mstatsPrefs['show_screens'])) { $text .= '<div class="col-xs-12 col-md-3"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Képernyőméretek</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>Felbontás</th><th class="text-right">Megtekintés</th></tr></thead><tbody>' . $screenRows . '</tbody></table></div></div></div>'; }
$text .= '</div>';
if (!empty($mstatsPrefs['show_trends'])) { $text .= '<div class="row mstats-trend-tables">';
$text .= '<div class="col-xs-12 col-md-6"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Heti összesítés – utolsó 180 nap</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>Hét</th><th class="text-right">Megtekintés</th><th class="text-right">Egyedi látogató</th></tr></thead><tbody>' . $weeklyRows . '</tbody></table></div></div></div>';
$text .= '<div class="col-xs-12 col-md-6"><div class="panel mstats-panel"><div class="panel-heading mstats-panel-heading compact"><h3>Havi összesítés – utolsó 180 nap</h3></div><div class="table-responsive"><table class="table table-striped table-hover mstats-table"><thead><tr><th>Hónap</th><th class="text-right">Megtekintés</th><th class="text-right">Egyedi látogató</th></tr></thead><tbody>' . $monthlyRows . '</tbody></table></div></div></div></div>'; }
$text .= '<p class="small text-muted mstats-privacy-note">' . LAN_MAVEX_STATS_PRIVACY_NOTE . '</p></div>';

$mstatsUiTranslations = array(
    'Előző azonos hosszúságú időszakhoz képest' => LAN_MAVEX_STATS_COMPARISON_PERIOD,
    'Egyéb / nem azonosított' => LAN_MAVEX_STATS_UNKNOWN_OTHER,
    'Nem azonosított' => LAN_MAVEX_STATS_UNKNOWN,
    'Időszak kiválasztása' => LAN_MAVEX_STATS_PERIOD_SELECT,
    'Egyedi látogatók' => LAN_MAVEX_STATS_UNIQUE_VISITORS,
    'Oldalmegtekintések' => LAN_MAVEX_STATS_PAGE_VIEWS,
    'Összes megtekintés' => LAN_MAVEX_STATS_TOTAL_VIEWS,
    'Új' => LAN_MAVEX_STATS_NEW,
    'Előző időszakhoz képest: oldalmegtekintés ' => LAN_MAVEX_STATS_COMPARISON_VIEWS_PREFIX,
    ' · egyedi látogató ' => LAN_MAVEX_STATS_COMPARISON_VISITORS_PREFIX,
    'Vasárnap' => LAN_MAVEX_STATS_SUNDAY,
    'Hétfő' => LAN_MAVEX_STATS_MONDAY,
    'Kedd' => LAN_MAVEX_STATS_TUESDAY,
    'Szerda' => LAN_MAVEX_STATS_WEDNESDAY,
    'Csütörtök' => LAN_MAVEX_STATS_THURSDAY,
    'Péntek' => LAN_MAVEX_STATS_FRIDAY,
    'Szombat' => LAN_MAVEX_STATS_SATURDAY,
    'Forgalom a hét napjai szerint' => LAN_MAVEX_STATS_WEEKDAY_TRAFFIC,
    'Forgalom a nap 24 órájában' => LAN_MAVEX_STATS_HOURLY_TRAFFIC,
    'Legforgalmasabb nap: ' => LAN_MAVEX_STATS_BUSIEST_DAY_PREFIX,
    'Legforgalmasabb óra: ' => LAN_MAVEX_STATS_BUSIEST_HOUR_PREFIX,
    'Nincs külső hivatkozás adat.' => LAN_MAVEX_STATS_NO_REFERRER_DATA,
    'Nap' => LAN_MAVEX_STATS_DAY,
    'Hónap' => LAN_MAVEX_STATS_MONTH,
    'Év' => LAN_MAVEX_STATS_YEAR,
    'Grafikon nézete' => LAN_MAVEX_STATS_CHART_VIEW,
    'nap' => LAN_MAVEX_STATS_DAY_LOWER,
    'hónap' => LAN_MAVEX_STATS_MONTH_LOWER,
    'év' => LAN_MAVEX_STATS_YEAR_LOWER,
    'Előző ' => LAN_MAVEX_STATS_PREVIOUS_PREFIX,
    'Aktuális' => LAN_MAVEX_STATS_CURRENT,
    'Következő ' => LAN_MAVEX_STATS_NEXT_PREFIX,
    'MAVEX TEREM · WEBANALITIKA' => LAN_MAVEX_STATS_BRAND_KICKER,
    'KIMUTATÁSI IDŐSZAK' => LAN_MAVEX_STATS_REPORTING_PERIOD,
    'Bontásban látható az oldalmegtekintések és az egyedi látogatók száma.' => LAN_MAVEX_STATS_CHART_CAPTION,
    'Látogatottság' => LAN_MAVEX_STATS_TRAFFIC,
    'FORGALOM IDŐSZAK SZERINT' => LAN_MAVEX_STATS_TRAFFIC_BY_PERIOD,
    'Külső hivatkozások' => LAN_MAVEX_STATS_REFERRERS,
    'Domain' => LAN_MAVEX_STATS_DOMAIN,
    'Látogatás' => LAN_MAVEX_STATS_VISITS,
    'Még nincs elegendő adat.' => LAN_MAVEX_STATS_INSUFFICIENT_DATA,
    'Böngészők' => LAN_MAVEX_STATS_BROWSERS,
    'Böngésző' => LAN_MAVEX_STATS_BROWSER,
    'Megtekintés' => LAN_MAVEX_STATS_VIEWS,
    'Operációs rendszerek' => LAN_MAVEX_STATS_OPERATING_SYSTEMS,
    'Rendszer' => LAN_MAVEX_STATS_SYSTEM,
    'Eszköztípusok' => LAN_MAVEX_STATS_DEVICE_TYPES,
    'Eszköz' => LAN_MAVEX_STATS_DEVICE,
    'Képernyőméretek' => LAN_MAVEX_STATS_SCREEN_SIZES,
    'Felbontás' => LAN_MAVEX_STATS_RESOLUTION,
    'Heti összesítés – utolsó 180 nap' => LAN_MAVEX_STATS_WEEKLY_SUMMARY_180,
    'Havi összesítés – utolsó 180 nap' => LAN_MAVEX_STATS_MONTHLY_SUMMARY_180,
    'Hét' => LAN_MAVEX_STATS_WEEK,
    'Egyedi látogató' => LAN_MAVEX_STATS_UNIQUE_VISITOR,
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
$text = strtr($text, $mstatsUiTranslations);

$ns = e107::getRender();
$ns->tablerender(LAN_MAVEX_STATS_ADMIN_TITLE, $text);
