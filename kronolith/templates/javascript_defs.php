<?php
/**
 * JavaScript variables for the traditional interface.
 */

$charset = $GLOBALS['registry']->getCharset();

/* Variables used in core javascript files. */
$var = array(
    'calendar_info_url' => (string)Horde::applicationUrl('calendars/info.php', true),
    'page_title' => $GLOBALS['registry']->get('name') . ' :: ',
    'pref_api_url' => (string)Horde::getServiceLink('prefsapi', 'kronolith'),
    'twentyFour' => intval($GLOBALS['prefs']->getValue('twentyFour')),
    'view_url' => (string)Horde::applicationUrl('view.php'),
);

/* Gettext strings used in core javascript files. */
$gettext = array(
    'close' => _("Close"),
    'enddate_error' => _("The end date must be later than the start date."),
    'loading' => _("Loading ..."),
);

?>
<script type="text/javascript">//<![CDATA[
var KronolithVar = <?php echo Horde_Serialize::serialize($var, Horde_Serialize::JSON, $charset) ?>;
var KronolithText = <?php echo Horde_Serialize::serialize($gettext, Horde_Serialize::JSON, $charset) ?>;
//]]></script>
