<form action="<?php echo htmlspecialchars(Horde::selfUrl(false, false)) ?>" method="post" name="matrix">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="formname" value="mtmatrix" />

<h1 class="header">
 <?php echo _("Queue/Type Matrix") ?>
</h1>

<table width="100%" cellspacing="0" class="linedRow">
<!-- Header row of types -->
 <tr>
  <th class="control">&nbsp;</th>
<?php foreach ($types as $tid => $type): ?>
  <th class="control" style="text-align:center" valign="bottom"><strong><?php echo Horde::link(Horde::url($tlink)->add('type', $tid), $type) . htmlspecialchars($type) ?></a></strong></th>
<?php endforeach; ?>
 </tr>

<!-- List each queue once for each type -->
<?php foreach ($queues as $mid => $queue): $selected = $whups_driver->getTypes($mid); ?>
 <tr>
  <td class="rightAlign"><strong><?php echo Horde::link(Horde::url($mlink)->add('queue', $mid), $queue) . htmlspecialchars($queue) ?></a></strong>&nbsp;&nbsp;</td>
<?php foreach ($types as $tid => $type): ?>
  <td style="text-align:center"><input type="checkbox" class="checkbox" name="matrix[<?php echo (int)$mid ?>][<?php echo (int)$tid ?>]"<?php if (!empty($selected[$tid])) echo ' checked="checked"' ?> /></td>
<?php endforeach; ?>
 </tr>
<?php endforeach; ?>
</table>

<div><input type="submit" class="button" value="<?php echo _("Update Associations") ?>" /></div>

</form>
