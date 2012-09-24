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

$viewName = Horde_Util::getFormData('view', 'Event');
$view = Kronolith::getView($viewName);
if (is_string($view->event)) {
    $notification->push($view->event, 'horde.error');
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('event:' . $view->event->calendarType . '|' . $view->event->calendar . ':' . $view->event->id . ':' . Horde_Util::getFormData('datetime', Kronolith::currentDate()->dateString()))->redirect();
}

switch ($viewName) {
case 'DeleteEvent':
    /* Shortcut when we're deleting events and don't want confirmation. */
    if (!$view->event->recurs() &&
        !($prefs->getValue('confirm_delete') ||
          Horde_Util::getFormData('confirm'))) {
        Horde::url('delete.php?' . $_SERVER['QUERY_STRING'], true)->redirect();
    }
    break;

case 'EditEvent':
    if ($view->event->private &&
        $view->event->creator != $GLOBALS['registry']->getAuth()) {
        $url = $url = Horde_Util::getFormData('url');
        if (!empty($url)) {
            $url = new Horde_Url($url, true);
        } else {
            $url = Horde::url($prefs->getValue('defaultview') . '.php', true);
        }
        $url->unique()->redirect();
    }
    break;
}

$page_output->header(array(
    'body_class' => $prefs->getValue('show_panel') ? 'rightPanel' : null,
    'title' => $view->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));

echo '<div id="page">';
Kronolith::eventTabs($viewName, $view->event);
$view->html();
echo '</div>';
$page_output->footer();
