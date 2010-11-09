<?php
/**
 * JavaScript variables for the traditional interface.
 */

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

Horde::addInlineJsVars(array(
    '-var KronolithDate' => 'new Date(' . sprintf('%d, %d, %d', $currentDate->year, $currentDate->month - 1, $currentDate->mday) . ')',
    'var KronolithText' => $gettext,
    'var KronolithVar' => $var,
    'var KronolithView' => (isset($view) && is_object($view)) ? $view->getName() : ''
), array('top' => true));
