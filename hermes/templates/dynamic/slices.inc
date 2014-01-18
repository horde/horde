<!-- Main Time View Container-->
<div id="hermesViewTime" style="display:none">

  <!-- Time Entry Form -->
  <div class="header"><span class="horde-collapse" id="hermesTimeFormCollapse"><?php echo _("Time Entry")?></span></div>
  <form id="hermesTimeForm" action="">
    <input type="hidden" name="id" id="hermesTimeFormId" value="0" />
    <div id="hermesTimeFormInputs">
      <div class="hermesTimeFormGeneral">
        <div id="hermesLoadingTime" class="hermesLoading" style="display:none"></div>
        <?php if ($registry->isAdmin(array('permission' => 'hermes:timeadmin'))): ?>
        <label for="hermesTimeFormEmployee"><?php echo _("Employee") ?>
          <?php $userType = Hermes::getEmployeesType();
          if ($userType[0] == 'text'):?>
            <input type="text" id="hermesTimeFormEmployee" name="employee" />
          <?php else: ?>
            <select name="employee" id="hermesTimeFormEmployee">
              <?php foreach ($userType[1] as $username => $user_label): ?>
              <?php $selected = ($username == $GLOBALS['registry']->getAuth()) ? 'selected="selected"' : ''; ?>
                <option <?php echo $selected ?>value="<?php echo $username ?>"><?php echo htmlspecialchars($user_label) ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
          </label>
        <?php else: ?>
          <input type="hidden" id="hermesTimeFormEmployee" name="employee" />
        <?php endif; ?>
        <label for="hermesTimeFormClient">
          <?php echo _("Client")?>
          <?php echo Hermes::getClientSelect('hermesTimeFormClient');?>
        </label><span class="hermesSeparator">|</span>
        <label for="hermesTimeFormCostobject">
          <?php echo _("Cost Object")?>
          <select type="text" id="hermesTimeFormCostobject" name="costobject"></select>
        </label><span class="hermesSeparator">|</span>
        <label for="HermesTimeFormStartDate">
          <?php echo _("Date") ?>*
          <input class="input" type="text" name="start_date" id="hermesTimeFormStartDate" size="10" />
          <?php echo Horde::img('picker.png', _("Set start date"), 'id="hermesTimeFormStartPicker" class="hermesDatePicker"') ?>
        </label><span class="hermesSeparator">|</span>
        <label for="hermesTimeFormHours">
          <?php echo _("Hours")?>*
          <input class="input" type="text" id="hermesTimeFormHours" name="hours" />
        </label><span class="hermesSeparator">|</span>
        <label for="hermesTimeFormBillable">
          <?php echo _("Billable")?>
          <input type="checkbox" name="billable" id="hermesTimeFormBillable" checked="checked" />
          <span class="hermesSeparator">|</span>
        </label>
        <label for="hermesTimeFormType">
          <?php echo _("Job Type")?>*
          <?php echo Hermes::getJobTypeSelect('hermesTimeFormJobtype');?>
        </label>
      </div>
      <div class="hermesTimeFormDetails">
        <label for="hermesTimeFormDesc"><?php echo _("Description")?>*<br />
          <textarea  name="description" id="hermesTimeFormDesc" rows="2" class="hermesLongField"></textarea>
        </label><br />
        <label for="hermesTimeFormNotes"><?php echo _("Additional Notes")?><br />
          <textarea  name="notes" id="hermesTimeFormNotes" rows="3" class="hermesLongField"></textarea>
        </label>
      </div>
    </div>
    <div class="horde-form-buttons">
      <input id="hermesTimeSave" class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
      <input id="hermesTimeSaveAsNew" class="horde-create" type="submit" value="<?php echo _("Save As New") ?>" style="display:none;" />
      <input id="hermesTimeReset" type="reset" value ="<?php echo _("Reset") ?>" />
      <input id="hermesReturnToSearch" class="horde-cancel" type="button" value="<?php echo _("Return to search results")?>" />
    </div>
  </form>
  <!-- End Time Entry Form -->

  <!-- Time Slice Grid -->
  <div id="hermesSlices">
    <div class="header"><?php echo _("My Unsubmitted Time")?>
      <span class="QuickFinderWrapper"><input type="text" name="quicksearchT" id="quicksearchT" title="<?php echo _("Quick Filter")?>" for="hermesTimeListInternal" empty="hermesTimeListEmpty" /></span>
    </div>

    <div id="hermesTimeListHeader">
      <div class="hermesUnselectedSlice hermesTimeListSelect">&nbsp;</div>
      <div class="hermesTimeListDate hermesBorderLeft"><a class="widget" id="sortDate"><?php echo _("Date")?></a></div>
      <div class="hermesTimeListClient hermesBorderLeft"><a class="widget" id="sortClient"><?php echo _("Client")?></a></div>
      <div class="hermesTimeListCostObject hermesBorderLeft"><a class="widget" id="sortCostObject"><?php echo _("Cost Object")?></a></div>
      <div class="hermesTimeListType hermesBorderLeft"><a class="widget" id="sortType"><?php echo _("Job Type")?></a></div>
      <div class="hermesTimeListDesc hermesBorderLeft"><a class="widget" id="sortDesc"><?php echo _("Description")?></a></div>
      <div class="hermesTimeListBill hermesBorderLeft"><a class="widget" id="sortBill"><?php echo _("Bill") ?></a></div>
      <div class="hermesTimeListHours hermesBorderLeft"><a class="widget" id="sortHours"><?php echo _("Hours")?></a></div>
      <div class="hermesTimeSliceOptions">&nbsp;</div>
    </div>
    <div id="hermesTimeListBody">
      <div id="hermesTimeListTemplate" style="display:none;">
        <div class="hermesUnselectedSlice hermesTimeListSelect">&nbsp;</div>
        <div class="hermesTimeListDate hermesBorderLeft"></div>
        <div class="hermesTimeListClient hermesBorderLeft"></div>
        <div class="hermesTimeListCostObject hermesBorderLeft"></div>
        <div class="hermesTimeListType hermesBorderLeft"></div>
        <div class="hermesTimeListDesc hermesBorderLeft"></div>
        <div class="hermesTimeListBill hermesBorderLeft"></div>
        <div class="hermesTimeListHours hermesBorderLeft"></div>
        <div class="hermesTimeSliceOptions"><span class="sliceEdit sliceControl">&nbsp;</span><span class="sliceDelete sliceControl">&nbsp;</span></div>
      </div>
      <div id="hermesTimeListInternal"></div>
      <div id="hermesTimeListEmpty" style="display:none;"><?php echo _("No Time Slices")?></div>
    </div>
    <div id="hermesTimeListControls" class="horde-form-buttons">
      <input id="hermesTimeListSubmit" disabled="disabled" class="horde-button" type="button" value="<?php echo _("Submit Time")?>" />
    </div>
  </div>
  <!-- End of Time Slice Grid -->

</div>
<!-- End of Time View Container -->

<div id="hermesDeleteDiv" class="hermesDialog" style="display:none">
<p><?php echo _("Delete the time slice?") ?></p>
<div class="hermesFormActions">
  <input id="hermesDeleteYes" type="button" value="<?php echo _("Yes") ?>" class="horde-default" />
  <input id="hermesDeleteNo" type="button" value="<?php echo _("No") ?>" />
</div>
</div>