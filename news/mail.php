<?php
/**
 * News
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: mail.php 183 2008-01-06 17:39:50Z duck $
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

/* Error handler */
function _error($msg)
{
    $GLOBALS['notification']->push($msg, 'horde.error');
    $news_url = Util::addParameter(Horde::applicationUrl('news.php', true), 'id', $GLOBALS['id']);
    header('Location: ' . $news_url);
    exit;
}

if (!Auth::isAuthenticated()) {
    _error(_("Only authenticated users can send mails."));
}

$to = Util::getFormData('email');
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
                Auth::getAuth(),
                $row['title'],
                $row['publish'],
                Util::addParameter(Horde::applicationUrl('news.php', true, -1), 'id', $id));

require_once 'Horde/MIME/Mail.php';
$mail = new MIME_Mail($row['title'], $body, $to, $from, NLS::getCharset());
$result = $mail->send($conf['mailer']['type'], $conf['mailer']['params']);
if ($result instanceof PEAR_Error) {
    $notification->push($result->getMessage(), 'horde.error');
} else {
    $notification->push(sprintf(_("News succesfully send to %s"), $to), 'horde.success');
}

header('Location: ' . Util::addParameter(Horde::applicationUrl('news.php', true), 'id', $id));
exit;
