<?php

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

if ($quickText = Horde_Util::getPost('quickText')) {
    $result = $registry->tasks->quickAdd($quickText);
    if (!$result) {
        throw new Nag_Exception($result);
    }
    $notification->push(sprintf(ngettext("Added %d task", "Added %d tasks", count($result)), count($result)), 'horde.success');
    Horde::url('list.php', true)->redirect();
}
