<?php echo $form->renderActive(null, null, '', 'post'); ?>

<h1 class="header"><?php echo $title ?></h1>
<table id="groups" class="sortable striped">
<thead>
<tr>
    <th><?php echo _("Group") ?></th>
    <th><?php echo _("Actions") ?></th>
</tr>
</thead>
<tbody>
<?php
foreach ($groups as $group_id => $grouo_name) {
    echo '<tr><td>' . $grouo_name . '</td>';
    echo '<td><a href="' . Util::addParameter($edit_url, 'g', $group_id) . '">' . $edit_img . ' ' . _("Rename") . '</a></td>';
    echo '<td><a href="#" onclick="if (confirm(\'' . _("Do you really want to delete this group?") . '\')) {window.location=\'' .  Util::addParameter($remove_url, 'g', $group_id) . '\'}">' . $remove_img . ' ' . _("Delete") . '</a></td>';
    echo '<td><a href="#" onclick="popup(\'' . Util::addParameter($perms_url, 'cid', $group_id) . '\')">' . $perms_img . ' ' .  _("Permissions") . '</a></td>';
    echo '</tr>';
}
?>
</tbody>
</table>
