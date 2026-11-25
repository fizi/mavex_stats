<?php
/** Public MAVEX Stats showcase. Detailed analytics remain admin-only. */
require_once('../../class2.php');
e107::lan('mavex_stats', false, true); // Load the plugin's public frontend language file.


$stats = array(
    'visitors' => 0, 'views' => 0, 'members' => 0
);
$monthly = array();
$sql = e107::getDb();
$now = time();
$currentStart = strtotime(date('Y-m-01 00:00:00', $now));
// KPI comparison windows: rolling last 24 hours versus the preceding 24 hours.
$kpiCurrentStart = $now - 86400;
$kpiPreviousStart = $now - 172800;
$kpiPreviousEnd = $kpiCurrentStart;
$periodStart = strtotime(date('Y-m-01 00:00:00', strtotime('-5 months', $now)));

// Page views and distinct visitors in the last 24 hours.
try {
    if ($sql->db_Select('mavex_stats_events', 'COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors', 'event_time >= ' . (int)$kpiCurrentStart . ' AND event_time < ' . (int)$now)) {
        $r = $sql->db_Fetch();
        $stats['views'] = (int)($r['views'] ?? 0);
        $stats['visitors'] = (int)($r['visitors'] ?? 0);
    }
} catch (Throwable $e) {}

// The preceding 24-hour comparison window.
$previous = array('views' => 0, 'visitors' => 0);
try {
    if ($sql->db_Select('mavex_stats_events', 'COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors', 'event_time >= ' . (int)$kpiPreviousStart . ' AND event_time < ' . (int)$kpiPreviousEnd)) {
        $r = $sql->db_Fetch();
        $previous['views'] = (int)($r['views'] ?? 0);
        $previous['visitors'] = (int)($r['visitors'] ?? 0);
    }
} catch (Throwable $e) {}

// Total registered members; trend compares the current total with the total 24 hours ago.
$previousTotalMembers = 0;
try {
    if ($sql->db_Select('user', 'COUNT(*) AS total_members', 'user_id > 0 AND user_join > 0 AND user_join <= ' . (int)$now)) {
        $r = $sql->db_Fetch();
        $stats['members'] = (int)($r['total_members'] ?? 0);
    }
    if ($sql->db_Select('user', 'COUNT(*) AS total_members', 'user_id > 0 AND user_join > 0 AND user_join <= ' . (int)$kpiCurrentStart)) {
        $r = $sql->db_Fetch();
        $previousTotalMembers = (int)($r['total_members'] ?? 0);
    }
} catch (Throwable $e) {}

// Six monthly values for the chart.
$monthlyMap = array();
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime('-' . $i . ' months', $now));
    $monthlyMap[$key] = 0;
}
try {
    if ($sql->db_Select('mavex_stats_events', "DATE_FORMAT(FROM_UNIXTIME(event_time), '%Y-%m') AS month_key, COUNT(*) AS views", 'event_time >= ' . (int)$periodStart . ' GROUP BY month_key ORDER BY month_key ASC')) {
        while ($r = $sql->db_Fetch()) {
            $key = (string)($r['month_key'] ?? '');
            if (isset($monthlyMap[$key])) { $monthlyMap[$key] = (int)$r['views']; }
        }
    }
} catch (Throwable $e) {}
$monthNames = array(1=>LAN_MAVEX_STATS_PUBLIC_MONTH_1,2=>LAN_MAVEX_STATS_PUBLIC_MONTH_2,3=>LAN_MAVEX_STATS_PUBLIC_MONTH_3,4=>LAN_MAVEX_STATS_PUBLIC_MONTH_4,5=>LAN_MAVEX_STATS_PUBLIC_MONTH_5,6=>LAN_MAVEX_STATS_PUBLIC_MONTH_6,7=>LAN_MAVEX_STATS_PUBLIC_MONTH_7,8=>LAN_MAVEX_STATS_PUBLIC_MONTH_8,9=>LAN_MAVEX_STATS_PUBLIC_MONTH_9,10=>LAN_MAVEX_STATS_PUBLIC_MONTH_10,11=>LAN_MAVEX_STATS_PUBLIC_MONTH_11,12=>LAN_MAVEX_STATS_PUBLIC_MONTH_12);
foreach ($monthlyMap as $key => $views) {
    $ts = strtotime($key . '-01');
    $monthly[] = array('label' => date('Y', $ts) . '. ' . $monthNames[(int)date('n', $ts)], 'views' => $views);
}

