<?php echo $form->renderActive(); ?>
<br />

<table style="width: 100%">
<tr valign="top">
<td>

<h1 class="header">
<span style="float: right"><a href="<?php echo Horde::url('edit/friends/index.php') ?>"><?php echo _("Edit friends") ?></a></span>
<?php echo _("Friends activities") ?></h1>
<?php
$list = $firendActivities;
require FOLKS_TEMPLATES . '/block/activities.php';
?>

</td>
<td>

<h1 class="header"><?php echo _("People you might know") ?></h1>
<?php
// Prepare actions
$actions = array(
    array('url' => Horde::url('edit/friends/add.php'),
          'id' => 'user',
          'name' => _("Add friend")),
    array('url' => Horde::url('user.php'),
          'id' => 'user',
          'name' => _("View profile")));
if ($registry->hasInterface('letter')) {
    $actions[] = array('url' => $registry->callByPackage('letter', 'compose', ''),
                        'id' => 'user_to',
                        'name' => _("Send message"));
}
$list = $friends->getPossibleFriends(20);
require FOLKS_TEMPLATES . '/block/users.php';
?>

<br />
<br />

<h1 class="header"><?php echo $title ?></h1>
<?php
// Prepare actions
$actions = array(
    array('url' => Horde::url('user.php'),
          'id' => 'user',
          'name' => _("View profile")));
if ($registry->hasInterface('letter')) {
    $actions[] = array('url' => $registry->callByPackage('letter', 'compose', ''),
                        'id' => 'user_to',
                        'name' => _("Send message"));
}
$list = $friend_list;
require FOLKS_TEMPLATES . '/block/users.php';
?>

<br />
<br />

<h1 class="header">
<span style="float: right"><a href="<?php echo Horde::url('edit/activity.php') ?>"><?php echo _("Edit activities") ?></a></span>
<?php echo _("Your activities") ?>
</h1>
<?php
$list = $activities;
require FOLKS_TEMPLATES . '/block/activities.php';
?>

</td>
</tr>
</table>

