<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Tyler Colbert <tyler@colberts.us>
 */

@define('WICKED_BASE', dirname(__FILE__));
require_once WICKED_BASE . '/lib/base.php';

$page = Page::getCurrentPage();
if (is_a($page, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"), 'horde.error');
    header('Location: ' . Wicked::url('WikiHome', true));
    exit;
}

if (!$page->allows(WICKED_MODE_HISTORY)) {
    /* Redirect to display page and force it to display an error. */
    $url = Horde_Util::addParameter(Wicked::url($page->pageName(), true), 'actionID', 'history');
    header('Location: ' . $url);
    exit;
}

$title = sprintf(_("History: %s"), $page->pageName());
require WICKED_TEMPLATES . '/common-header.inc';
require WICKED_TEMPLATES . '/menu.inc';
$page->render(WICKED_MODE_HISTORY);
require $registry->get('templates', 'horde') . '/common-footer.inc';
