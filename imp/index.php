<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

// Will redirect to login page if not authenticated.
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp');

// Load initial page as defined by view mode & preferences.
$page = IMP_Auth::getInitialPage();

switch ($registry->getView()) {
case $registry::VIEW_SMARTMOBILE:
    // If loading the mailbox page, we need to redirect so that jquery mobile
    // correctly loads deep-linked page.
    if (!is_null($page->mbox)) {
        $page->url->redirect();
    }
    break;
}

require IMP_Auth::getInitialPage()->fullpath;
