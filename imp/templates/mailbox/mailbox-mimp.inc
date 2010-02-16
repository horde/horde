<?php

if (!empty($msgcount)) {
    $title .= ' (' . $unseen . '/' .  $msgcount . ')';
}
if ($pageOb['pagecount'] > 1) {
    $title .= ' - ' . $pageOb['page'] . ' ' . _("of") . ' ' . $pageOb['pagecount'];
}

if ($readonly) {
    $title .= ' [' . _("Read-Only") . ']';
}

$mimp_render = new Horde_Mobile();
$c = &$mimp_render->add(new Horde_Mobile_card('m', $title));
$c->softkey('#o', _("Menu"));

$notification->notify(array('listeners' => 'status', 'mobile' => $c));

if (!empty($pageOb['end'])) {
    $elts = null;
    $t = &$c->add(new Horde_Mobile_table($elts));
    $t->set('border', 0);
    $t->set('padding', 1);
    $t->set('spacing', 1);

    $r = &$t->add(new Horde_Mobile_row());
    $r->add($sort[Horde_Imap_Client::SORT_ARRIVAL]);
    $r->add($sort[Horde_Imap_Client::SORT_FROM]);
    $r->add($sort[Horde_Imap_Client::SORT_SUBJECT]);

    $i = 1;
    foreach ($msgs as $msg) {
        $r = &$t->add(new Horde_Mobile_row());
        $r->add($msg['status']);
        $r->add($msg['from']);
        $l = &$r->add(new Horde_Mobile_link($msg['subject'], $msg['target']));
        $l->set('accesskey', $i++);
    }
}

$mimp_render->add($menu);
$mimp_render->display();
