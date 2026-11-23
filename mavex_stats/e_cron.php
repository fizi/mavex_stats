<?php
/** Scheduled maintenance tasks for MAVEX Stats. */
if (!defined('e107_INIT')) { exit; }

class mavex_stats_cron
{
    public function config()
    {
        return array(array(
            'name' => 'MAVEX Stats – automatic data retention cleanup',
            'function' => 'cleanupExpiredEvents',
            'category' => 'content',
            'description' => 'Deletes MAVEX Stats events older than the retention period configured in plugin settings.'
        ));
    }

    public function cleanupExpiredEvents()
    {
        $prefs = (array) e107::pref('mavex_stats');
        $days = isset($prefs['retention_days']) ? (int) $prefs['retention_days'] : 0;
        if (!in_array($days, array(90, 180, 365), true)) {
            return false;
        }

        $cutoff = time() - ($days * 86400);
        return e107::getDb()->db_Delete('mavex_stats_events', 'event_time < ' . (int) $cutoff);
    }
}
