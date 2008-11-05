
<?php echo $form->renderActive(); ?>

<br />

<table style="width: 100%">
<tr valign="top">
<td>

<h1 class="header">
<span style="float: right"><a href="<?php echo Horde::applicationUrl('edit/friends.php') ?>"><?php echo _("Edit friends") ?></a></span>
<?php echo _("Friends activities") ?></h1>
<table id="friendactivities" class="striped sortable"  style="width: 100%">
<thead>
<tr>
    <th><?php echo _("Username") ?></th>
    <th><?php echo _("Date") ?></th>
    <th><?php echo _("Activity") ?></th>
</tr>
</thead>
<tbody>
<?php
foreach ($firendActivities as $activity_date => $activity) {
    echo '<tr><td><a href="' . Folks::getUrlFor('user', $activity['user']) . '">'
            . '<img src="' . Folks::getImageUrl($activity['user']) . '" class="userMiniIcon" /> '
                . $activity['user'] . '</a></td>'
            . ' <td>' . Folks::format_datetime($activity_date)  . '</td> '
            . '<td>' . $activity['message'] . '</td></tr>';
}
?>

</table>

<br />

<h1 class="header">
<span style="float: right"><a href="<?php echo Horde::applicationUrl('edit/activity.php') ?>"><?php echo _("Edit activities") ?></a></span>
<?php echo _("Your activities") ?>
</h1>
<table id="activities" class="striped sortable" style="width: 100%">
<thead>
<tr>
    <th><?php echo _("Application") ?></th>
    <th><?php echo _("Date") ?></th>
    <th><?php echo _("Activity") ?></th>
</tr>
</thead>
<tbody>
<?php
foreach ($activities as $activity) {
$scope = explode(':', $activity['activity_scope']);
?>
<tr>
    <td nowrap="nowrap"><a href="<?php echo $registry->get('webroot', $scope[0]) ?>" />
        <img src="<?php echo $registry->getImageDir($scope[0]) . '/' . $scope[0] ?>.png" />
        <?php echo $registry->get('name', $scope[0]) ?></a>
    </td>
    <td><?php echo Folks::format_datetime($activity['activity_date']) ?></td>
    <td><?php echo $activity['activity_message']; unset($activity['activity_message']); ?></td>
</tr>
<?php } ?>
</tbody>
</table>

</td>
<td>

<h1 class="header" nowrap="nowrap"><?php echo _("Online friends") ?></h1>
<?php
foreach ($friend_list as $user) {
    if (!array_key_exists($user, $online)) {
        continue;
    }
    $img = Folks::getImageUrl($user);
    echo '<a href="' .  Folks::getUrlFor('user', $user) . '" title="' . $user . '">'
        . '<img src="' . $img . '" class="userMiniIcon" /></a>';
}
?>

<br />
<br />

<h1 class="header"><?php echo $title ?></h1>
<?php
foreach ($friend_list as $user) {
    $img = Folks::getImageUrl($user);
    echo '<a href="' .  Folks::getUrlFor('user', $user) . '" title="' . $user . '">'
        . '<img src="' . $img . '" class="userMiniIcon" /></a>';
}
?>

</td>
</tr>
</table>