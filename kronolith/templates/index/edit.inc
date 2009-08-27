<div id="kronolithEventDialog" style="display:none">
<form id="kronolithEventForm" action="">
<input id="kronolithEventCalendar" type="hidden" name="cal" />
<input id="kronolithEventId" type="hidden" name="id" />

<div>
  <label for="kronolithEventTitle"><?php echo _("Title") ?>:</label><br />
  <input type="text" name="title" id="kronolithEventTitle" class="kronolithLongField" />
</div>

<div>
  <label for="kronolithEventLocation"><?php echo _("Location") ?>:</label><br />
  <input type="text" name="location" id="kronolithEventLocation" size="35" />
  &middot; <span class="kronolithEventGeo"><a href="#"><?php echo _("Find in Maps") ?></a></span>
</div>

<table cellspacing="2" cellpadding="0" border="0">
<tbody>
  <tr>
    <td class="rightAlign"><label for="kronolithEventAllday"><?php echo _("All-day") ?>:</label></td>
    <td><label><input type="checkbox" name="whole_day" id="kronolithEventAllday" /> <?php echo _("all-day event") ?></label></td>
  </tr>

  <tr>
    <td class="rightAlign"><label for="kronolithEventStartDate"><?php echo _("From") ?>:</label></td>
    <td>
      <input type="text" name="start_date" id="kronolithEventStartDate" size="10" class="kronolithDatePicker" />
      <label><?php echo _("at") ?> <input type="text" name="start_time" id="kronolithEventStartTime" size="8" /></label>
    </td>
  </tr>

  <tr>
    <td class="rightAlign"><label for="kronolithEventEndDate"><?php echo _("To") ?>:</label></td>
    <td>
      <input type="text" name="end_date" id="kronolithEventEndDate" size="10" class="kronolithDatePicker" />
      <label><?php echo _("at") ?> <input type="text" name="end_time" id="kronolithEventEndTime" size="8" /></label>
    </td>
  </tr>

  <tr>
    <td class="rightAlign"><label for="kronolithEventRecurrence"><?php echo _("Repeat") ?>:</label></td>
    <td><select name="recur" id="kronolithEventRecurrence" class="kronolithLongField">
      <option value="0"><?php echo _("no repeat") ?></option>
      <option value="1"><?php echo _("Every day") ?></option>
      <option value="2"><?php echo _("Every week") ?></option>
      <option value=""><?php echo _("Every 14 days") ?></option>
      <option value="3"><?php echo _("Every month") ?></option>
      <option value="5"><?php echo _("Every year") ?></option>
      <option value="-1"><?php echo _("Custom...") ?></option>
    </select></td>
  </tr>
</tbody></table>

<div>
  <label for="kronolithEventUrl"><?php echo _("URL") ?>:</label><br />
  <input type="text" name="url" id="kronolithEventUrl" class="kronolithLongField" />
</div>

<div>
  <label for="kronolithEventTags"><?php echo _("Tags and Keywords") ?>:</label><br />
  <input id="kronolithEventTags" name="tags" />
  <span id="kronolithEventTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
</div>

<div id="kronolithEventTopTags"></div>

<div id="kronolithEventActions">
  <input id="kronolithEventSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input id="kronolithEventDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <input id="kronolithEventCancel" type="button" value="<?php echo _("Cancel") ?>" class="button cancel" />
  <span id="kronolithEventAdvancedLink">| <a href="#"><?php echo _("Advanced") ?></a></span><br />
</div>

</form></div>
