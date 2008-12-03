<?php

$row['forum_id'] = 9;

if (empty($row['form_id']) || ($row['form_ttl'] > $_SERVER['REQUEST_TIME'])) {
    return;
}

$form = $registry->callByPackage('ulaform', 'display', array($row['form_id']));
if ($form instanceof PEAR_Error) {
    echo $form;
} elseif ($form === true) {
    echo _("Thanks");
} else {
    echo '<h1 class="header">' . $form['title'] . '</h1>';
    echo $form['form'];
}
