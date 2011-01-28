<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Tyler Colbert <tyler@colberts.us>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('wicked');

$actionID = Horde_Util::getFormData('actionID');
try {
    $page = Wicked_Page::getCurrentPage();
} catch (Wicked_Exception $e) {
    $notification->push(_("Internal error viewing requested page"), 'horde.error');
    $page = Wicked_Page::getPage('');
    $actionID = null;
}

switch ($actionID) {
case 'lock':
    if (!$page->allows(Wicked::MODE_LOCKING)) {
        $notification->push(_("You are not allowed to lock this page"),
                            'horde.error');
        break;
    }
    try {
        $result = $page->lock();
    } catch (Wicked_Exception $e) {
        $notification->push(sprintf(_("Page failed to lock: %s"), $e->getMessage()),
                            'horde.error');
    }
    break;

case 'unlock':
    if (!$page->allows(Wicked::MODE_UNLOCKING)) {
        $notification->push(_("You are not allowed to unlock this page"),
                            'horde.error');
    }
    try {
        $result = $page->unlock();
        $notification->push(_("Page unlocked"), 'horde.success');
    } catch (Wicked_Exception $e) {
        $notification->push(
            sprintf(_("Page failed to unlock: %s"), $e->getMessage()),
            'horde.error');
    }
    break;

case 'history':
    if ($page->allows(Wicked::MODE_HISTORY)) {
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
    if (!$page->allows(Wicked::MODE_DISPLAY)) {
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

    $wiki = $page->getProcessor($format);
    try {
        $text = $wiki->transform($page->getText(), $format);
        $browser->downloadHeaders($page->pageTitle() . $ext, $mime, false, strlen($text));
        echo $text;
        exit;
    } catch (Wicked_Exception $e) {
        $notification->push($e);
    }
    break;

default:
    $wicked->logPageView($page->pageName());
    break;
}

if (!$page->allows(Wicked::MODE_DISPLAY)) {
    if ($page->pageName() == 'WikiHome') {
        Horde::fatal(_("You don't have permission to view this page."));
    }
    $notification->push(_("You don't have permission to view this page."),
                        'horde.error');
    $page = Wicked_Page::getPage('');
}

$params = Horde_Util::getFormData('params');
$page->preDisplay(Wicked::MODE_DISPLAY, $params);

if ($page->isLocked()) {
    $notification->push(sprintf(_("This page is locked by %s for %d Minutes."), $page->getLockRequestor(), $page->getLockTime()), 'horde.message');
}

$history = $session->get('wicked', 'history', Horde_Session::TYPE_ARRAY);

$title = $page->pageTitle();
Horde::startBuffer();
$page->render(Wicked::MODE_DISPLAY, $params);
$content = Horde::endBuffer();

require $registry->get('templates', 'horde') . '/common-header.inc';
require WICKED_TEMPLATES . '/menu.inc';
echo $content;
require $registry->get('templates', 'horde') . '/common-footer.inc';

if ($page instanceof Wicked_Page_StandardPage &&
    (!isset($history[0]) ||
     $history[0] != $page->pageName())) {
    array_unshift($history, $page->pageName());
    $session->set('wicked', 'history', $history);
}

if (count($history) > 10) {
    array_pop($history);
    $session->set('wicked', 'history', $history);
}
