<div id="hermesViewSearch" style="display:none">
  <div id="hermesLoadingSearch" class="hermesLoading"></div>
  <div class="hermesViewTimeContainer">
   <div id="hermesSearchEntry">
    <h1><?php echo _("Search")?></h1>
    <form id="hermesSearchForm" action="">
      <div id="hermesSearchFormInputs">
        <label for="hermesSearchFormEmployee">
        <?php echo _("Employee"); ?>
         <?php
         $userType = Hermes::getEmployeesType();
         if ($userType[0] == 'text'):?>
          <input type="text" id="hermesSearchFormEmployee" name="employees[]" />
         <?php else: ?>
          <select>
              <option value="userone">User One</option>
          </select>
         <?php endif; ?>
        </label><span class="hermesSeparator">|</span>
        <label for="hermesSearchFormClient">
         <?php echo _("Client")?>*
         <?php echo Hermes::getClientSelect('hermesSearchFormClient');?>
        </label><span class="hermesSeparator">|</span>
        <label for="hermesSearchFormJobtype">
         <?php echo _("Job Type");?>
         <?php echo Hermes::getJobTypeSelect('hermesSearchFormJobtype');?>
        </label><span class="hermesSeparator">|</span>
        <label for="hermesTimeFormCostobject">
         <?php echo _("Cost Object")?>
         <select type="text" id="hermesTimeFormCostobject" name="costobject"></select>
        </label><span class="hermesSeparator">|</span>
        <label for="HermesSearchFormAfterDate">
         <?php echo _("Do not include entries before") ?>
         <input class="input" type="text" name="after_date" id="hermesSearchFormAfterDate" size="10" />
         <?php echo Horde::img('picker.png', _("Set date"), 'id="hermesSearchFormAfterPicker" class="hermesDatePicker"') ?>
        </label><span class="hermesSeparator">|</span>
        <label for="HermesSearchFormBeforeDate">
         <?php echo _("Do not include entries after") ?>
         <input class="input" type="text" name="before_date" id="hermesSearchFormBeforeDate" size="10" />
         <?php echo Horde::img('picker.png', _("Set date"), 'id="hermesSearchFormBeforePicker" class="hermesDatePicker"') ?>
        </label><span class="hermesSeparator">|</span>
      </div>
    </form>
   </div>
   <h2><?php echo _("Search Results");?></h2><span class="QuickFinderWrapper"><?php echo Horde::img('search.png', _("Search")) ?><input type="text" name="quicksearchS" id="quicksearchS" for="hermesSearchListInternal" empty="hermesSearchListEmpty" /></span>
   <div id="hermesSearchList">
    <div id="hermesSearchListHeader">
      <div class="hermesUnselectedSlice hermesTimeListSelect">&nbsp;</div>
      <div class="hermesTimeListDate hermesBorderLeft"><a class="widget" id="sortDate"><?php echo _("Date")?></a></div>
      <div class="hermesTimeListEmployee hermesBorderLeft"><a class="widget" id="sortDate"><?php echo _("Employee")?></a></div>
      <div class="hermesTimeListClient hermesBorderLeft"><a class="widget" id="sortClient"><?php echo _("Client")?></a></div>
      <div class="hermesTimeListCostObject hermesBorderLeft"><a class="widget" id="sortCostObject"><?php echo _("Cost Object")?></a></div>
      <div class="hermesTimeListType hermesBorderLeft"><a class="widget" id="sortType"><?php echo _("Job Type")?></a></div>
      <div class="hermesTimeListDesc hermesBorderLeft"><a class="widget" id="sortDesc"><?php echo _("Description")?></a></div>
      <div class="hermesTimeListBill hermesBorderLeft"><a class="widget" id="sortBill"><?php echo _("Bill") ?></a></div>
      <div class="hermesTimeListHours hermesBorderLeft"><a class="widget" id="sortHours"><?php echo _("Hours")?></a></div>
      <div class="hermesTimeSliceOptions">&nbsp;</div>
    </div>
    <div id="hermesSearchListBody">
      <div id="hermesSearchListTemplate" style="display:none;">
        <div class="hermesUnselectedSlice hermesTimeListSelect">&nbsp;</div>
        <div class="hermesTimeListDate hermesBorderLeft"></div>
        <div class="hermesTimeListEmployee hermesBorderLeft"></div>
        <div class="hermesTimeListClient hermesBorderLeft"></div>
        <div class="hermesTimeListCostObject hermesBorderLeft"></div>
        <div class="hermesTimeListType hermesBorderLeft"></div>
        <div class="hermesTimeListDesc hermesBorderLeft"></div>
        <div class="hermesTimeListBill hermesBorderLeft"></div>
        <div class="hermesTimeListHours hermesBorderLeft"></div>
        <div class="hermesTimeSliceOptions"><a href="#" class="sliceEdit rounded bold"><?php echo _("Edit") ?></a> <a href="#" class="sliceDelete rounded bold">x</a></div>
      </div>
      <div id="hermesSearchListInternal"></div>
      <div id="hermesSearchListEmpty" style="display:none;"><?php echo _("No Time Slices")?></div>
    </div>
    <div class="hermesTimeListControls">
      <a id="hermesSearchListSubmit" class="button"><?php echo _("Export Time") ?></a>
    </div>
   </div>
   </div>
</div>