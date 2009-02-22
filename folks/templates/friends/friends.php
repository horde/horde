<table style="width: 100%">
<tr valign="top">
<td>

<h1 class="header">
<span style="float: right"><a href="<?php echo Horde::applicationUrl('edit/friends/index.php') ?>"><?php echo _("Edit friends") ?></a></span>
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