$maxViews = 1;
foreach ($monthly as $m) { $maxViews = max($maxViews, $m['views']); }
$fmt = static function($n) { return number_format((int)$n, 0, ',', ' '); };
$growth = static function($current, $prior) {
    if ($prior <= 0) {
        return array('class' => $current > 0 ? 'up' : 'neutral', 'label' => $current > 0 ? LAN_MAVEX_STATS_PUBLIC_NEW : '—');
    }
    $change = (($current - $prior) / $prior) * 100;
    return array(
        'class' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        'label' => ($change > 0 ? '▲ +' : ($change < 0 ? '▼ ' : '— ')) . number_format($change, 1, ',', ' ') . '%'
    );
};
$visitorGrowth = $growth($stats['visitors'], $previous['visitors']);
$viewGrowth = $growth($stats['views'], $previous['views']);
$memberGrowth = ((int)$stats['members'] === (int)$previousTotalMembers)
    ? array('class' => 'neutral', 'label' => '0,0%')
    : $growth($stats['members'], $previousTotalMembers);

// Coordinates for the responsive six-month line chart.
$chartPoints = array();
$chartWidth = 760; $chartTop = 30; $chartBottom = 190;
$chartMax = max(1, $maxViews);
foreach ($monthly as $i => $m) {
    $x = count($monthly) > 1 ? 45 + ($i * 670 / (count($monthly) - 1)) : 380;
    $y = $chartBottom - (($m['views'] / $chartMax) * ($chartBottom - $chartTop));
    $chartPoints[] = array('x' => round($x, 1), 'y' => round($y, 1), 'views' => (int)$m['views'], 'label' => $m['label']);
}
$linePath = '';
foreach ($chartPoints as $i => $point) { $linePath .= ($i === 0 ? 'M' : ' L') . $point['x'] . ' ' . $point['y']; }
$areaPath = $linePath . ' L' . $chartPoints[count($chartPoints)-1]['x'] . ' ' . $chartBottom . ' L' . $chartPoints[0]['x'] . ' ' . $chartBottom . ' Z';
$monthLabel = $monthNames[(int)date('n', $now)];

