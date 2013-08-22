<?php if ($GLOBALS['registry']->isAdmin()): ?>
<div id="hermesViewAdminjobs" style="display:none;">

  <div class="header"><span class="horde-collapse" id="hermesJobTypesFormCollapse"><?php echo _("Job Types")?></div>
  <form id="hermesJobForm" action="">
    <div id="hermesJobFormInputs">
      <input type="hidden" id="hermesJobFormId" name="job_id" />
      <label for="hermesJobFormName"><?php echo _("Name") ?>*
        <input type="text" name="name" id="hermesJobFormName" />
      </label><br />
      <label for="hermesJobFormBillable">
        <?php echo _("Billable")?>
        <input type="checkbox" name="billable" id="hermesJobFormBillable" />
        <span class="hermesSeparator">|</span>
      </label>
      <label for="hermesJobFormEnabled">
        <?php echo _("Enabled")?>
        <input type="checkbox" name="enabled" id="hermesJobFormEnabled" />
      </label><br />
      <label for="HermesJobFormRate"><?php echo _("Hourly Rate") ?>
        <input type="text" name="rate" id="hermesJobFormRate" />
      </label>
      <div class="horde-form-buttons">
        <input id="hermesJobSave" class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
        <input id="hermesJobSaveAsNew" class="horde-create" type="submit" value="<?php echo _("Save As New") ?>" style="display:none;" />
        <input id="hermesJobReset" type="reset" value ="<?php echo _("Reset") ?>" />
      </div>
    </div>
  </form>
  <div id="hermesJobTypeListHeader">
    <div class="hermesJobTypeListName hermesBorderLeft"><?php echo _("Name")?></div>
    <div class="hermesJobTypeListBillable hermesBorderLeft"><?php echo _("Billable")?></div>
    <div class="hermesJobTypeListEnabled hermesBorderLeft"><?php echo _("Enabled")?></div>
    <div class="hermesJobTypeListRate hermesBorderLeft"><?php echo _("Rate")?></div>
    <div class="hermesJobTypeListOptions">&nbsp;</div>
  </div>
  <div id="hermesJobTypeListBody">
    <div id="hermesJobTypeListTemplate" style="display:none;">
      <div class="hermesJobTypeListName hermesBorderLeft"></div>
      <div class="hermesJobTypeListBillable hermesBorderLeft"></div>
      <div class="hermesJobTypeListEnabled hermesBorderLeft"></div>
      <div class="hermesJobTypeListRate hermesBorderLeft"></div>
      <div class="hermesJobTypeListOptions"><span class="jobTypeEdit sliceControl">&nbsp;</span><span class="jobTypeDelete sliceControl">&nbsp;</span></div>
    </div>
    <div id="hermesJobTypeListInternal"></div>
  </div>

</div>
<?php endif; ?>