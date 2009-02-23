<?php echo $form->renderActive(); ?>
<br />

<table style="width: 100%">
<tr valign="top">
<td>

<h1 class="header">
<span style="float: right"><a href="<?php echo Horde::applicationUrl('edit/friends/index.php') ?>"><?php echo _("Edit friends") ?></a></span>
<?php echo _("Friends activities") ?></h1>
<table id="friendactivities" class="striped sortable"  style="width: 100%">
<thead>
<tr>
    <th><?php echo _("User") ?></th>
    <th><?php echo _("Activity") ?></th>
</tr>
</thead>
<tbody>
<?php
foreach ($firendActivities as $activity_date => $activity) {
    echo '<tr>'
            . '<td><a href="' . Folks::getUrlFor('user', $activity['user']) . '">'
            . '<img src="' . Folks::getImageUrl($activity['user']) . '" class="userMiniIcon" /> '
                . $activity['user'] . '</a></td>'
            . ' <td>' . $activity['message'] . '<br />' .
                    '<span class="small">' . Folks::format_datetime($activity_date) . '</span>'
            . '</td></tr>';
}
?>

</table>

<br />
<br />

<h1 class="header">
<span style="float: right"><a href="<?php echo Horde::applicationUrl('edit/activity.php') ?>"><?php echo _("Edit activities") ?></a></span>
<?php echo _("Your activities") ?>
</h1>
<table id="activities" class="striped sortable" style="width: 100%">
<thead>
<tr>
    <th><?php echo _("Application") ?></th>
    <th><?php echo _("Activity") ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($activities as $activity) { ?>
<tr>
    <td><a href="<?php echo $registry->get('webroot', $activity['activity_scope']) ?>" />
        <img src="<?php echo $registry->getImageDir($activity['activity_scope']) . '/' . $activity['activity_scope'] ?>.png" />
        <?php echo $registry->get('name', $activity['activity_scope']) ?></a>
    </td>
    <td>
        <?php echo $activity['activity_message']; unset($activity['activity_message']); ?><br >
        <span class="small"><?php echo Folks::format_datetime($activity['activity_date']) ?></span>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

</td>
<td>

<h1 class="header"><?php echo _("Online friends") ?></h1>
<?php
// Prepare actions
$actions = array(
    array('url' => Horde::applicationUrl('user.php'),
          'id' => 'user',
          'name' => _("View profile")));
if ($registry->hasInterface('letter')) {
    $actions[] = array('url' => $registry->callByPackage('letter', 'compose', ''),
                        'id' => 'user_to',
                        'name' => _("Send message"));
}
$list = array_intersect($friend_list, array_flip($online));
require FOLKS_TEMPLATES . '/block/users.php';
?>

<br />
<br />

<h1 class="header"><?php echo _("People you might know") ?></h1>
<?php
// Prepare actions
$actions = array(
    array('url' => Horde::applicationUrl('edit/friends/add.php'),
          'id' => 'user',
          'name' => _("Add friend")),
    array('url' => Horde::applicationUrl('user.php'),
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
    array('url' => Horde::applicationUrl('user.php'),
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

</td>
</tr>
</table>

