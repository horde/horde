#!/usr/bin/php
<?php
/**
 * This script bounces a message back to the sender and can be used with IMP's
 * spam reporting feature to bounce spam.
 *
 * It takes the orginal message from standard input and requires the bounce
 * message in the file imp/config/bounce.txt. Important: the bounce message
 * must be a complete message including headers!
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('imp', array('authentication' => true, 'cli' => true));

$bounce_template = IMP_BASE . '/config/bounce.txt';

/* If there's no bounce template file then abort */
if (!file_exists($bounce_template)) {
    exit(0);
}

/* Read the message content. */
$data = Horde_Cli::readStdin();

/* Who's the spammer? */
preg_match('/return-path: <(.*?)>\r?\n/i', $data, $matches);
$return_path = $matches[1];

/* Who's the target? */
preg_match_all('/delivered-to: (.*?)\r?\n/is', $data, $matches);
$delivered_to = $matches[1][count($matches[1])-1];

/* Read the bounce template and construct the mail */
$bounce = file_get_contents($bounce_template);
$bounce = str_replace(array('%TO%', '%TARGET%'),
                      array($return_path, $delivered_to),
                      $bounce);

/* Send the mail */
$sendmail = "/usr/sbin/sendmail -t -f ''";
$fd = popen($sendmail, 'w');
fputs($fd, preg_replace("/\n$/", "\r\n", $bounce . $data));
pclose($fd);
