<table id="activities" class="striped"  style="width: 100%">
<tbody>
<?php
foreach ($list as $activity_date => $activity) {
    echo '<tr>'
            . '<td><a href="' . Folks::getUrlFor('user', $activity['user_uid']) . '">'
            . '<img src="' . Folks::getImageUrl($activity['user_uid']) . '" class="userMiniIcon" style="float: left" /> '
            . $activity['user_uid'] . '</a> - <span class="small">' . Folks::format_datetime($activity_date) . '</span>' . '<br />'
            . $activity['activity_message'] . '<br />'
            . '</td></tr>';
}
?>
</tbody>
</table>
