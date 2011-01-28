<?php
/**
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

$calendar = Horde_Util::getFormData('calendar');
$url = Horde::url('', true);
if (Kronolith::showAjaxView()) {
    $url->setAnchor('calendar:internal|' . $calendar);
} else {
    $url->setAnchor('calendar:' . $calendar);
    if (!in_array($display_calendars, $calendar)) {
        $url->add('toggle_calendar', $calendar);
    }
}
$url->redirect();
