<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('KRONOLITH_BASE', dirname(__FILE__));
require KRONOLITH_BASE . '/lib/base.php';

/* Check permissions. */
if (Kronolith::hasPermission('max_events') !== true &&
    Kronolith::hasPermission('max_events') <= Kronolith::countEvents()) {
    $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d events."), Kronolith::hasPermission('max_events')), ENT_COMPAT, NLS::getCharset());
    if (!empty($conf['hooks']['permsdenied'])) {
        $message = Horde::callHook('_perms_hook_denied', array('kronolith:max_events'), 'horde', $message);
    }
    $notification->push($message, 'horde.error', array('content.raw'));
    $url = Util::addParameter($prefs->getValue('defaultview') . '.php', array('month' => Util::getFormData('month'),
                                                                              'year' => Util::getFormData('year')));
    header('Location: ' . Horde::applicationUrl($url, true));
    exit;
}

$calendar_id = Util::getFormData('calendar', Kronolith::getDefaultCalendar(PERMS_EDIT));
if (!$calendar_id) {
    $url = Util::addParameter($prefs->getValue('defaultview') . '.php', array('month' => Util::getFormData('month'),
                                                                              'year' => Util::getFormData('year')));
    header('Location: ' . Horde::applicationUrl($url, true));
}

$event = $kronolith_driver->getEvent();
$_SESSION['kronolith']['attendees'] = $event->getAttendees();

$date = Util::getFormData('datetime');
if (!$date) {
    $date = Util::getFormData('date', date('Ymd')) . '000600';
    if ($prefs->getValue('twentyFour')) {
        $event->start->hour = 12;
    }
}
$event->start = new Horde_Date($date);

$url = Util::getFormData('url');

// Default to a 1 hour duration.
$event->end = new Horde_Date($event->start);
if (Util::getFormData('allday')) {
    $event->end->mday++;
    /*
    $event->end->hour = 23;
    $event->end->min = $event->end->sec = 59;
    */
} else {
    $event->end->hour++;
}
$event->end->correct();
$month = $event->start->month;
$year = $event->start->year;

$buttons = array('<input type="submit" class="button" name="save" value="' . _("Save Event") . '" onclick="return checkCategory();" />');
if (isset($url)) {
    $cancelurl = $url;
} else {
    $cancelurl = Util::addParameter('month.php', array('month' => $month,
                                                       'year' => $year));
    $cancelurl = Horde::applicationUrl($cancelurl, true);
}

$title = _("Add a new event");
$calendars = Kronolith::listCalendars(false, PERMS_EDIT | PERMS_DELEGATE);
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
require KRONOLITH_TEMPLATES . '/edit/javascript.inc';
require KRONOLITH_TEMPLATES . '/edit/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
