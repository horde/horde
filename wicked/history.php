<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Tyler Colbert <tyler@colberts.us>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('wicked');

$page = Wicked_Page::getCurrentPage();
if (is_a($page, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"), 'horde.error');
    Wicked::url('WikiHome', true)->redirect();
}

if (!$page->allows(Wicked::MODE_HISTORY)) {
    /* Redirect to display page and force it to display an error. */
    Wicked::url($page->pageName(), true)->add('actionID', 'history')->redirect();
}

$title = sprintf(_("History: %s"), $page->pageName());
require WICKED_TEMPLATES . '/common-header.inc';
require WICKED_TEMPLATES . '/menu.inc';
$page->render(Wicked::MODE_HISTORY);
require $registry->get('templates', 'horde') . '/common-footer.inc';
