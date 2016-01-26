<form enctype="multipart/form-data" method="post" name="import_smime_key" action="<?php echo $this->selfurl ?>">
<input type="hidden" name="reload" value="<?php echo $this->h($this->reload) ?>" />
<input type="hidden" name="actionID" value="<?php echo $this->target ?>" />

<h1 class="header">
<?php if ($this->target == 'process_import_public_key'): ?>
  <?php echo _("Import Public S/MIME Key") ?>
<?php else: ?>
  <?php echo _("Import Personal S/MIME Certificate") ?>
<?php endif ?>
</h1>

<?php if ($this->target == 'process_import_public_key'): ?>
<p class="horde-content">
  <label class="importKeyText">
    <?php echo _("Insert Certificate Here") ?>:<br />
    <textarea id="import_key" name="import_key" rows="6" cols="80" class="fixed"></textarea>
  </label>
</p>

<span class="horde-content importKeyOr">
  --<?php echo _("OR") ?>--
</span>
<?php endif ?>

<div class="horde-content">
  <label class="importKeyUpload">
    <?php echo _("Upload") ?>:
    <input id="upload_key" name="upload_key" type="file" size="40" />
  </label>
</div>

<?php if ($this->target == 'process_import_personal_certs'): ?>
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
<?php endif ?>

<p class="horde-form-buttons">
  <input type="submit" name="import" class="horde-default" value="<?php echo _("Import") ?>" />
  <input type="submit" name="cancel" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
</p>
</form>
