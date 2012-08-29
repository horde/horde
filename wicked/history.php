<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Tyler Colbert <tyler@colberts.us>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('wicked');

try {
    $page = Wicked_Page::getCurrentPage();
} catch (Wicked_Exception $e) {
    $notification->push(_("Internal error viewing requested page"), 'horde.error');
    Wicked::url('Wiki/Home', true)->redirect();
}

if (!$page->allows(Wicked::MODE_HISTORY)) {
    /* Redirect to display page and force it to display an error. */
    Wicked::url($page->pageName(), true)->add('actionID', 'history')->redirect();
}

$page_output->header(array(
    'title' => sprintf(_("History: %s"), $page->pageName())
));
require WICKED_TEMPLATES . '/menu.inc';
$page->render(Wicked::MODE_HISTORY);
$page_output->footer();
