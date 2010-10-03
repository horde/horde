<?php
/**
 * JavaScript variables for the traditional interface.
 */

$charset = 'UTF-8';
$currentDate = Kronolith::currentDate();

/* Variables used in core javascript files. */
$var = array(
    'URI_AJAX' => Horde::getServiceLink('ajax', 'kronolith')->url,
    'calendar_info_url' => (string)Horde::url('calendars/info.php', true),
    'page_title' => $GLOBALS['registry']->get('name') . ' :: ',
    'twentyFour' => intval($GLOBALS['prefs']->getValue('twentyFour')),
    'view_url' => (string)Horde::url('view.php'),
);

/* Gettext strings used in core javascript files. */
$gettext = array(
    'close' => _("Close"),
    'enddate_error' => _("The end date must be later than the start date."),
    'loading' => _("Loading ..."),
);

?>
<script type="text/javascript">//<![CDATA[
var KronolithDate = new Date(<?php printf('%d, %d, %d', $currentDate->year, $currentDate->month - 1, $currentDate->mday) ?>);
var KronolithText = <?php echo Horde_Serialize::serialize($gettext, Horde_Serialize::JSON, $charset) ?>;
var KronolithVar = <?php echo Horde_Serialize::serialize($var, Horde_Serialize::JSON, $charset) ?>;
var KronolithView = '<?php if (isset($view) && is_object($view)) echo $view->getName() ?>';
//]]></script>
