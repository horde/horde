<form enctype="multipart/form-data" method="post" name="import_pgp_key" action="<?php echo $this->selfurl ?>">
 <input type="hidden" name="reload" value="<?php echo $this->h($this->reload) ?>" />
 <input type="hidden" name="actionID" value="<?php echo $this->target ?>" />
 <?php echo $this->formInput ?>

 <table class="importKeyTable">
  <tr>
   <td class="header leftAlign nowrap">
<?php if ($this->target == 'process_import_public_key'): ?>
    <?php echo _("Import Public PGP Key") ?>
<?php elseif ($this->target == 'process_import_personal_key'): ?>
    <?php echo _("Import Personal Keys") ?>
<?php endif; ?>
   </td>
  </tr>

  <tr>
   <td class="importKeyHowto">
<?php if ($this->target == 'process_import_public_key'): ?>
    <?php echo _("Paste PGP public keys into the textarea, upload a file containing PGP public keys, or combine both methods. Multiple keys are supported.") ?>
<?php elseif ($this->target == 'process_import_personal_key'): ?>
    <?php echo _("Paste your PGP public and private key into the textarea, upload a file containing your keys, or combine both methods. The first public key and private key recognized in the input will be used as your personal keys.") ?>
<?php endif; ?>
   </td>
  </tr>

  <tr>
   <td class="item leftAlign">
    <table>
     <tr>
      <td class="item leftAlign importKeyText"><label for="import_key">
<?php if ($this->target == 'process_import_public_key'): ?>
       <?php echo _("Import Public Key") ?>:
<?php elseif ($this->target == 'process_import_personal_key'): ?>
       <?php echo _("Import Personal Keys") ?>:
<?php endif; ?>
      </label></td>
     </tr>
     <tr>
      <td class="item leftAlign">
       <textarea id="import_key" name="import_key" rows="6" cols="80" class="fixed"></textarea>
      </td>
     </tr>
    </table>
   </td>
  </tr>

  <tr>
   <td class="item leftAlign importKeyOr">
    --<?php echo _("OR") ?>--
   </td>
  </tr>

  <tr>
   <td class="item leftAlign">
    <table>
     <tr>
      <td class="item leftAlign">
       <label for="upload_key" class="importKeyUpload"><?php echo _("Upload") ?>:</label>
       <input id="upload_key" name="upload_key" type="file" size="40" />
      </td>
     </tr>
    </table>
   </td>
  </tr>

  <tr>
   <td class="header nowrap">
    <input type="submit" name="import" class="horde-default" value="<?php echo _("Import") ?>" />
    <input type="submit" name="cancel" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
   </td>
  </tr>
 </table>

</form>
