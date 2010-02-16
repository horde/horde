<?php

$c = &$mimp_render->add(new Horde_Mobile_card('m', $title));
$c->softkey('#o', _("Menu"));

$notification->notify(array('listeners' => 'status', 'mobile' => $c));

$f = &$c->add(new Horde_Mobile_form('compose.php'));

$f->add(new Horde_Mobile_hidden('action', 'rc'));
$f->add(new Horde_Mobile_hidden('thismailbox', $thismailbox));
$f->add(new Horde_Mobile_hidden('uid', $uid));

$f->add(new Horde_Mobile_input('to', $header['to'], _("To: ")));

$f->add(new Horde_Mobile_submit(_("Redirect"), 'a'));
$f->add(new Horde_Mobile_submit(_("Expand Names"), 'a'));

$mimp_render->add($menu);
$mimp_render->display();
