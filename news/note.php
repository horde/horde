<?php
/**
 * News
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: note.php 183 2008-01-06 17:39:50Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */
define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

$id = Util::getFormData('id');
$row = $news->get($id);
if ($row instanceof PEAR_Error) {
    $notification->push($row->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl('browse.php'));
    exit;
}

$news_url = Util::addParameter(Horde::applicationUrl('news.php', true), 'id', $id);
$body = $row['title'] . "\n\n"
       . _("On") . ': ' . $news->dateFormat($row['publish']) . "\n"
       . _("Link") . ': ' . $news_url . "\n\n"
       . strip_tags($row['content']);

/* Create a new vNote object using this message's contents. */
$vCal = new Horde_iCalendar();
$vNote = &Horde_iCalendar::newComponent('vnote', $vCal);
$vNote->setAttribute('BODY', $body);

/* Attempt to add the new vNote item to the requested notepad. */
$res = $registry->call('notes/import', array($vNote, 'text/x-vnote'));

if ($res instanceof PEAR_Error) {
    $notification->push($res->getMessage(), 'horde.error');
    header('Location: ' . $news_url);
    exit;
} else {
    $notification->push(_("News sucessfuly added to you notes."), 'horde.success');
    header('Location: ' . $registry->getInitialPage('mnemo'));
    exit;
}
