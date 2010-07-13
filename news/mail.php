<?php
/**
 * News
 *
 * $Id: mail.php 1174 2009-01-19 15:11:03Z duck $
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
    header('Location: ' . Horde::applicationUrl('browse.php'));
    exit;
}

/* Error handler */
function _error($msg)
{
    $GLOBALS['notification']->push($msg, 'horde.error');
    header('Location: ' . News::getUrlFor('news', $GLOBALS['id']));
    exit;
}

if (!$registry->isAuthenticated()) {
    _error(_("Only authenticated users can send mails."));
}

$to = Horde_Util::getFormData('email');
if (empty($to)) {
    _error(_("No mail entered."));
    exit;
}

$from = $prefs->getValue('from_addr');
if (empty($from)) {
    _error(_("You have no email set."));
    exit;
}

$body = sprintf(_("%s would you like to invite you to read the news\n Title: %s\n\n Published: %s \nLink: %s"),
                $GLOBALS['registry']->getAuth(),
                $row['title'],
                $row['publish'],
                News::getUrlFor('news', $id, true, -1));

$mail = new Horde_Mime_Mail(array('subject' => $row['title'], 'body' => $body, 'to' => $to, 'from' => $from, 'charset' => $GLOBALS['registry']->getCharset()));
try {
    $mail->send($injector->getInstance('Horde_Mail'));
    $notification->push(sprintf(_("News succesfully send to %s"), $to), 'horde.success');
} catch (Horde_Mime_Exception $e) {
    $notification->push($e);
}

header('Location: ' . News::getUrlFor('news', $id));
exit;
