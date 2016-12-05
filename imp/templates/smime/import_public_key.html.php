<form enctype="multipart/form-data" method="post" name="import_smime_key" action="<?php echo $this->selfurl ?>">
<input type="hidden" name="reload" value="<?php echo $this->h($this->reload) ?>" />
<input type="hidden" name="actionID" value="process_import_public_key" />

<h1 class="header">
  <?php echo _("Import Public S/MIME Key") ?>
</h1>

<p class="horde-content">
  <label class="importKeyText">
    <?php echo _("Insert Certificate Here") ?>:<br />
    <textarea id="import_key" name="import_key" rows="6" cols="80" class="fixed"></textarea>
  </label>
</p>

<span class="horde-content importKeyOr">
  --<?php echo _("OR") ?>--
</span>

<div class="horde-content">
  <label class="importKeyUpload">
    <?php echo _("Upload") ?>:
    <input id="upload_key" name="upload_key" type="file" size="40" />
  </label>
</div>

<p class="horde-form-buttons">
  <input type="submit" name="import" class="horde-default" value="<?php echo _("Import") ?>" />
  <input type="submit" name="cancel" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
</p>
</form>
