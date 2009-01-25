<?php
/**
 * News
 *
 * $Id: note.php 890 2008-09-23 09:58:23Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
require_once dirname(__FILE__) . '/lib/base.php';

$id = Util::getFormData('id');
$row = $news->get($id);
if ($row instanceof PEAR_Error) {
    $notification->push($row);
    header('Location: ' . Horde::applicationUrl('browse.php'));
    exit;
}

$body = $row['title'] . "\n\n"getUrlFor
       . _("On") . ': ' . $news->dateFormat($row['publish']) . "\n"
       . _("Link") . ': ' . News::getUrlFor('news', $id) . "\n\n"
       . strip_tags($row['content']);

/* Create a new vNote object using this message's contents. */
$vCal = new Horde_iCalendar();
$vNote = &Horde_iCalendar::newComponent('vnote', $vCal);
$vNote->setAttribute('BODY', $body);

/* Attempt to add the new vNote item to the requested notepad. */
$res = $registry->call('notes/import', array($vNote, 'text/x-vnote'));

if ($res instanceof PEAR_Error) {
    $notification->push($res);
    header('Location: ' . News::getUrlFor('news', $id));
    exit;
} else {
    $notification->push(_("News sucessfuly added to you notes."), 'horde.success');
    header('Location: ' . $registry->getInitialPage('mnemo'));
    exit;
}