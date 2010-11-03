<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('wicked');

$v1 = Horde_Util::getFormData('v1');
$v2 = Horde_Util::getFormData('v2');

/* Bail out if we didn't get any versions - at least one of these has
 * to be non-empty. */
if (!$v1 && !$v2) {
    Horde::url('history.php', true)
        ->add('page', Horde_Util::getFormData('page'))
        ->redirect();
}

/* Make sure that $v2 is a higher version than $v1. Empty string is
 * the current version of the page, so is always highest.  Also, '?' is a
 * wildcard for the previous version, so it's always the lowest. */
if (!$v1 || ($v2 && version_compare($v1, $v2) > 0) || $v2 == '?') {
    $tmp = $v1;
    $v1 = $v2;
    $v2 = $tmp;
}

try {
    $page = Wicked_Page::getPage(Horde_Util::getFormData('page'), $v2);
} catch (Wicked_Exception $e) {
    $notification->push(sprintf(_("Internal error viewing requested page: %s"), $e->getMessage()), 'horde.error');
    Wicked::url('WikiHome', true)->redirect();
}

if ($v1 == '?') {
    $v1 = $page->previousVersion();
}

/* Kick back to the display page if we're not allowed to diff this
 * page. */
if (!$page->allows(Wicked::MODE_DIFF)) {
    Wicked::url($page->pageName(), true)
        ->add('actionID', 'diff')
        ->redirect();
}

$title = sprintf(_("Diff for %s between %s and %s"), $page->pageName(), $v1, $page->version());
require WICKED_TEMPLATES . '/common-header.inc';
require WICKED_TEMPLATES . '/menu.inc';
$page->render(Wicked::MODE_DIFF, $v1);
require $registry->get('templates', 'horde') . '/common-footer.inc';
