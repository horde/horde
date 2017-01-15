<div id="hermesViewAdmindeliverables" style="display:none;">
<div class="header"><span class="horde-collapse" id="hermesDeliverablesFormCollapse"><?php echo _("Deliverables")?></span></div>
<form id="hermesDeliverablesForm" action="">
    <div id="hermesDeliverablesFormInputs">
      <input type="hidden" id="hermesDeliverablesClient" name="client_id" />
      <input type="hidden" id="hermesDeliverablesId" name="deliverable_id" />
      <label for="hermesDeliverablesFormName"><?php echo _("Name") ?>*
        <input type="text" name="name" id="hermesDeliverablesFormName" />
      </label><br />
      <label for="hermesDeliverablesFormActive"><?php echo _("Active") ?>
        <input type="checkbox" name="active" id="hermesDeliverablesFormActive" />
        </label><br />
      <label for="hermesDeliverablesFormEstimate"><?php echo _("Estimate") ?>
        <input type="text" name="estimate" id="hermesDeliverablesFormEstimate" />
        </label><br />
      <label for="hermesDeliverablesFormDesc"><?php echo _("Description") ?><br />
        <textarea rows="3" name="desc" id="hermesDeliverablesFormDesc" class="hermesLongField"></textarea>
      </label>
      <div class="horde-form-buttons">
        <input id="hermesDeliverablesSave" class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
        <input id="hermesDeliverablesSaveAsNew" class="horde-create" type="submit" value="<?php echo _("Save As New") ?>" style="display:none;" />
        <input id="hermesDeliverablesReset" type="reset" value ="<?php echo _("Reset") ?>" />
      </div>
    </div>
  </form>
  <div class="header"><?php echo Hermes::getClientSelect('hermesDeliverablesClientSelect', false, true);?></div>
  <div id="hermesDeliverablesListHeader">
    <div class="hermesDeliverablesName hermesBorderLeft"><?php echo _("Name") ?></div>
    <div class="hermesDeliverablesActive hermesBorderLeft"><?php echo _("Active") ?></div>
    <div class="hermesDeliverablesEstimate hermesBorderLeft"><?php echo _("Estimate") ?></div>
    <div class="hermesDeliverablesActual hermesBorderLeft"><?php echo _("Actual") ?></div>
    <div class="hermesDeliverablesDesc hermesBorderLeft"><?php echo _("Description") ?></div>
    <div class="hermesDeliverablesOptions">&nbsp;</div>
  </div>
  <div id="hermesDeliverablesBody">
    <div id="hermesDeliverablesTemplate" style="display:none;">
      <div class="hermesDeliverablesName hermesBorderLeft"></div>
      <div class="hermesDeliverablesActive hermesBorderLeft"></div>
      <div class="hermesDeliverablesEstimate hermesBorderLeft"></div>
      <div class="hermesDeliverablesActual hermesBorderLeft"></div>
      <div class="hermesDeliverablesDesc hermesBorderLeft"></div>
      <div class="hermesDeliverablesOptions"><span class="deliverableEdit sliceControl">&nbsp;</span><span class="deliverableDelete sliceControl">&nbsp;</span><span class="deliverableDetail sliceControl">&nbsp;</span></div>
    </div>
    <div id="hermesDeliverablesListInternal"></div>
  </div>
</div>