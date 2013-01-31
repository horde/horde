<div class="smallheader">
 <?php echo _("State Management") ?>
</div>

<p>
 <?php echo _("Reset all device state. This will cause your devices to resyncronize all items.") ?>
 <input class="horde-delete" type="submit" value="Reset" name="reset" />
</p>

<div class="smallheader">
 <?php echo _("Device Management") ?>
</div>
<?php if ($this->devices): ?>
<input type="hidden" id="removedevice" name="removedevice" />
<input type="hidden" name="wipeid" id="wipeid" />
<input type="hidden" name="cancelwipe" id="cancelwipe" />
<table class="horde-table striped">
 <tr>
  <th></th>
  <th><?php echo _("Device") ?></th>
  <th><?php echo _("Last Sync Time") ?></th>
  <th><?php echo _("Status") ?></th>
 </tr>
<?php foreach ($this->devices as $d): ?>
 <tr class="<tag:devices.class />">
  <td>
<?php if ($d['device_policykey']): ?>
   <input class="horde-delete" type="button" value="<?php echo _("Wipe") ?>" id="wipe_<?php echo $d['key'] ?>" />
<?php endif; ?>
<?php if ($d['ispending']): ?>
   <input type="button" value="<?php echo _("Cancel Wipe") ?>" id="cancel_<?php echo $d['key'] ?>" />
<?php endif; ?>
   <input class="horde-delete" type="button" value="<?php echo _("Remove") ?>" id="remove_<?php echo $d['key'] ?>" />
  </td>
  <td><?php echo $d['device_type'] ?></td>
  <td><?php echo $d['ts'] ?></td>
  <td><?php echo $d['status'] ?></td>
 </tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>
 <em><?php echo _("None") ?></em>
</p>
<?php endif; ?>

<p>
 <strong>
  <?php echo _("NOTE: WIPING A DEVICE MAY RESET IT TO FACTORY DEFAULTS. PLEASE MAKE SURE YOU REALLY WANT TO DO THIS BEFORE REQUESTING A WIPE") ?>
 </strong>
</p>
