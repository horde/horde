<?php
if (empty($blacklist)) {
    echo '<ul class="notices"><li>';
    echo _("There are no users in your blacklist.");
    echo '</li>';
} else  {
?>
<h1 class="header"><?php echo $title ?></h1>
<table id="blacklist" class="sortable striped">
<thead>
<tr>
    <th><?php echo _("Username") ?></th>
    <th><?php echo _("Action") ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($blacklist as $user) { ?>
<tr>
    <td><?php echo '<img src="' . Folks::getImageUrl($user) . '" class="userMiniIcon" /> ' . $user ?></td>
    <td>
        <a href="<?php echo Util::addParameter($remove_url, 'user', $user) ?>"><?php echo $remove_img  . ' ' . _("Remove") ?></a>
        <a href="<?php echo Folks::getUrlFor('user', $user) ?>"><?php echo $profile_img  . ' ' . _("View profile") ?></a>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

<?php
}
echo $form->renderActive();