<form method="post" name="import_form" enctype="multipart/form-data" action="<?php echo Horde::url('data.php') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="<?php echo htmlspecialchars($next_step) ?>" />
<input type="hidden" name="import_step" value="<?php echo (int)$import_step ?>" />

<h1 class="header">
  <?php echo _("Import Bookmarks") ?>
</h1>

<div class="horde-content">
  <?php echo Horde::label('import_format', _("Select the format of the source file:")) ?><br />
  <select name="import_format" id="import_format">
  <?php foreach ($file_types as $key => $value):?>
    <option value="<?php echo $key ?>"><?php echo $value ?></option>
  <?php endforeach ?>
  </select><br />

  <?php echo Horde::label('import_file', _("Select the file to import:")) ?><br />
  <input type="file" name="import_file" id="import_file" size="40" /><br /><br />
</div>

<div class="horde-form-buttons">
  <input type="submit" value="<?php echo _("Import") ?>" class="horde-default" />
</div>
</form>
