<h1 class="header"><?php echo _("Friends") ?></h1>
<table style="width: 100%">
<tr valign="top">
<td style="width: 120px">

<table class="striped"  style="width: 100%">
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

echo '<tr><td><a href="' . Horde::url('edit/friends/add.php') . '">' . _("Add") . '</a>';
echo '<tr><td><a href="' . Horde::url('edit/friends/invite.php') . '">' . _("Invite") . '</a>';
echo '<tr><td><a href="' . Horde::url('edit/friends/index.php') . '">' . _("All") . '</a>';

foreach ($groups as $group_id => $group_name) {
    echo '<tr><td><a href="' . Horde_Util::addParameter(Horde::url('edit/friends/friends.php'), $group_id) . '">' . $group_name . '</a>';
}

echo '<tr><td><a href="' . Horde::url('edit/friends/know.php') . '">' . _("Might know") . '</a>';
echo '<tr><td><a href="' . Horde::url('edit/friends/for.php') . '">' . _("Wainting for") . '</a>';
echo '<tr><td><a href="' . Horde::url('edit/friends/from.php') . '">' . _("Wainting from") . '</a>';
echo '<tr><td><a href="' . Horde::url('edit/friends/of.php') . '">' . _("I am friend of") . '</a>';
echo '<tr><td><a href="' . Horde::url('edit/friends/blacklist.php') . '">' . _("Blacklist") . '</a>';

?>
</table>

<br />
<br />

<h1 class="header"><?php echo Horde::img('feed.png') . ' ' . _("Feeds") ?></h1>
<table class="striped">
<tr><td><a href="<?php echo Folks::getUrlFor('feed', 'online') ?>"><?php echo _("Online users") ?></a></td></tr>
<tr><td><a href="<?php echo Folks::getUrlFor('feed', 'friends') ?>"><?php echo _("Online friends") ?></a></td></tr>
<tr><td><a href="<?php echo Folks::getUrlFor('feed', 'activity') ?>"><?php echo _("Friends activity") ?></a></td></tr>
<tr><td><a href="<?php echo Folks::getUrlFor('feed', 'know') ?>"><?php echo _("People you might know") ?></a></td></tr>
</table>

</td>
<td>
