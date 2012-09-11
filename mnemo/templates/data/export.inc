<h1 class="header"><?php echo _("Export Notes") ?></h1>

<form method="post" name="export" action="<?php echo $GLOBALS['registry']->downloadUrl(_("notes.csv")) ?>">
<input type="hidden" name="actionID" value="export" />

<div class="horde-content">
  <?php echo Horde::label('exportID', _("Select the export format:")) ?><br />
  <select id="exportID" name="exportID">
   <option value="<?php echo Horde_Data::EXPORT_CSV ?>">CSV</option>
  </select>
</div>

<div class="horde-form-buttons">
  <input type="submit" value="<?php echo _("Export") ?>" class="horde-default" />
</div>

</form>
