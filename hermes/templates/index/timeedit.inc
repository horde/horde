<div id="hermesViewTime" style="display:none">
  <div id="hermesLoadingTime" class="hermesLoading"></div>
  <div id="hermesViewTimeContainer">
   <div id="hermesTimeEntry">
    <h2><?php echo _("Time Entry")?></h2>
    <form id="hermesTimeForm" action="">
        <div id="hermesTimeFormInputs">
          <div class="hermesTimeFormGeneral">
            <label for="hermesTimeFormClient">
              <?php echo _("Client")?>
              <?php echo Hermes::getClientSelect();?>
            </label><span class="hermesSeparator">|</span>
            <label for="hermesTimeFormCostobject">
              <?php echo _("Costobject")?>
              <select type="text" id="hermesTimeFormCostobject" name="costobject"></select>
            </label><span class="hermesSeparator">|</span> <label for="HermesTimeFormStartDate">
              <?php echo _("Date") ?>
              <input class="input" type="text" name="start_date" id="hermesTimeFormStartDate" size="10" />
              <?php echo Horde::img('picker.png', _("Set start date"), 'id="hermesTimeFormStartPicker" class="hermesDatePicker"') ?>
            </label><span class="hermesSeparator">|</span>
             <label for="hermesTimeFormHours">
              <?php echo _("Hours")?>
              <input class="input" type="text" id="hermesTimeFormHours" name="hours" />
            </label><span class="hermesSeparator">|</span>
            <label for="hermesTimeFormType">
              <?php echo _("Job Type")?>
              <?php echo Hermes::getJobTypeSelect();?>
            </label>
          </div>
          <div class="hermesTimeFormDetails">
              <label for="hermesTimeFormDesc"><?php echo _("Description")?>:<br />
                <textarea  name="description" id="hermesTimeFormDesc" rows="2" class="hermesLongField"></textarea>
              </label><br />
              <label for="hermesTimeFormNotes"><?php echo _("Additional Notes")?>:<br />
                <textarea  name="notes" id="hermesTimeFormNotes" rows="3" class="hermesLongField"></textarea>
              </label>
          </div>
         </div>
         <div class="hermesFormActions">
          <input id="hermesTimeSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
          <input id="hermesTimeSaveAsNew" type="button" value="<?php echo _("Save As New") ?>" class="button ok" />
          <input id="hermesTimeDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
          <span class="hermesSeparator"><?php echo _("or") ?></span> <a class="hermesFormCancel"><?php echo _("Cancel") ?></a>
        </div>
    </form>
   </div>
   <div id="hermesTimeList">
       <h3><?php echo _("Unsubmitted Time");?></h1>
       <div id="hermesTimeListHeader">
        <div class="hermesUnselectedSlice hermesTimeListSelect">&nbsp;</div>
        <div class="hermesTimeListDate hermesBorderLeft"><a class="widget" id="sortDate"><?php echo _("Date")?></a></div>
        <div class="hermesTimeListHours hermesBorderLeft"><a class="widget" id="sortHours"><?php echo _("Hours")?></a></div>
        <div class="hermesTimeListClient hermesBorderLeft"><a class="widget" id="sortClient"><?php echo _("Client")?></a></div>
        <div class="hermesTimeListCostObject hermesBorderLeft"><a class="widget" id="sortCostObject"><?php echo _("Cost Object")?></a></div>
        <div class="hermesTimeListType hermesBorderLeft"><a class="widget" id="sortType"><?php echo _("Job Type")?></a></div>
        <div class="hermesTimeListDesc hermesBorderLeft"><a class="widget"><?php echo _("Description")?></a></div>
        <div class="hermesTimeSliceOptions">&nbsp;</div>
       </div>
       <div id="hermesTimeListBody">
        <div id="hermesTimeListTemplate" style="display:none;">
          <div class="hermesUnselectedSlice hermesTimeListSelect">&nbsp;</div>
          <div class="hermesTimeListDate hermesBorderLeft"></div>
          <div class="hermesTimeListHours hermesBorderLeft"></div>
          <div class="hermesTimeListClient hermesBorderLeft"></div>
          <div class="hermesTimeListCostObject hermesBorderLeft"></div>
          <div class="hermesTimeListType hermesBorderLeft"></div>
          <div class="hermesTimeListDesc hermesBorderLeft"></div>
          <div class="hermesTimeSliceOptions"><a href="#" class="sliceEdit rounded"><?php echo _("Edit") ?></a> <a href="#" class="sliceDelete rounded">x</a></div>
        </div>
       </div>
   </div>
   <div class="hermesTimeListControls" style="margin-top:20px;">
     <a class="button"><?php echo _("Submit Time") ?></a>
   </div>
  </div>
</div>