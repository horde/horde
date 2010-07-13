<?php
/**
 * JavaScript variables for the traditional interface.
 */

$charset = $GLOBALS['registry']->getCharset();

/* Variables used in core javascript files. */
$var = array(
    'view_url' => (string)Horde::applicationUrl('view.php'),
    'pref_api_url' => (string)Horde::getServiceLink('prefsapi', 'kronolith'),
    'calendar_info_url' => (string)Horde::applicationUrl('calendars/info.php', true),
    'page_title' => $GLOBALS['registry']->get('name') . ' :: ',
);

/* Gettext strings used in core javascript files. */
$gettext = array_map('addslashes', array(
    'loading' => _("Loading ..."),
    'close' => _("Close"),
));

?>
<script type="text/javascript">//<![CDATA[
var KronolithVar = <?php echo Horde_Serialize::serialize($var, Horde_Serialize::JSON, $charset) ?>;
var KronolithText = <?php echo Horde_Serialize::serialize($gettext, Horde_Serialize::JSON, $charset) ?>;
//]]></script>
