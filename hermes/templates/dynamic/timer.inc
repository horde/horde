<!-- Begin Timer Dialog -->
<div id="hermesTimerDialog" class="quickAdd" style="display:none;">
 <h2><?php echo _("Timer Creation") ?></h2>
 <p><?php echo _("Enter a title for the new timer.") ?></p>
<form id="hermesTimerForm" action="">
<div>
  <label for="hermesTimerTitle">
    <input type="text" name="title" id="hermesTimerTitle" class="hermesLongField" />
  </label>
</div>
<p>
  <input id="hermesTimerSave" class="horde-default" type="submit" value="<?php echo _("Save")?>" />
  <input id="hermesTimerCancel" class="horde-cancel" type="button" value="<?php echo _("Cancel")?>" />
</p>
</form>
</div>
<!-- End Timer Dialog -->