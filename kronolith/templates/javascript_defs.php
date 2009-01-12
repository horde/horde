<?php

require_once 'Horde/Serialize.php';
$charset = NLS::getCharset();

/* Variables used in core javascript files. */
$var = array(
    'view_url' => Horde::applicationUrl('view.php'),
    'pref_api_url' => Horde::applicationUrl('pref_api.php', true),
    'calendar_info_url' => Horde::applicationUrl('calendars/info.php', true),
    'page_title' => $GLOBALS['registry']->get('name') . ' :: ',
);

/* Gettext strings used in core javascript files. */
$gettext = array_map('addslashes', array(
    'loading' => _("Loading ..."),
    'close' => _("Close"),
));

?>
<script type="text/javascript">//<![CDATA[
var KronolithVar = <?php echo Horde_Serialize::serialize($var, SERIALIZE_JSON, $charset) ?>;
var KronolithText = <?php echo Horde_Serialize::serialize($gettext, SERIALIZE_JSON, $charset) ?>;
//]]></script>
