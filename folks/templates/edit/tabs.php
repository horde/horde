<h1 class="header"><?php echo _("Friends") ?></h1>
<br />
<br />

<?php

// Users online
$online = $folks_driver->getOnlineUsers();
if ($online instanceof PEAR_Error) {
    return $online;
}

// Get groups
$groups = $friends->getGroups();
if ($groups instanceof PEAR_Error) {
    $notification->push($groups);
    $groups = array();
}

$vars = Variables::getDefaultVariables();
$ftabs = new Horde_UI_Tabs('ftab', $vars);

$ftabs->addTab(_("All"), Horde::applicationUrl('edit/friends/index.php'), 'all');
$ftabs->addTab(_("Invite"), Horde::applicationUrl('edit/friends/invite.php'), 'invite');

foreach ($groups as $group_id => $group_name) {
    $ftabs->addTab($group_name, Horde::applicationUrl('edit/friends/friends.php'), $group_id);
}

$ftabs->addTab(_("Wainting for"), Horde::applicationUrl('edit/friends/for.php'), 'for');
$ftabs->addTab(_("Wainting from"), Horde::applicationUrl('edit/friends/from.php'), 'from');
$ftabs->addTab(_("I am friend of"), Horde::applicationUrl('edit/friends/of.php'), 'of');

echo $ftabs->render();

