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

$page = Page::getCurrentPage();
if (is_a($page, 'PEAR_Error')) {
    $notification->push(_("Internal error viewing requested page"),
                        'horde.error');
}

$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'lock':
    if (!$page->allows(WICKED_MODE_LOCKING)) {
        $notification->push(_("You are not allowed to lock this page"),
                            'horde.error');
        break;
    }
    $result = $page->lock();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("Page failed to lock: %s"), $result->getMessage()),
                            'horde.error');
    }
    break;

case 'unlock':
    if (!$page->allows(WICKED_MODE_UNLOCKING)) {
        $notification->push(_("You are not allowed to unlock this page"),
                            'horde.error');
    }
    $result = $page->unlock();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(
            sprintf(_("Page failed to unlock: %s"), $result->getMessage()),
            'horde.error');
    } else {
        $notification->push(_("Page unlocked"), 'horde.success');
    }
    break;

case 'history':
    if ($page->allows(WICKED_MODE_HISTORY)) {
        /* Redirect to history page. */
        Horde::url('history.php')
            ->add('page', $page->pageName())
            ->redirect();
    }
    $notification->push(_("This page does not have a history"), 'horde.error');
    break;

case 'special':
    $page->handleAction();
    break;

case 'export':
    if (!$page->allows(WICKED_MODE_DISPLAY)) {
        $notification->push(_("You don't have permission to view this page."),
                            'horde.error');
        if ($page->pageName() == 'WikiHome') {
            throw new Horde_Exception(_("You don't have permission to view this page."));
        }
        Wicked::url('WikiHome', true)->redirect();
    }

    switch (Horde_Util::getGet('format')) {
    case 'html':
        $format = 'Xhtml';
        $ext = '.html';
        $mime = 'text/html';
        break;

    case 'tex':
        $format = 'Latex';
        $ext = '.tex';
        $mime = 'text/x-tex';
        break;

    case 'plain':
    default:
        $format = 'Plain';
        $ext = '.txt';
        $mime = 'text/text';
        break;
    }

    $wiki = &$page->getProcessor($format);
    $text = $wiki->transform($page->getText(), $format);
    if (is_a($text, 'PEAR_Error')) {
        echo $text->getMessage();
    } else {
        $browser->downloadHeaders($page->pageTitle() . $ext, $mime, false, strlen($text));
        echo $text;
    }
    exit(0);

default:
    $wicked->logPageView($page->pageName());
}

if (!$page->allows(WICKED_MODE_DISPLAY)) {
    $notification->push(_("You don't have permission to view this page."),
                        'horde.error');
    if ($page->pageName() == 'WikiHome') {
        throw new Horde_Exception(_("You don't have permission to view this page."));
    }
    Wicked::url('WikiHome', true)->redirect();
}

$params = Horde_Util::getFormData('params');
$page->preDisplay(WICKED_MODE_DISPLAY, $params);

if (!isset($_SESSION['wickedSession']['history'])) {
    $_SESSION['wickedSession']['history'] = array();
}

if ($page->isLocked()) {
    $notification->push(sprintf(_("This page is locked by %s for %d Minutes."), $page->getLockRequestor(), $page->getLockTime()), 'horde.message');
}

$title = $page->pageTitle();
require WICKED_TEMPLATES . '/common-header.inc';
require WICKED_TEMPLATES . '/menu.inc';
$page->render(WICKED_MODE_DISPLAY, $params);
require $registry->get('templates', 'horde') . '/common-footer.inc';

if (is_a($page, 'StandardPage') &&
    (!isset($_SESSION['wickedSession']['history'][0]) ||
     $_SESSION['wickedSession']['history'][0] != $page->pageName())) {
    array_unshift($_SESSION['wickedSession']['history'], $page->pageName());
}
if (count($_SESSION['wickedSession']['history']) > 10) {
    array_pop($_SESSION['wickedSession']['history']);
}
