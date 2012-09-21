<?php
if ($friends->hasCapability('groups_add')) {
    echo $form->renderActive(null, null, '', 'post');
}

?>

<h1 class="header"><?php echo $title ?></h1>
<table id="groups" class="sortable striped" style="width: 100%">
<thead>
<tr>
    <th><?php echo _("Group") ?></th>
    <th><?php echo _("Owner") ?></th>
    <th colsan="3"><?php echo _("Action") ?></th>
</tr>
</thead>
<tbody>
<?php

foreach ($groups as $group_id => $group_name) {
    echo '<tr><td>' . $group_name . '</td>';
    $owner = $friends->getGroupOwner($group_id);
    echo '<td style="text-align: center"><a href="' . Folks::getUrlFor('user', $owner) .'"><img src="' . Folks::getImageUrl($owner) . '" class="userMiniIcon" /><br />' . $owner . '</a></td>';
    echo '<td><a href="' . $members_url->add('g', $group_id) . '">' . $members_img . ' ' . _("Members") . '</a></td>';
    if ($friends->hasCapability('groups_add')) {
        echo '<td><a href="' . $edit_url->add('g', $group_id) . '">' . $edit_img . ' ' . _("Rename") . '</a></td>';
        echo '<td><a href="#" onclick="if (confirm(\'' . _("Do you really want to delete this group?") . '\')) {window.location=\'' .  $remove_url->add('g', $group_id) . '\'}">' . $remove_img . ' ' . _("Delete") . '</a></td>';
        echo '<td><a href="#" onclick="' . Horde::popupJs($perms_url, array('params' => array('cid' => $group_id), 'urlencode' => true)) . '">' . $perms_img . ' ' .  _("Permissions") . '</a></td>';
    }
    echo '</tr>';
}
?>
</tbody>
</table>
