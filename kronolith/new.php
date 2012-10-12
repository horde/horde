<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('event')->redirect();
}

/* Check permissions. */
$url = Horde::url($prefs->getValue('defaultview') . '.php', true)
      ->add(array('month' => Horde_Util::getFormData('month'),
                  'year' => Horde_Util::getFormData('year')));

$perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
if ($perms->hasAppPermission('max_events') !== true &&
    $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
    Horde::permissionDeniedError(
        'kronolith',
        'max_events',
        sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events'))
    );
    $url->redirect();
}

$calendar_id = Horde_Util::getFormData(
    'calendar',
    empty($GLOBALS['display_resource_calendars']) ?
        'internal_' . Kronolith::getDefaultCalendar(Horde_Perms::EDIT) :
        'resource_' . $GLOBALS['display_resource_calendars'][0]
);
if ($calendar_id == 'internal_' || $calendar_id == 'resource_') {
    $url->redirect();
}

$event = Kronolith::getDriver()->getEvent();
$session->set('kronolith', 'attendees', $event->attendees);
$session->set('kronolith', 'resources', $event->getResources());

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

$buttons = array('<input type="submit" class="horde-default" name="save" value="' . _("Save Event") . '" />');
$url = Horde_Util::getFormData('url');
if (isset($url)) {
    $cancelurl = new Horde_Url($url);
} else {
    $cancelurl = Horde::url('month.php', true)->add('month', $month);
}

$calendars = Kronolith::listCalendars(Horde_Perms::EDIT | Kronolith::PERMS_DELEGATE, true);

Horde_Core_Ui_JsCalendar::init(array(
    'full_weekdays' => true
));

$page_output->addScriptFile('edit.js');
$page_output->addScriptFile('popup.js', 'horde');

$page_output->header(array(
    'title' => _("Add a new event")
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
require KRONOLITH_TEMPLATES . '/edit/edit.inc';
$page_output->footer();
