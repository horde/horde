<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

$viewName = Horde_Util::getFormData('view', 'Event');
$view = Kronolith::getView($viewName);
if (is_string($view->event)) {
    $notification->push($view->event, 'horde.error');
    header('Location: ' . Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true));
    exit;
}

if (Kronolith::showAjaxView()) {
    header('Location: ' . Horde::applicationUrl('', true)->setAnchor('event:' . $view->event->calendarType . '|' . $view->event->calendar . ':' . $view->event->id . ':' . Horde_Util::getFormData('datetime', Kronolith::currentDate()->dateString())));
    exit;
}

switch ($viewName) {
case 'DeleteEvent':
    /* Shortcut when we're deleting events and don't want confirmation. */
    if (!$view->event->recurs() &&
        !($prefs->getValue('confirm_delete') ||
          Horde_Util::getFormData('confirm'))) {
        header('Location: ' . Horde::applicationUrl('delete.php?' . $_SERVER['QUERY_STRING'], true));
        exit;
    }
    break;

case 'EditEvent':
    if ($view->event->private &&
        $view->event->creator != $GLOBALS['registry']->getAuth()) {
        $url = $url = Horde_Util::getFormData('url');
        if (!empty($url)) {
            $url = new Horde_Url($url, true);
        } else {
            $url = Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true);
        }
        header('Location: ' . $url->unique());
        exit;
    }
    break;
}

$title = $view->getTitle();
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';

echo '<div id="page">';
Kronolith::eventTabs($viewName, $view->event);
$view->html();
echo '</div>';
require $registry->get('templates', 'horde') . '/common-footer.inc';