// Selectable traffic chart: daily (30 days), monthly (12 months), yearly (5 years).
$chartPeriod = isset($_GET['period']) ? (string)$_GET['period'] : 'monthly';
if (!in_array($chartPeriod, array('daily', 'monthly', 'yearly'), true)) {
    $chartPeriod = 'monthly';
}
$chartConfig = array(
    'daily' => array('count' => 30, 'unit' => 'day'),
    'monthly' => array('count' => 12, 'unit' => 'month'),
    'yearly' => array('count' => 5, 'unit' => 'year')
);
$chartCount = $chartConfig[$chartPeriod]['count'];
$chartUnit = $chartConfig[$chartPeriod]['unit'];
$chartMap = array();
for ($i = $chartCount - 1; $i >= 0; $i--) {
    if ($chartUnit === 'day') {
        $ts = strtotime('-' . $i . ' days', strtotime(date('Y-m-d 00:00:00', $now)));
        $key = date('Y-m-d', $ts);
        $label = $monthNames[(int)date('n', $ts)] . ' ' . date('j.', $ts);
    } elseif ($chartUnit === 'month') {
        $ts = strtotime('-' . $i . ' months', strtotime(date('Y-m-01 00:00:00', $now)));
        $key = date('Y-m', $ts);
        $label = date('Y', $ts) . '. ' . $monthNames[(int)date('n', $ts)];
    } else {
        $ts = strtotime('-' . $i . ' years', strtotime(date('Y-01-01 00:00:00', $now)));
        $key = date('Y', $ts);
        $label = $key;
    }
    $chartMap[$key] = array('label' => $label, 'views' => 0, 'visitors' => 0);
}
if ($chartUnit === 'day') {
    $chartStart = strtotime('-' . ($chartCount - 1) . ' days', strtotime(date('Y-m-d 00:00:00', $now)));
    $dateFormat = '%Y-%m-%d';
} elseif ($chartUnit === 'month') {
    $chartStart = strtotime('-' . ($chartCount - 1) . ' months', strtotime(date('Y-m-01 00:00:00', $now)));
    $dateFormat = '%Y-%m';
} else {
    $chartStart = strtotime('-' . ($chartCount - 1) . ' years', strtotime(date('Y-01-01 00:00:00', $now)));
    $dateFormat = '%Y';
}
try {
    $chartSql = "DATE_FORMAT(FROM_UNIXTIME(event_time), '" . $dateFormat . "') AS bucket_key, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors";
    if ($sql->db_Select('mavex_stats_events', $chartSql, 'event_time >= ' . (int)$chartStart . ' AND event_time < ' . (int)($now + 1) . ' GROUP BY bucket_key ORDER BY bucket_key ASC')) {
        while ($r = $sql->db_Fetch()) {
            $key = (string)($r['bucket_key'] ?? '');
            if (isset($chartMap[$key])) {
                $chartMap[$key]['views'] = (int)($r['views'] ?? 0);
                $chartMap[$key]['visitors'] = (int)($r['visitors'] ?? 0);
            }
        }
    }
} catch (Throwable $e) {}
$chartData = array_values($chartMap);
$chartMax = 1;
foreach ($chartData as $item) {
    $chartMax = max($chartMax, $item['views'], $item['visitors']);
}
$chartPoints = array();
$chartTop = 30;
$chartBottom = 190;
foreach ($chartData as $i => $item) {
    $x = count($chartData) > 1 ? 45 + ($i * 670 / (count($chartData) - 1)) : 380;
    $viewsY = $chartBottom - (($item['views'] / $chartMax) * ($chartBottom - $chartTop));
    $visitorsY = $chartBottom - (($item['visitors'] / $chartMax) * ($chartBottom - $chartTop));
    $chartPoints[] = array(
        'x' => round($x, 1),
        'viewsY' => round($viewsY, 1),
        'visitorsY' => round($visitorsY, 1),
        'views' => (int)$item['views'],
        'visitors' => (int)$item['visitors'],
        'label' => $item['label']
    );
}
$viewsPath = '';
$visitorsPath = '';
foreach ($chartPoints as $i => $point) {
    $viewsPath .= ($i === 0 ? 'M' : ' L') . $point['x'] . ' ' . $point['viewsY'];
    $visitorsPath .= ($i === 0 ? 'M' : ' L') . $point['x'] . ' ' . $point['visitorsY'];
}
$chartTitle = $chartPeriod === 'daily' ? LAN_MAVEX_STATS_PUBLIC_CHART_DAILY : ($chartPeriod === 'yearly' ? LAN_MAVEX_STATS_PUBLIC_CHART_YEARLY : LAN_MAVEX_STATS_PUBLIC_CHART_MONTHLY);

