<form enctype="multipart/form-data" method="post" name="import_pgp_key" action="<?php echo $this->selfurl ?>">
<input type="hidden" name="reload" value="<?php echo $this->h($this->reload) ?>" />
<input type="hidden" name="actionID" value="<?php echo $this->target ?>" />

<h1 class="header">
<?php if ($this->target == 'process_import_public_key'): ?>
  <?php echo _("Import Public PGP Key") ?>
<?php elseif ($this->target == 'process_import_personal_key'): ?>
  <?php echo _("Import Personal Key") ?>
<?php endif; ?>
</h1>

<p class="horde-content">
<?php if ($this->target == 'process_import_public_key'): ?>
  <?php echo _("Paste PGP public keys into the textarea, upload a file containing PGP public keys, or combine both methods. Multiple keys are supported.") ?>
<?php elseif ($this->target == 'process_import_personal_key'): ?>
  <?php echo _("Paste your PGP private key into the textarea or upload a file containing your key.") ?>
<?php endif; ?>
</p>

<p class="horde-content">
  <label for="import_key" class="importKeyText">
<?php if ($this->target == 'process_import_public_key'): ?>
    <?php echo _("Import Public Key") ?>:
<?php elseif ($this->target == 'process_import_personal_key'): ?>
    <?php echo _("Import Personal Key") ?>:
<?php endif; ?>
  </label>
  <textarea id="import_key" name="import_key" rows="6" cols="80" class="fixed"></textarea>
</p>

<span class="horde-content importKeyOr">
  --<?php echo _("OR") ?>--
</span>

<p class="horde-content">
  <label for="upload_key" class="importKeyUpload"><?php echo _("Upload") ?>:</label>
  <input id="upload_key" name="upload_key" type="file" size="40" />
</p>

<p class="horde-form-buttons">
  <input type="submit" name="import" class="horde-default" value="<?php echo _("Import") ?>" />
  <input type="submit" name="cancel" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
</p>

</form>
