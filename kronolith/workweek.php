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
    Horde::url('', true)->setAnchor('week:' . Kronolith::currentDate()->dateString())->redirect();
    exit;
}

$view = Kronolith::getView('WorkWeek');

$page_output->addScriptFile('tooltips.js', 'horde');
Kronolith::addCalendarLinks();

$page_output->header(array(
    'body_class' => $prefs->getValue('show_panel') ? 'rightPanel' : null,
    'title' => sprintf(_("Week %d"), $view->week)
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
Kronolith::tabs($view);
$view->html(KRONOLITH_TEMPLATES);
require KRONOLITH_TEMPLATES . '/calendar_titles.inc';
$page_output->footer();
