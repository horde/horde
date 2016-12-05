<form enctype="multipart/form-data" method="post" name="import_smime_key" action="<?php echo $this->selfurl ?>">
<input type="hidden" name="reload" value="<?php echo $this->h($this->reload) ?>" />
<input type="hidden" name="actionID" value="process_import_personal_certs" />

<h1 class="header">
  <?php echo _("Import Personal S/MIME Certificate") ?>
</h1>

<div class="horde-content">
  <label class="importKeyUpload">
    <?php echo _("Upload") ?>:
    <input id="upload_key" name="upload_key" type="file" size="40" />
  </label>
</div>

<div class="horde-content">
  <label class="importKeyText">
    <?php echo _("Password of Uploaded Certificate") ?>:<br />
    <input id="upload_key_pass" name="upload_key_pass" type="password" size="30" />
  </label>
</div>
<div class="horde-content">
  <label class="importKeyText">
    <?php echo _("New Password for Private Key") ?>:<br />
    <input id="upload_key_pk_pass" name="upload_key_pk_pass" type="password" size="30" />
  </label>
</div>

<p class="horde-form-buttons">
  <input type="submit" name="import" class="horde-default" value="<?php echo _("Import") ?>" />
  <input type="submit" name="cancel" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
</p>
</form>
