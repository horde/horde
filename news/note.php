<?php
/**
 * News
 *
 * $Id: note.php 1241 2009-01-29 23:27:58Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

require_once dirname(__FILE__) . '/lib/base.php';

$id = Horde_Util::getFormData('id');
$row = $news->get($id);
if ($row instanceof PEAR_Error) {
    $notification->push($row);
    Horde::applicationUrl('browse.php')->redirect();
}

$body = $row['title'] . "\n\n"
       . _("On") . ': ' . News::dateFormat($row['publish']) . "\n"
       . _("Link") . ': ' . News::getUrlFor('news', $id) . "\n\n"
       . strip_tags($row['content']);

/* Create a new vNote object using this message's contents. */
$vCal = new Horde_iCalendar();
$vNote = &Horde_iCalendar::newComponent('vnote', $vCal);
$vNote->setAttribute('BODY', $body);

/* Attempt to add the new vNote item to the requested notepad. */
try {
    $registry->call('notes/import', array($vNote, 'text/x-vnote'));
    $notification->push(_("News sucessfuly added to you notes."), 'horde.success');
    header('Location: ' . $registry->getInitialPage('mnemo'));
} catch (Horde_Exception $e) {
    $notification->push($e);
    News::getUrlFor('news', $id)->redirect();
}
