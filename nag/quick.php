<?php

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

if ($quickText = Horde_Util::getPost('quickText')) {
    $result = $registry->tasks->quickAdd($quickText);
    if ($result) {
        if (count($result) == 1) {
            $notification->push(_("Added one task"), 'horde.success');
        } else {
            $notification->push(sprintf(_("Added %s tasks"), count($result)), 'horde.success');
        }
        Horde::applicationUrl('list.php', true)->redirect();
    }
    Horde::fatal($result);
}
