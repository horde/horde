<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require dirname(__FILE__) . '/lib/base.php';

/* Check permissions. */
if (Kronolith::hasPermission('max_events') !== true &&
    Kronolith::hasPermission('max_events') <= Kronolith::countEvents()) {
    try {
        $message = Horde::callHook('perms_denied', array('kronolith:max_events'));
    } catch (Horde_Exception_HookNotSet $e) {
        $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d events."), Kronolith::hasPermission('max_events')), ENT_COMPAT, Horde_Nls::getCharset());
    }
    $notification->push($message, 'horde.error', array('content.raw'));
    $url = Horde_Util::addParameter($prefs->getValue('defaultview') . '.php', array('month' => Horde_Util::getFormData('month'),
                                                                              'year' => Horde_Util::getFormData('year')));
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;
}

$calendar_id = Horde_Util::getFormData('calendar', Kronolith::getDefaultCalendar(PERMS_EDIT));
if (!$calendar_id) {
    $url = Horde_Util::addParameter($prefs->getValue('defaultview') . '.php', array('month' => Horde_Util::getFormData('month'),
                                                                              'year' => Horde_Util::getFormData('year')));
    header('Location: ' . Horde::applicationUrl($url, true));
}

$event = Kronolith::getDriver()->getEvent();
$_SESSION['kronolith']['attendees'] = $event->getAttendees();
$_SESSION['kronolith']['resources'] = $event->getResources();

$date = Horde_Util::getFormData('datetime');
if (!$date) {
    $date = Horde_Util::getFormData('date', date('Ymd')) . '000600';
    if ($prefs->getValue('twentyFour')) {
        $event->start->hour = 12;
    }
}
$event->start = new Horde_Date($date);
$event->end = new Horde_Date($event->start);
if (Horde_Util::getFormData('allday')) {
    $event->end->mday++;
} else {
    // Default to a 1 hour duration.
    $event->end->hour++;
}
$month = $event->start->month;
$year = $event->start->year;

$buttons = array('<input type="submit" class="button" name="save" value="' . _("Save Event") . '" />');
$url = Horde_Util::getFormData('url');
if (isset($url)) {
    $cancelurl = $url;
} else {
    $cancelurl = Horde_Util::addParameter('month.php', array('month' => $month,
                                                       'year' => $year));
    $cancelurl = Horde::applicationUrl($cancelurl, true);
}

$title = _("Add a new event");
$calendars = Kronolith::listCalendars(false, PERMS_EDIT | PERMS_DELEGATE);
Horde::addScriptFile('popup.js', 'horde', true);
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
require KRONOLITH_TEMPLATES . '/edit/javascript.inc';
require KRONOLITH_TEMPLATES . '/edit/edit.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
