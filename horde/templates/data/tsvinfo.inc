<form method="post" name="import_form" action="data.php">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="<?php echo (int)$next_step ?>" />
<input type="hidden" name="import_format" value="<?php echo htmlspecialchars(Horde_Util::getFormData('import_format')) ?>" />
<input type="hidden" name="import_step" value="<?php echo (int)$import_step ?>" />
<h1 class="header">
 <?php printf(_("Import, Step %d"), (int)$import_step) ?>
</h1>
<div class="horde-content">
 <?php echo _("Here is the beginning of the file:") ?><br />
 <pre><?php echo htmlspecialchars($storage->get('first_lines')) ?></pre><br />
 <?php echo Horde::label('header', _("Does the first row contain the field names? If yes, check this box:")) ?>
 <input id="header" type="checkbox" name="header" value="1" /><br />
</div>

<div class="horde-form-buttons">
  <input type="submit" value="<?php echo _("Next") ?>" class="button" />
</div>
</form>
