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

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('year:' . Kronolith::currentDate()->dateString())->redirect();
    exit;
}

$view = Kronolith::getView('Year');
$title = $view->year;

require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';

echo '<div id="page">';
Kronolith::tabs();
$view->html();
echo '</div>';

require KRONOLITH_TEMPLATES . '/calendar_titles.inc';
require KRONOLITH_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
