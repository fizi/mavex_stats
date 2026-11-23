<?php
/** Independent MAVEX statistics collector bootstrap. */
if (!defined('e107_INIT')) { exit; }
// e107 2.x uses e_ADMIN_AREA to distinguish the public and admin areas.
// USER_AREA is not a reliable/core e107 2.x constant.
if (defined('e_ADMIN_AREA') && e_ADMIN_AREA) { return; }

$statsPrefs = (array) e107::pref('mavex_stats');
if (isset($statsPrefs['tracking_enabled']) && empty($statsPrefs['tracking_enabled'])) { return; }

$statsEndpoint = e_PLUGIN_ABS . 'mavex_stats/collect.php';
$statsScript = "(function(){\n" .
    "var endpoint=" . json_encode($statsEndpoint) . ";\n" .
    "var path=window.location.pathname||'/';\n" .
    "var ref=''; try { if(document.referrer){ref=(new URL(document.referrer)).hostname||'';} } catch(e){}\n" .
    "var payload={path:path,referrer:ref,screen:(screen&&screen.width?screen.width+'x'+screen.height:'')};\n" .
    "try { var body=new URLSearchParams(payload); if(navigator.sendBeacon){navigator.sendBeacon(endpoint,body);} else {fetch(endpoint,{method:'POST',body:body,credentials:'same-origin',keepalive:true});} } catch(e){}\n" .
    "})();";
e107::js('inline', $statsScript);
