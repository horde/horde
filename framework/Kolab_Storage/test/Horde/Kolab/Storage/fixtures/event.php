<?php

$part1 = new Horde_Mime_Part();
$part1->setType('text/plain');
$part1->setTransferEncoding('quoted-printable');
$part1->setCharset('UTF-8');
$part1->setDisposition('inline');
$part1->setBytes(249);

$part2 = new Horde_Mime_Part();
$part2->setType('application/x-vnd.kolab.event');
$part2->setTransferEncoding('quoted-printable');
$part2->setName('kolab.xml');
$part2->setDisposition('attachment');
$part2->setBytes(704);

$message = new Horde_Mime_Part();
$message->setType('multipart/mixed');
$message->addPart($part1);
$message->addPart($part2);
$message->buildMimeIds(0);

return $message;
