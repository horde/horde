<?php
/**
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('month:' . Kronolith::currentDate()->dateString())->redirect();
}

$view = Kronolith::getView('Month');
$title = $view->date->strftime('%B %Y');
if ($prefs->getValue('show_panel')) {
    $bodyClass = 'rightPanel';
}

Horde::addScriptFile('views.js', 'kronolith');

require $registry->get('templates', 'horde') . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));

echo '<div id="page">';
Kronolith::tabs();
$view->html();
echo '</div>';

require KRONOLITH_TEMPLATES . '/calendar_titles.inc';
require KRONOLITH_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
