<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('day:' . Kronolith::currentDate()->dateString())->redirect();
}

$view = Kronolith::getView('Day');

$page_output->addScriptFile('tooltips.js', 'horde');
Kronolith::addCalendarLinks();

$page_output->header(array(
    'body_class' => $prefs->getValue('show_panel') ? 'rightPanel' : null,
    'title' => $view->getTime($prefs->getValue('date_format'))
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
Kronolith::tabs($view);
$view->html(KRONOLITH_TEMPLATES);
require KRONOLITH_TEMPLATES . '/calendar_titles.inc';
$page_output->footer();
