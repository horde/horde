<?php

$mimp_render = new Horde_Mobile();
$c = $mimp_render->add(new Horde_Mobile_card('m', sprintf(_("Search %s"), $imp_mbox['mailbox'])));

$f = $c->add(new Horde_Mobile_form('mailbox-mimp.php'));

$f->add(new Horde_Mobile_hidden('a', 'rs'));
$f->add(new Horde_Mobile_hidden('mailbox', $imp_mbox['mailbox']));

$f->add(new Horde_Mobile_input('search', '', _("Search String: ")));

$f->add(new Horde_Mobile_submit(_("Run Search"), 'submit'));

$imp_notify->setMobileObject($c);
$notification->notify(array('listeners' => 'status'));

$mimp_render->display();
