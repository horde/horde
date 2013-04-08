<div class="smallheader">
 <?php echo _("Timestamps of successful synchronization sessions") ?>
</div>

<?php if ($this->devices): ?>
<input type="hidden" id="removedb" name="removedb" />
<input type="hidden" id="removedevice" name="removedevice" />
<table class="striped">
 <tr>
  <th><?php echo _("Device") ?></th>
  <th><?php echo _("Database") ?></th>
  <th><?php echo _("Server Time") ?></th>
  <th><?php echo _("Client Anchor") ?></th>
  <th><?php echo _("Delete") ?></th>
 </tr>
<?php foreach ($this->devices as $d): ?>
 <tr>
  <td><?php echo $this->h($d['device']) ?></td>
  <td><?php echo $this->h($d['db']) ?></td>
  <td><?php echo $d['time'] ?></td>
  <td><?php echo $this->h($d['anchor']) ?></td>
  <td>
   <input class="horde-delete" type="button" value="<?php echo _("Delete") ?>" onclick="HordeSyncMLPrefs.removeAnchor('<?php echo $d['deviceid'] ?>', '<?php echo $d['rawdb'] ?>')" />
  </td>
 </tr>
<?php endforeach; ?>
</table>

<p>
 <input type="submit" class="horde-delete" name="deleteall" value="<?php echo _("Delete All SyncML Data") ?>" />
</p>
<?php else: ?>
<p>
 <em><?php echo _("None") ?></em>
</p>
<?php endif; ?>
