<div id="hermesViewSearch" style="display:none">
  <div id="hermesLoadingSearch" class="hermesLoading"></div>
  <div id="hermesViewSearchContainer">
   <form id="hermesSearchForm" action="">
   <?php
    $userType = Hermes::getEmployeesType();
    if ($userType[0] == 'text'):?>
      <input type="text" id="hermesSearchFormEmployee" name="employees[]" />
    <?php else: ?>
      <select>
          <option value="userone">User One</option>
      </select>
    <?php endif; ?>
    <?php echo Hermes::getClientSelect('hermesSearchFormClient');?>
    <?php echo Hermes::getJobTypeSelect('hermesSearchFormJobtype');?>

   </form>
     <div id="hermesSearchList">
       <div id="hermesSearchListHeader">
        <div class="hermesUnselectedSlice hermesSearchListSelect">&nbsp;</div>
        <div class="hermesSearchListDate hermesBorderLeft"><a class="widget" id="sortDate"><?php echo _("Date")?></a></div>
        <div class="hermesSearchListClient hermesBorderLeft"><a class="widget" id="sortClient"><?php echo _("Client")?></a></div>
        <div class="hermesSearchListCostObject hermesBorderLeft"><a class="widget" id="sortCostObject"><?php echo _("Cost Object")?></a></div>
        <div class="hermesSearchListType hermesBorderLeft"><a class="widget" id="sortType"><?php echo _("Job Type")?></a></div>
        <div class="hermesSearchListDesc hermesBorderLeft"><a class="widget" id="sortDesc"><?php echo _("Description")?></a></div>
        <div class="hermesSearchListBill hermesBorderLeft"><a class="widget" id="sortBill"><?php echo _("Bill") ?></a></div>
        <div class="hermesSearchListHours hermesBorderLeft"><a class="widget" id="sortHours"><?php echo _("Hours")?></a></div>
        <div class="hermesTimeSliceOptions">&nbsp;</div>
       </div>
        <div id="hermesSearchListBody">
         <div id="hermesSearchListTemplate" style="display:none;">
           <div class="hermesUnselectedSlice hermesSearchListSelect">&nbsp;</div>
           <div class="hermesSearchListDate hermesBorderLeft"></div>
           <div class="hermesSearchListClient hermesBorderLeft"></div>
           <div class="hermesSearchListCostObject hermesBorderLeft"></div>
           <div class="hermesSearchListType hermesBorderLeft"></div>
           <div class="hermesSearchListDesc hermesBorderLeft"></div>
           <div class="hermesSearchListBill hermesBorderLeft"></div>
           <div class="hermesSearchListHours hermesBorderLeft"></div>
           <div class="hermesTimeSliceOptions"><a href="#" class="sliceEdit rounded bold"><?php echo _("Edit") ?></a> <a href="#" class="sliceDelete rounded bold">x</a></div>
         </div>
         <div id="hermesSearchListInternal"></div>
         <div id="hermesSearchListEmpty" style="display:none;"><?php echo _("No Time Slices")?></div>
        </div>
        <div class="hermesSearchListControls">
            <a id="hermesSearchListSubmit" class="button"><?php echo _("Submit Time") ?></a>
        </div>
     </div>
   </div>
  </div>
</div>