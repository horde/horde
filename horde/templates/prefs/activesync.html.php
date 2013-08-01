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
 <tr class="header">
  <th></th>
  <th class="smallheader"><?php echo _("Device") ?></th>
  <th class="smallheader"><?php echo _("Last Sync Time") ?></th>
  <th class="smallheader"><?php echo _("Status") ?></th>
  <th class="smallheader"><?php echo _("Device Information") ?></th>
 </tr>
<?php foreach ($this->devices as $d): ?>
 <tr>
  <td>
    <?php if ($d->policykey): ?>
      <input class="horde-delete" type="button" value="<?php echo _("Wipe") ?>" id="wipe_<?php echo $d->id ?>" />
    <?php endif; ?>
    <?php if ($d->rwstatus == Horde_ActiveSync::RWSTATUS_PENDING): ?>
      <?php $status = $this->contentTag('span', _("Wipe Pending"), array('class' => 'notice')) ?>
      <input type="button" value="<?php echo _("Cancel Wipe") ?>" id="cancel_<?php echo $d->id ?>" />
    <?php elseif ($d->rwstatus == Horde_ActiveSync::RWSTATUS_WIPED): ?>
      <?php $status = $this->contentTag('span', _("Device is Wiped"), array('class' => 'notice')) ?>
    <?php else: ?>
      <?php $status = $d->policykey ? _("Provisioned") : _("Not Provisioned") ?>
    <?php endif; ?>
    <input class="horde-delete" type="button" value="<?php echo _("Remove") ?>" id="remove_<?php echo $d->id ?>" />
  </td>
  <td><?php echo $d->deviceType ?></td>
  <td><?php echo $d->getLastSyncTimestamp() ? strftime($GLOBALS['prefs']->getValue('date_format') . ' %H:%M', $d->getLastSyncTimestamp()) : _("None") ?></td>
  <td><?php echo $status ?></td>
  <td>
    <?php foreach ($d->getFormattedDeviceProperties() as $key => $value): ?>
      <?php echo '<b>' . $key . '</b>: ' . $value . '<br />' ?>
    <?php endforeach; ?>
  </td>
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
