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
    <?php echo $this->render('device_table'); ?>
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
