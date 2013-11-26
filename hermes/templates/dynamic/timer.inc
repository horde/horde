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
<div class="hermesTimerFormDetail">
  <?php echo _("Enter details about this timer.")?><br />
  <label for="hermesTimerClient"><?php echo _("Client")?>
    <?php echo Hermes::getClientSelect('hermesTimerClient') ?>
  </label><br />
  <label for="hermesTimerCostObject">
    <?php echo _("Cost Object")?>
    <select type="text" id="hermesTimerCostObject" name="costobject"></select>
  </label><br />
  <label for="hermesTimerJobType">
    <?php echo _("Job Type")?>
    <?php echo Hermes::getJobTypeSelect('hermesTimerJobtype');?>
  </label>
</div>
<div class="hermesTimerExclusiveDetail">
  <label for="hermesTimerExclusive">
    <?php echo _("Make this timer exclusive?")?>
    <input type="checkbox" name="exclusive" id="hermesTimerExclusive" checked="checked" />
  </label>
  <p><?php echo _("Exclusive timers cannot run while other exclusive timers are running, but may run while other non-exclusive timers are running.")?></p>
</div>
<p>
  <input id="hermesTimerSave" class="horde-default" type="submit" value="<?php echo _("Save")?>" />
  <input id="hermesTimerCancel" class="horde-cancel" type="button" value="<?php echo _("Cancel")?>" />
</p>
</form>
</div>
<!-- End Timer Dialog -->
<!-- Begin Timer Done Dialog -->
<div id="hermesLogTimer" class="quickAdd" style="display:none;">
  <h2><?php echo _("Log Timer") ?></h2>
  <p><?php echo _("You are logging the following timer:")?></p>
  <div id="hermesLogTimerDetails"></div>
  <p>
    <input id="hermesLogTimerLogRestart" class="horde-button" type="submit" value="<?php echo _("Stop and reset")?>" />
    <input id="hermesLogTimerLogClose" class="horde-default" type="submit" value="<?php echo _("Stop and remove")?>" />
    <input id="hermesLogTimerCancel" class="horde-cancel" type="button" value="<?php echo _("Cancel")?>" />
  </p>
</div>