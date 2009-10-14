<?php

$c = &$mimp_render->add(new Horde_Mobile_card('m', $title));
$c->softkey('#o', _("Menu"));

$imp_notify->setMobileObject($c);
$notification->notify(array('listeners' => 'status'));

$f = &$c->add(new Horde_Mobile_form('compose-mimp.php'));

// Hidden Variables.
$f->add(new Horde_Mobile_hidden('composeCache', $cacheID));
$f->add(new Horde_Mobile_hidden('resume_draft', Horde_Util::getFormData('resume_draft', $resume_draft)));

if (!$prefs->isLocked('default_identity')) {
    $ib = &$f->add(new Horde_Mobile_block(new Horde_Mobile_text(_("Identity: "))));
    $ident_sel = &$ib->add(new Horde_Mobile_select('identity'));
    $default = Horde_Util::getFormData('identity', $identity->getDefault());
    foreach ($select_list as $key => $select) {
        $ident_sel->add($select, $key, ($key == $default));
    }
}

$f->add(new Horde_Mobile_input('to', $header['to'], _("To: ")));
if ($prefs->getValue('compose_cc')) {
    $f->add(new Horde_Mobile_input('cc', $header['cc'], _("Cc: ")));
}
if ($prefs->getValue('compose_bcc')) {
    $f->add(new Horde_Mobile_input('bcc', $header['bcc'], _("Bcc: ")));
}
$f->add(new Horde_Mobile_input('subject', $header['subject'], _("Subject: ")));

$f->add(new Horde_Mobile_textarea('message', $msg, _("Message: "), 10, 80));

if (!$compose_disable) {
    $f->add(new Horde_Mobile_submit(_("Send"), 'a'));
}
if ($conf['user']['allow_folders'] && !$readonly_drafts) {
    $f->add(new Horde_Mobile_submit(_("Save Draft"), 'a'));
}

$f->add(new Horde_Mobile_submit(_("Expand Names"), 'a'));

$mimp_render->add($menu);
$mimp_render->display();
