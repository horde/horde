<form id="activesyncadmin" name="activesyncadmin" action="<?php echo Horde::selfUrl()?>" method="post">
  <input type="hidden" name="actionID" id="actionID" />
  <input type="hidden" name="deviceID" id="deviceID" />
  <input type="hidden" name="uid" id="uid" />

  <h1 class="header"><?php echo _("ActiveSync Devices") ?>
  <h2 class="header"> <?php echo _("Search:") ?>
    <select id="searchBy" name="searchBy">
    <option value="username" <?php echo Horde_Util::getFormData('searchBy') == 'username' ? 'selected="selected"' : ''?>><?php echo  _("Username") ?></option>
    <option value="device_type" <?php echo Horde_Util::getFormData('searchBy') == 'device_type' ? 'selected="selected"' : ''?>><?php echo _("Device Type")?></option>
    <option value="device_id" <?php echo Horde_Util::getFormData('searchBy') == 'device_id' ? 'selected="selected"' : ''?>><?php echo _("Device Id")?></option>
    <option value="device_agent" <?php echo Horde_Util::getFormData('searchBy') == 'device_agent' ? 'selected="selected"' : ''?>><?php echo _("User Agent") ?></option>
    <?php if ($GLOBALS['conf']['activesync']['storage'] == 'Nosql'): ?>
    <option value="<?php echo Horde_ActiveSync_Device::MODEL?>" <?php echo Horde_Util::getFormData('searchBy') == Horde_ActiveSync_Device::MODEL ? 'selected="selected"' : ''?>><?php echo _("Model") ?></option>
    <option value="<?php echo Horde_ActiveSync_Device::IMEI?>" <?php echo Horde_Util::getFormData('searchBy') == Horde_ActiveSync_Device::IMEI ? 'selected="selected"' : ''?>><?php echo _("IMEI") ?></option>
    <option value="<?php echo Horde_ActiveSync_Device::NAME?>" <?php echo Horde_Util::getFormData('searchBy') == Horde_ActiveSync_Device::NAME ? 'selected="selected"' : ''?>><?php echo _("Common Name") ?></option>
    <option value="<?php echo Horde_ActiveSync_Device::OS?>" <?php echo Horde_Util::getFormData('searchBy') == Horde_ActiveSync_Device::OS ? 'selected="selected"' : ''?>><?php echo _("OS") ?></option>
    <option value="<?php echo Horde_ActiveSync_Device::VERSION?>" <?php echo Horde_Util::getFormData('searchBy') == Horde_ActiveSync_Device::VERSION ? 'selected="selected"' : ''?>><?php echo _("Version") ?></option>
    <option value="<?php echo Horde_ActiveSync_Device::PHONE_NUMBER?>" <?php echo Horde_Util::getFormData('searchBy') == Horde_ActiveSync_Device::PHONE_NUMBER ? 'selected="selected"' : ''?>><?php echo _("Phone Number") ?></option>
    <?php endif;?>
    <input type="text" id="searchInput" name="searchInput" value="<?php echo Horde_Util::getFormData('searchInput')?>" />
    <input class="horde-submit" type="submit" value="<?php echo _("Search") ?>" name="search" id="search" /></h1>
  </h2>

  <?php if ($this->devices): ?>
  <table class="horde-table activesync-devices striped">
   <tr class="header">
    <th class="smallheader"><?php echo _("User")?></th>
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
      <?php $status = $this->contentTag('span', _("Deivce is Blocked."), array('class' => 'notice'))?>
    <?php else: ?>
      <?php $status = $d->policykey ? _("Provisioned") : _("Not Provisioned") ?>
    <?php endif; ?>
    <tr>
      <td><?php echo $d->user?></td>
      <td><?php echo $d->deviceType ?></td>
      <td><?php echo $d->getLastSyncTimestamp() ? strftime($GLOBALS['prefs']->getValue('date_format') . ' %H:%M', $d->getLastSyncTimestamp()) : _("None") ?></td>
      <td><?php echo $status ?></td>
      <td>
        <?php foreach ($d->getFormattedDeviceProperties() as $key => $value): ?>
          <?php echo '<b>' . $key . '</b>: ' . $value . '<br />' ?>
        <?php endforeach; ?>
      </td>
      <td>
        <?php if ($d->policykey): ?>
          <input class="horde-delete" type="button" value="<?php echo _("Wipe") ?>" id="wipe_<?php echo $d->id ?>" />
        <?php endif; ?>
        <?php if ($d->rwstatus == Horde_ActiveSync::RWSTATUS_PENDING): ?>
          <input type="button" value="<?php echo _("Cancel Wipe") ?>" id="cancel_<?php echo $d->id ?>" />
        <?php endif; ?>
        <input class="horde-delete" type="button" value="<?php echo _("Remove") ?>" id="remove_<?php echo $d->id ?>" /><br />
        <br class="spacer" />
        <?php if ($d->blocked): ?>
          <input class="horde-button" type="button" value="<?php echo _("Unblock")?>" id="unblock_<?php echo $d->id?>" />
        <?php else: ?>
          <input class="horde-delete" type="button" value="<?php echo _("Block")?>" id="block_<?php echo $d->id?>" />
        <?php endif; ?>
      </td>
   </tr>
  <?php endforeach; ?>
  </table>
  <?php else: ?>
  <p><em><?php echo _("None") ?></em></p><br class="spacer" />
  <?php endif; ?>

  <br class="spacer" />
  <input class="horde-delete" type="submit" value="<?php echo _("Reset all device state") ?>" name="reset" id="reset" />
  <?php echo _("This will cause all devices to resyncronize all items.") ?>

  <p>
   <strong>
    <?php echo _("NOTE: WIPING A DEVICE MAY RESET IT TO FACTORY DEFAULTS. PLEASE MAKE SURE YOU REALLY WANT TO DO THIS BEFORE REQUESTING A WIPE") ?>
   </strong>
  </p>
</form>
