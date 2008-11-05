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
    <td><?php echo $user ?></td>
    <td><a href="<?php $remove_url . $user ?>"><?php echo _("Remove") ?></a></td>
</tr>
<?php } ?>
</tbody>
</table>

<?php
} 
echo $form->renderActive();