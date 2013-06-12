<form action="<?php echo htmlspecialchars(Horde::selfUrl(false, false)) ?>" method="post" name="matrix">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="formname" value="mtmatrix" />

<h1 class="header">
 <?php echo _("Queue/Type Matrix") ?>
</h1>

<br />
<table class="horde-table">
 <thead><tr>
  <th class="rightAlign">&nbsp;</th>
<?php foreach ($types as $tid => $type): ?>
  <th style="text-align:center"><strong><?php echo Horde::link(Horde::url($tlink)->add('type', $tid), $type) . htmlspecialchars($type) ?></a></strong></th>
<?php endforeach; ?>
 </tr></thead>

<?php foreach ($queues as $mid => $queue): $selected = $whups_driver->getTypes($mid); ?>
 <tr>
  <td class="rightAlign"><strong><?php echo Horde::link(Horde::url($mlink)->add('queue', $mid), $queue) . htmlspecialchars($queue) ?></a></strong></td>
<?php foreach ($types as $tid => $type): ?>
  <td style="text-align:center"><input type="checkbox" class="checkbox" name="matrix[<?php echo (int)$mid ?>][<?php echo (int)$tid ?>]"<?php if (!empty($selected[$tid])) echo ' checked="checked"' ?> /></td>
<?php endforeach; ?>
 </tr>
<?php endforeach; ?>
</table>

<div class="horde-form-buttons"><input type="submit" class="horde-default" value="<?php echo _("Update Associations") ?>" /></div>

</form>
