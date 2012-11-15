<!-- Main Search Container -->
<div id="hermesViewSearch" style="display:none">

  <!-- Search Form -->
  <div class="header"><?php echo _("Search") ?></div>
  <form id="hermesSearchForm" action="">
    <div id="hermesSearchFormInputs">
      <div id="hermesLoadingSearch" class="hermesLoading" style="display:none"></div>
      <label for="hermesSearchFormEmployee">
      <?php echo _("Employee"); ?>
      <?php $userType = Hermes::getEmployeesType();
      if ($userType[0] == 'text'):?>
        <input type="text" id="hermesSearchFormEmployee" name="employees[]" />
      <?php else: ?>
        <select name="employees[]" id="hermesSearchFormEmployee" multiple="multiple">
        <?php foreach ($userType[1] as $username => $user_label): ?>
          <option value="<?php echo $username ?>"><?php echo htmlspecialchars($user_label) ?></option>
        </select>
        <?php endforeach ?>
      <?php endif; ?>
      </label><span class="hermesSeparator">|</span>
      <label for="hermesSearchFormClient">
        <?php echo _("Client")?>*
        <?php echo Hermes::getClientSelect('hermesSearchFormClient', true);?>
      </label><span class="hermesSeparator">|</span>
      <label for="hermesSearchFormJobtype">
        <?php echo _("Job Type");?>
        <?php echo Hermes::getJobTypeSelect('hermesSearchFormJobtype', true);?>
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
      <label for="hermesSearchFormSubmitted">
         <?php echo _("Submitted") ?>
         <select name="hermesSearchFormSubmitted">
           <option value=""></option>
           <option value="1"><?php echo _("Yes") ?></option>
           <option value="0"><?php echo _("No") ?></option>
         </select>
      </label>
      <label for="hermesSearchFormExported">
         <?php echo _("Exported") ?>
         <select name="hermesSearchFormExported">
           <option value=""></option>
           <option value="1"><?php echo _("Yes") ?></option>
           <option value="0"><?php echo _("No") ?></option>
         </select>
      </label>
      <label for="hermesSearchFormBillable">
         <?php echo _("Billable") ?>
         <select name="hermesSearchFormBillable">
           <option value=""></option>
           <option value="1"><?php echo _("Yes") ?></option>
           <option value="0"><?php echo _("No") ?></option>
         </select>
      </label>
    </div>
    <div class="horde-form-buttons">
      <input id="hermesSearch" class="horde-default" type="submit" value="<?php echo _("Search")?>" />
      <input id="hermesTimeReset" type="reset" value ="<?php echo _("Reset") ?>" />
    </div>
  </form>
  <!-- End Search Form -->

  <!-- Search results grid -->
  <div class="header"><?php echo _("Search Results") ?>
    <span class="QuickFinderWrapper"><input type="text" name="quicksearchS" id="quicksearchS" for="hermesSearchListInternal" empty="hermesSearchListEmpty" /></span>
  </div>

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
    </div>
    <div id="hermesSearchListInternal"></div>
    <div id="hermesSearchListEmpty" style="display:none;"><?php echo _("No Time Slices")?></div>
  </div>
  <!-- @TODO
  <div id="hermesTimeListControls" class="horde-form-buttons">
    <a id="hermesSearchListSubmit" class="horde-default"><?php echo _("Export Time") ?></a>
  </div> -->
</div>