// Public device, browser and operating-system summaries use the existing
// User-Agent data; no IP address or additional personal data is collected.
$breakdownStart = $now - (30 * 86400);
$deviceStats = array();
$browserStats = array();
$osStats = array();
try {
    if ($sql->db_Select('mavex_stats_events', 'user_agent, COUNT(*) AS hits', 'event_time >= ' . (int)$breakdownStart . ' AND event_time < ' . (int)($now + 1) . ' GROUP BY user_agent')) {
        while ($r = $sql->db_Fetch()) {
            $ua = (string)($r['user_agent'] ?? '');
            $hits = (int)($r['hits'] ?? 0);
            if (preg_match('/Edg\\//i', $ua)) { $browser = 'Microsoft Edge'; }
            elseif (preg_match('/OPR\\//i', $ua)) { $browser = 'Opera'; }
            elseif (preg_match('/Firefox\\//i', $ua)) { $browser = 'Firefox'; }
            elseif (preg_match('/Chrome\\//i', $ua)) { $browser = 'Google Chrome'; }
            elseif (preg_match('/Safari\\//i', $ua)) { $browser = 'Safari'; }
            elseif (preg_match('/MSIE|Trident\\//i', $ua)) { $browser = 'Internet Explorer'; }
            else { $browser = LAN_MAVEX_STATS_PUBLIC_OTHER_UNKNOWN; }
            $browserStats[$browser] = ($browserStats[$browser] ?? 0) + $hits;

            if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua) || (preg_match('/Android/i', $ua) && !preg_match('/Mobile/i', $ua))) { $device = LAN_MAVEX_STATS_PUBLIC_DEVICE_TABLET; }
            elseif (preg_match('/Mobile|iPhone|iPod|Windows Phone/i', $ua) || (preg_match('/Android/i', $ua) && preg_match('/Mobile/i', $ua))) { $device = LAN_MAVEX_STATS_PUBLIC_DEVICE_MOBILE; }
            elseif ($ua !== '') { $device = LAN_MAVEX_STATS_PUBLIC_DEVICE_DESKTOP; }
            else { $device = LAN_MAVEX_STATS_PUBLIC_UNKNOWN; }
            $deviceStats[$device] = ($deviceStats[$device] ?? 0) + $hits;

            if (preg_match('/Windows NT 10/i', $ua)) { $os = 'Windows 10/11'; }
            elseif (preg_match('/Windows NT 6\\.3/i', $ua)) { $os = 'Windows 8.1'; }
            elseif (preg_match('/Windows NT 6\\.1/i', $ua)) { $os = 'Windows 7'; }
            elseif (preg_match('/Android/i', $ua)) { $os = 'Android'; }
            elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) { $os = 'iOS / iPadOS'; }
            elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) { $os = 'macOS'; }
            elseif (preg_match('/CrOS/i', $ua)) { $os = 'ChromeOS'; }
            elseif (preg_match('/Linux/i', $ua)) { $os = 'Linux'; }
            elseif ($ua !== '') { $os = LAN_MAVEX_STATS_PUBLIC_OTHER_UNKNOWN; }
            else { $os = LAN_MAVEX_STATS_PUBLIC_UNKNOWN; }
            $osStats[$os] = ($osStats[$os] ?? 0) + $hits;
        }
    }
} catch (Throwable $e) {}
arsort($deviceStats); arsort($browserStats); arsort($osStats);
$makePublicBreakdown = static function($items, $limit = 5) use ($fmt) {
    $total = array_sum($items);
    if ($total <= 0) { return '<p class="mstats-breakdown-empty">' . LAN_MAVEX_STATS_PUBLIC_INSUFFICIENT_DATA . '</p>'; }
    $html = '';
    $shown = 0;
    foreach ($items as $label => $count) {
        if ($shown >= $limit) { break; }
        $percent = ($count / $total) * 100;
        $html .= '<div class="mstats-breakdown-row"><div class="mstats-breakdown-meta"><span>' . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</span><strong>' . number_format($percent, 1, ',', ' ') . '%</strong></div><div class="mstats-breakdown-track"><span style="width:' . number_format($percent, 2, '.', '') . '%"></span></div></div>';
        $shown++;
    }
    return $html;
};

// Public content library: count public news and list public child chapters.
// Keep the three queries independent so one failed query cannot hide the other cards.
$libraryNewsCount = 0;
$libraryChapters = array();
$libraryNewsUrl = e_BASE . 'news.php';

