<?php

echo $form->renderActive();

if (empty($activities)) {
    echo '<ul class="notices"><li>';
    echo _("There is no activity logged for your account.");
    echo '</li></ul>';
    return;
}
?>
<h1 class="header"><?php echo $title ?></h1>
<table id="activities" class="striped sortable" style="width: 100%">
<thead>
<tr>
    <th><?php echo _("Application") ?></th>
    <th><?php echo _("Date") ?></th>
    <th><?php echo _("Activity") ?></th>
    <th><?php echo _("Action") ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($activities as $activity) { ?>
<tr>
    <td><a href="<?php echo $registry->get('webroot', $activity['activity_scope']) ?>" />
        <img src="<?php echo Horde_Themes::img($activitiy['activity_scope'] . 'png', $activity['activity_scope']) ?>" />
        <?php echo $registry->get('name', $activity['activity_scope']) ?></a>
    </td>
    <td><?php echo Folks::format_datetime($activity['activity_date']) ?></td>
    <td><?php echo $activity['activity_message']; unset($activity['activity_message']); ?></td>
    <td><a href="<?php echo Horde_Util::addParameter($delete_url, $activity) ?>" title="<?php echo _("Delete") ?>"/><?php echo $delete_img ?></a></td>
</tr>
<?php } ?>
</tbody>
</table>
