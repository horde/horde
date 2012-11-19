<div id="hermesViewAdminjobs" style="display:none;">
  <div class="header"><?php echo _("Job Types")?><?php echo Hermes::getJobTypeSelect('hermesJobTypeSelect'); ?></div>
  <form id="hermesJobForm" action="">
    <input type="hidden" id="hermesJobFormId" name="job_id" />
    <label for="HermesJobFormName"><?php echo _("Name") ?>*
      <input class="input" type="text" name="name" id="hermesJobFormName" />
    </label><br />
    <label for="hermesJobFormBillable">
      <?php echo _("Billable")?>
      <input type="checkbox" name="billable" id="hermesJobFormBillable" checked="checked" />
      <span class="hermesSeparator">|</span>
    </label>
    <label for="hermesJobFormEnabled">
      <?php echo _("Enabled")?>
      <input type="checkbox" name="enabled" id="hermesJobFormEnabled" checked="checked" />
    </label><br />
    <label for="HermesJobFormRate"><?php echo _("Hourly Rate") ?>
      <input class="input" type="text" name="rate" id="hermesJobFormRate" />
    </label>
    <div class="horde-form-buttons">
      <input id="hermesJobSave" class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
      <input id="hermesJobSaveAsNew" class="horde-create" type="submit" value="<?php echo _("Save As New") ?>" style="display:none;" />
      <input id="hermesJobReset" type="reset" value ="<?php echo _("Reset") ?>" />
    </div>
  </form>
</div>