// Public news: news_class is a CSV-style class field; 0 is the Everyone class.
try {
    $newsWhere = "news_class='0' AND news_start <= " . (int)$now
        . " AND (news_end=0 OR news_end > " . (int)$now . ")"
        . " AND news_render_type=0";
    if ($sql->db_Select('news', 'COUNT(news_id) AS total', $newsWhere)) {
        $newsCountRow = $sql->db_Fetch();
        if ($newsCountRow) {
            $libraryNewsCount = (int)($newsCountRow['total'] ?? 0);
        }
    }
} catch (Throwable $e) {
    $libraryNewsCount = 0;
}

// chapter_visibility is TINYINT in this installation; value 0 means public.
$chapterRows = array();
try {
    if ($sql->db_Select('page_chapters', 'chapter_id, chapter_name, chapter_parent, chapter_sef, chapter_order, chapter_template', 'chapter_visibility=0 ORDER BY chapter_order ASC')) {
        while ($chapterRow = $sql->db_Fetch()) {
            $chapterRows[] = $chapterRow;
        }
    }
} catch (Throwable $e) {
    $chapterRows = array();
}

$chapterMap = array();
foreach ($chapterRows as $chapterRow) {
    $chapterMap[(int)$chapterRow['chapter_id']] = $chapterRow;
}

foreach ($chapterRows as $chapterRow) {
    $chapterId = (int)($chapterRow['chapter_id'] ?? 0);
    $parentId = (int)($chapterRow['chapter_parent'] ?? 0);
    if ($chapterId <= 0 || $parentId <= 0 || empty($chapterRow['chapter_name']) || !isset($chapterMap[$parentId])) {
        continue;
    }

    // Count only pages accessible to anonymous visitors.
    $pageCount = 0;
    try {
        $pageWhere = 'page_chapter=' . $chapterId
            . " AND page_title<>''"
            . " AND FIND_IN_SET('0', page_class)>0"
            . " AND (page_password='' OR page_password IS NULL)";
        if ($sql->db_Select('page', 'COUNT(page_id) AS total', $pageWhere)) {
            $pageCountRow = $sql->db_Fetch();
            if ($pageCountRow) {
                $pageCount = (int)($pageCountRow['total'] ?? 0);
            }
        }
    } catch (Throwable $e) {
        $pageCount = 0;
    }

    $chapterRow['book_sef'] = (string)($chapterMap[$parentId]['chapter_sef'] ?? '');
    $chapterRow['book_id'] = $parentId;
    $chapterRow['book_name'] = (string)($chapterMap[$parentId]['chapter_name'] ?? '');
    $chapterUrl = e107::getUrl()->create('page/chapter/index', $chapterRow, 'allow=chapter_id,chapter_sef,book_sef');

    $libraryChapters[] = array(
        'name' => (string)$chapterRow['chapter_name'],
        'category' => (string)$chapterRow['book_name'],
        'count' => $pageCount,
        'url' => $chapterUrl
    );
}

$libraryCardHtml = '';
$libraryCardHtml .= '<article class="mstats-library-card mstats-library-card-news">'
    . '<div class="mstats-library-card-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M8 7h24l8 8v26H8z"/><path d="M32 7v9h8M14 23h20M14 30h20M14 37h13"/></svg></div>'
    . '<div class="mstats-library-card-body"><h3>' . LAN_MAVEX_STATS_PUBLIC_NEWS . '</h3><p class="mstats-library-category">' . LAN_MAVEX_STATS_PUBLIC_NEWS_DESC . '</p>'
    . '<div class="mstats-library-count"><strong>' . $fmt($libraryNewsCount) . '</strong><span>' . LAN_MAVEX_STATS_PUBLIC_PUBLISHED_NEWS . '</span></div>'
    . '<a class="mstats-library-button" href="' . htmlspecialchars($libraryNewsUrl, ENT_QUOTES, 'UTF-8') . '">' . LAN_MAVEX_STATS_PUBLIC_VIEW . ' <span aria-hidden="true">→</span></a></div></article>';

