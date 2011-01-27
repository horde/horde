<?php

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

if ($quickText = Horde_Util::getPost('quickText')) {
    try {
        $result = Nag::createTasksFromText($quickText, Nag::getDefaultTasklist(Horde_Perms::EDIT));
    } catch (Nag_Exception $e) {
        $notification->push($e->getMessage());
    }
    if ($result) {
        $notification->push(sprintf(ngettext("Added %d task", "Added %d tasks", count($result)), count($result)), 'horde.success');
    } else {
        $notification->push(_("No tasks have been added."), 'horde.warning');
    }
} else {
    $notification->push(_("No tasks have been added."), 'horde.warning');
}
Horde::url('list.php', true)->redirect();
