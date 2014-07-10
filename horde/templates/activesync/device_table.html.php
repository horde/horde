  <table class="horde-table activesync-devices striped">
   <tr class="header">
    <?php if ($this->isAdmin):?><th class="smallheader"><?php echo _("User")?></th><?php endif?>
    <th class="smallheader"><?php echo _("Device") ?></th>
    <th class="smallheader"><?php echo _("Last Sync Time") ?></th>
    <th class="smallheader"><?php echo _("Status") ?></th>
    <th class="smallheader"><?php echo _("Device Information") ?></th>
    <th class="smallheader"><?php echo _("Actions")?></th>
   </tr>
  <?php foreach ($this->devices as $d): ?>
    <?php if ($d->rwstatus == Horde_ActiveSync::RWSTATUS_PENDING): ?>
      <?php $status = $this->contentTag('span', _("Wipe Pending"), array('class' => 'notice')) ?>
    <?php elseif ($d->rwstatus == Horde_ActiveSync::RWSTATUS_WIPED): ?>
      <?php $status = $this->contentTag('span', _("Device is Wiped. Remove device state to allow device to reconnect."), array('class' => 'notice')) ?>
    <?php elseif ($d->blocked):?>
      <?php $status = $this->contentTag('span', _("Device is Blocked."), array('class' => 'notice'))?>
    <?php else: ?>
      <?php $status = $d->policykey ? _("Provisioned") : _("Not Provisioned") ?>
    <?php endif; ?>
    <tr>
      <?php if ($this->isAdmin):?><td><?php echo $d->user?></td><?php endif?>
      <td><?php echo $d->deviceType ?></td>
      <td><?php echo $d->getLastSyncTimestamp() ? strftime($GLOBALS['prefs']->getValue('date_format') . ' %H:%M', $d->getLastSyncTimestamp()) : _("None") ?></td>
      <td><?php echo $status ?></td>
      <td>
        <?php foreach ($d->getFormattedDeviceProperties() as $key => $value): ?>
          <?php echo '<b>' . $key . '</b>: ' . $value . '<br />' ?>
        <?php endforeach; ?>
        <b><?php echo _("Cached Heartbeat (seconds)")?></b>: <?php echo $d->hbinterval ?><br />
      </td>
      <td>
        <?php if ($d->policykey): ?>
          <input class="horde-delete" type="button" value="<?php echo _("Wipe") ?>" id="wipe_<?php echo $d->id . ':' . $d->user ?>" />
        <?php endif; ?>
        <?php if ($d->rwstatus == Horde_ActiveSync::RWSTATUS_PENDING): ?>
          <input type="button" value="<?php echo _("Cancel Wipe") ?>" id="cancel_<?php echo $d->id  . ':' . $d->user?>" />
        <?php endif; ?>
        <input class="horde-delete" type="button" value="<?php echo _("Remove") ?>" id="remove_<?php echo $d->id . ':' . $d->user ?>" /><br />
        <br class="spacer" />
        <?php if ($d->blocked && $this->isAdmin): ?>
          <input class="horde-button" type="button" value="<?php echo _("Unblock")?>" id="unblock_<?php echo $d->id . ':' . $d->user ?>" />
        <?php elseif ($this->isAdmin): ?>
          <input class="horde-delete" type="button" value="<?php echo _("Block")?>" id="block_<?php echo $d->id . ':' . $d->user ?>" />
        <?php endif; ?>
      </td>
   </tr>
  <?php endforeach; ?>
  </table>