foreach ($libraryChapters as $chapter) {
    $libraryCardHtml .= '<article class="mstats-library-card">'
        . '<div class="mstats-library-card-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M7 10h13l4 4h17v25H7z"/><path d="M7 18h34"/></svg></div>'
        . '<div class="mstats-library-card-body"><h3>' . htmlspecialchars($chapter['name'], ENT_QUOTES, 'UTF-8') . '</h3>'
        . '<p class="mstats-library-category">' . htmlspecialchars($chapter['category'], ENT_QUOTES, 'UTF-8') . '</p>'
        . '<div class="mstats-library-count"><strong>' . $fmt($chapter['count']) . '</strong><span>' . LAN_MAVEX_STATS_PUBLIC_PUBLISHED_CONTENT . '</span></div>'
        . '<a class="mstats-library-button" href="' . htmlspecialchars($chapter['url'], ENT_QUOTES, 'UTF-8') . '">' . LAN_MAVEX_STATS_PUBLIC_VIEW . ' <span aria-hidden="true">→</span></a></div></article>';
}

e107::css('mavex_stats', 'css/public.css');
require_once(HEADERF);
?>
<div class="mstats-public container-fluid">
  <section class="mstats-hero">
    <div class="mstats-hero-copy"><span class="mstats-hero-mark"><?php echo LAN_MAVEX_STATS_PUBLIC_BRAND; ?></span><h1><?php echo LAN_MAVEX_STATS_PUBLIC_TITLE; ?></h1><p><?php echo LAN_MAVEX_STATS_PUBLIC_INTRO; ?></p></div>
  </section>

  <section class="mstats-activity">
    <div class="mstats-activity-heading"><h2><span class="mstats-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M3 3v18h18M7 14l4-4 3 3 6-7"/></svg></span> <?php echo LAN_MAVEX_STATS_PUBLIC_TRAFFIC; ?> <em>– <?php echo LAN_MAVEX_STATS_PUBLIC_ACTIVITY; ?></em></h2></div>
    <div class="mstats-kpis">
      <article class="mstats-kpi"><span class="mstats-kpi-label"><?php echo LAN_MAVEX_STATS_PUBLIC_VISITORS; ?></span><div class="mstats-kpi-row"><span class="mstats-kpi-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><circle cx="17" cy="17" r="7"/><circle cx="32" cy="19" r="6"/><path d="M4 39c0-8 5-13 13-13s13 5 13 13M27 29c8-2 16 2 17 10"/></svg></span><div class="mstats-kpi-content"><div class="mstats-kpi-main"><strong><?php echo $fmt($stats['visitors']); ?></strong><small class="mstats-growth <?php echo $visitorGrowth['class']; ?>"><?php echo htmlspecialchars($visitorGrowth['label'], ENT_QUOTES, 'UTF-8'); ?></small></div><small class="mstats-kpi-note"><?php echo LAN_MAVEX_STATS_PUBLIC_PREVIOUS_24H; ?></small></div></div></article>
      <article class="mstats-kpi"><span class="mstats-kpi-label"><?php echo LAN_MAVEX_STATS_PUBLIC_PAGEVIEWS; ?></span><div class="mstats-kpi-row"><span class="mstats-kpi-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><rect x="6" y="8" width="36" height="31" rx="3"/><path d="M6 16h36M12 12h2m5 0h2"/></svg></span><div class="mstats-kpi-content"><div class="mstats-kpi-main"><strong><?php echo $fmt($stats['views']); ?></strong><small class="mstats-growth <?php echo $viewGrowth['class']; ?>"><?php echo htmlspecialchars($viewGrowth['label'], ENT_QUOTES, 'UTF-8'); ?></small></div><small class="mstats-kpi-note"><?php echo LAN_MAVEX_STATS_PUBLIC_PREVIOUS_24H; ?></small></div></div></article>
      <article class="mstats-kpi"><span class="mstats-kpi-label"><?php echo LAN_MAVEX_STATS_PUBLIC_MEMBERS; ?></span><div class="mstats-kpi-row"><span class="mstats-kpi-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><circle cx="23" cy="15" r="8"/><path d="M8 40c0-9 6-14 15-14s15 5 15 14"/></svg></span><div class="mstats-kpi-content"><div class="mstats-kpi-main"><strong><?php echo $fmt($stats['members']); ?></strong><small class="mstats-growth <?php echo $memberGrowth['class']; ?>"><?php echo htmlspecialchars($memberGrowth['label'], ENT_QUOTES, 'UTF-8'); ?></small></div><small class="mstats-kpi-note"><?php echo LAN_MAVEX_STATS_PUBLIC_MEMBER_CHANGE; ?></small></div></div></article>
    </div>
    <div class="mstats-traffic-layout">
      <section class="mstats-chart-panel">
        <div class="mstats-chart-heading"><h2><span class="mstats-chart-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M3 3v18h18M7 14l4-4 3 3 6-7"/></svg></span> <?php echo LAN_MAVEX_STATS_PUBLIC_TRAFFIC_TREND; ?> <span>(<?php echo htmlspecialchars($chartTitle, ENT_QUOTES, 'UTF-8'); ?>)</span></h2>
          <nav class="mstats-chart-periods" aria-label="<?php echo LAN_MAVEX_STATS_PUBLIC_CHART_PERIOD; ?>">
            <a href="?period=daily" class="<?php echo $chartPeriod === 'daily' ? 'active' : ''; ?>"><?php echo LAN_MAVEX_STATS_PUBLIC_DAILY; ?></a>
            <a href="?period=monthly" class="<?php echo $chartPeriod === 'monthly' ? 'active' : ''; ?>"><?php echo LAN_MAVEX_STATS_PUBLIC_MONTHLY; ?></a>
            <a href="?period=yearly" class="<?php echo $chartPeriod === 'yearly' ? 'active' : ''; ?>"><?php echo LAN_MAVEX_STATS_PUBLIC_YEARLY; ?></a>
          </nav>
        </div>
        <div class="mstats-chart-legend"><span class="mstats-legend-visitors"><?php echo LAN_MAVEX_STATS_PUBLIC_VISITORS; ?></span><span class="mstats-legend-views"><?php echo LAN_MAVEX_STATS_PUBLIC_PAGEVIEWS; ?></span></div>
        <div class="mstats-line-chart-wrap"><svg class="mstats-line-chart" viewBox="0 0 760 238" role="img" aria-label="<?php echo htmlspecialchars($chartTitle . ': ' . LAN_MAVEX_STATS_PUBLIC_VISITORS . ' ' . LAN_MAVEX_STATS_PUBLIC_CHART_AND . ' ' . LAN_MAVEX_STATS_PUBLIC_PAGEVIEWS, ENT_QUOTES, 'UTF-8'); ?>">
          <?php foreach (array(30,70,110,150,190) as $gy): ?><line x1="38" y1="<?php echo $gy; ?>" x2="748" y2="<?php echo $gy; ?>" class="mstats-gridline"/><?php endforeach; ?>
          <path d="<?php echo htmlspecialchars($viewsPath, ENT_QUOTES, 'UTF-8'); ?>" class="mstats-chart-line mstats-chart-line-views"/>
          <path d="<?php echo htmlspecialchars($visitorsPath, ENT_QUOTES, 'UTF-8'); ?>" class="mstats-chart-line mstats-chart-line-visitors"/>
          <?php foreach ($chartPoints as $i => $point): ?>
            <circle cx="<?php echo $point['x']; ?>" cy="<?php echo $point['viewsY']; ?>" r="3.5" class="mstats-chart-point mstats-chart-point-views"><title><?php echo htmlspecialchars($point['label'] . ' · ' . LAN_MAVEX_STATS_PUBLIC_PAGEVIEWS . ': ' . $fmt($point['views']), ENT_QUOTES, 'UTF-8'); ?></title></circle>
            <circle cx="<?php echo $point['x']; ?>" cy="<?php echo $point['visitorsY']; ?>" r="3.5" class="mstats-chart-point mstats-chart-point-visitors"><title><?php echo htmlspecialchars($point['label'] . ' · ' . LAN_MAVEX_STATS_PUBLIC_VISITORS . ': ' . $fmt($point['visitors']), ENT_QUOTES, 'UTF-8'); ?></title></circle>
            <?php if ($chartPeriod !== 'daily' || $i % 5 === 0 || $i === count($chartPoints) - 1): ?>
              <text x="<?php echo $point['x']; ?>" y="220" class="mstats-chart-month<?php echo $chartPeriod === 'monthly' ? ' mstats-chart-month-monthly' : ''; ?>" text-anchor="middle"><?php echo htmlspecialchars($point['label'], ENT_QUOTES, 'UTF-8'); ?></text>
            <?php endif; ?>
          <?php endforeach; ?>
        </svg></div>
      </section>
    </div>
    <section class="mstats-breakdowns" aria-label="<?php echo LAN_MAVEX_STATS_PUBLIC_VISITOR_TECH; ?>">
      <article class="mstats-breakdown-card"><h3><span class="mstats-breakdown-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/></svg></span> <?php echo LAN_MAVEX_STATS_PUBLIC_DEVICES; ?></h3><?php echo $makePublicBreakdown($deviceStats, 4); ?></article>
      <article class="mstats-breakdown-card"><h3><span class="mstats-breakdown-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.4 3.7 5.4 3.7 9s-1.2 6.6-3.7 9M12 3c-2.5 2.4-3.7 5.4-3.7 9s1.2 6.6 3.7 9M5.2 6.5c2 1.2 4.3 1.8 6.8 1.8s4.8-.6 6.8-1.8M5.2 17.5c2-1.2 4.3-1.8 6.8-1.8s4.8.6 6.8 1.8"/></svg></span> <?php echo LAN_MAVEX_STATS_PUBLIC_BROWSERS; ?></h3><?php echo $makePublicBreakdown($browserStats, 5); ?></article>
      <article class="mstats-breakdown-card"><h3><span class="mstats-breakdown-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="3" y="4" width="18" height="13" rx="1.5"/><path d="M8 21h8m-4-4v4"/></svg></span> <?php echo LAN_MAVEX_STATS_PUBLIC_OPERATING_SYSTEMS; ?></h3><?php echo $makePublicBreakdown($osStats, 5); ?></article>
      <p class="mstats-breakdown-note"><?php echo LAN_MAVEX_STATS_PUBLIC_BREAKDOWN_NOTE; ?></p>
    </section>
  </section>

  <section class="mstats-library-section">
    <div class="mstats-library-heading"><h2><span class="mstats-library-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 1 4 17.5z"/><path d="M4 6h13M8 8v9"/></svg></span> <?php echo LAN_MAVEX_STATS_PUBLIC_CONTENT_BASE; ?></h2><p><?php echo LAN_MAVEX_STATS_PUBLIC_CONTENT_BASE_DESC; ?></p></div>
    <div class="mstats-library-grid"><?php echo $libraryCardHtml !== '' ? $libraryCardHtml : '<p class="mstats-library-empty">' . LAN_MAVEX_STATS_PUBLIC_NO_CONTENT . '</p>'; ?></div>
  </section>
  <footer class="mstats-footer"><?php echo LAN_MAVEX_STATS_PUBLIC_BRAND; ?> <span>·</span> <?php echo LAN_MAVEX_STATS_PUBLIC_FOOTER; ?></footer>
</div>
<?php require_once(FOOTERF); ?>
