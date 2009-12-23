<?php

$m = new Horde_Mobile($title);
$m->add(new Horde_Mobile_text(sprintf(_("Welcome to %s"), $registry->get('name'))));

$f = new Horde_Mobile_form('login.php');
$f->add(new Horde_Mobile_hidden('app', $app));
$f->add(new Horde_Mobile_hidden('url', Horde_Util::getFormData('url', '')));
$f->add(new Horde_Mobile_hidden('anchor_string', Horde_Util::getFormData('anchor_string', '')));

foreach ($loginparams as $key => $val) {
    switch ($val['type']) {
    case 'hidden':
        $f->add(new Horde_Mobile_hidden($key, isset($val['value']) ? $val['value'] : ''));
        break;

    case 'text':
        $f->add(new Horde_Mobile_input($key, isset($val['value']) ? $val['value'] : '', $val['label']));
        break;

    case 'password':
        $p = $f->add(new Horde_Mobile_input($key, isset($val['value']) ? $val['value'] : '', $val['label']));
        $p->set('type', 'password');
        break;

    case 'select':
        $s = $f->add(new Horde_Mobile_select($key, 'popup', $val['label']));
        foreach ($val['value'] as $k2 => $v2) {
            // Doesn't account for 'hidden' values
            $s->add($v2['name'], $k2, !empty($v2['selected']));
        }
    }
}

if (!empty($lang_select)) {
    $f->add($lang_select);
}

// Submit button.
$f->add(new Horde_Mobile_submit($title, 'login_button'));

// Add the form to the page.
$m->add($f);

// Display everything.
$m->display();
