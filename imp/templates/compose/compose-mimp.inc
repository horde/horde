<?php

$c = $mimp_render->add(new Horde_Mobile_card('m', $title));
$c->softkey('#o', _("Menu"));

$notification->notify(array('listeners' => 'status', 'mobile' => $c));

$f = $c->add(new Horde_Mobile_form('compose-mimp.php'));

// Hidden Variables.
$f->add(new Horde_Mobile_hidden('composeCache', $cacheID));

if (!$prefs->isLocked('default_identity')) {
    $ib = $f->add(new Horde_Mobile_block(new Horde_Mobile_text(_("Identity: "))));
    $ident_sel = $ib->add(new Horde_Mobile_select('identity'));
    $default = Horde_Util::getFormData('identity', $identity->getDefault());
    foreach ($select_list as $key => $select) {
        $ident_sel->add($select, $key, ($key == $default));
    }
}

foreach ($display_hdrs as $key => $val) {
    $f->add(new Horde_Mobile_input($key, $header[$key], $val));
    if (isset($expand[$key])) {
        if (count($expand[$key][1]) > 5) {
            $f->add(new Horde_Mobile_text(sprintf(_("Ambiguous matches for \"%s\" (first 5 matches displayed):"), $expand[$key][0])));
        } else {
            $f->add(new Horde_Mobile_text(sprintf(_("Ambiguous matches for \"%s\":"), $expand[$key][0])));
        }

        foreach (array_slice($expand[$key][1], 0, 5) as $key2 => $val2) {
            $f->add(new Horde_Mobile_checkbox($key . '_expand_' . $key2, htmlspecialchars($val2), $val2, false));
        }
    }
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
$f->add(new Horde_Mobile_submit(_("Cancel"), 'a'));

$mimp_render->add($menu);
$mimp_render->display();
