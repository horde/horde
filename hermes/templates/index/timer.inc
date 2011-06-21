<div id="hermesTimerDialog" class="hermesDialog" style="display:none;">
<form id="hermesTimerForm" action="">
<div>
  <label for="hermesTimerTitle">
    <?php echo _("Timer title") ?>:<br />
    <input type="text" name="title" id="hermesTimerTitle" class="hermesLongField" />
  </label>
</div>
<div class="hermesFormActions">
  <input id="hermesTimerSave" type="button" value="<?php echo _("Save") ?>" class="button ok" />
  <input id="hermesTimerDelete" type="button" value="<?php echo _("Delete") ?>" class="button ko" />
  <span class="hermesSeparator"><?php echo _("or") ?></span> <a class="hermesFormCancel"><?php echo _("Cancel") ?></a>
</div>
</form>
</div>