<form enctype="multipart/form-data" method="post" name="import_smime_key" action="<?php echo $this->selfurl ?>">
 <input type="hidden" name="reload" value="<?php echo $this->h($this->reload) ?>" />
 <input type="hidden" name="actionID" value="<?php echo $this->target ?>" />
 <?php echo $this->forminput ?>

 <table class="importKeyTable">
  <tr>
   <td class="header leftAlign nowrap"<?php if ($this->target == 'process_import_personal_certs'): ?> colspan="2"<?php endif; ?>>
<?php if ($this->target == 'process_import_public_key'): ?>
    <?php echo _("Import Public S/MIME Key") ?>
<?php else: ?>
    <?php echo _("Import Personal S/MIME Certificates") ?>
<?php endif; ?>
   </td>
  </tr>

<?php if ($this->target == 'process_import_public_key'): ?>
  <tr>
   <td class="item leftAlign">
    <table>
     <tr>
      <td class="item leftAlign importKeyText"><label for="import_key">
       <?php echo _("Insert Certificate Here") ?>:
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
<?php endif; ?>

  <tr>
   <td class="item leftAlign"<?php if ($this->target == 'process_import_personal_certs'): ?> colspan="2"<?php endif; ?>>
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

<?php if ($this->target == 'process_import_personal_certs'): ?>
  <tr>
   <td class="item leftAlign" colspan="2">
    <table>
     <tr>
      <td class="item leftAlign">
       <label for="upload_key_pass" class="importKeyUpload"><?php echo _("Password") ?>:</label>
       <input id="upload_key_pass" name="upload_key_pass" type="password" size="30" />
      </td>
     </tr>
     <tr>
      <td class="item leftAlign">
       <label for="upload_key_pk_pass" class="importKeyUpload"><?php echo _("Private Key Password") ?>:</label>
       <input id="upload_key_pk_pass" name="upload_key_pk_pass" type="password" size="30" />
      </td>
     </tr>
    </table>
   </td>
  </tr>
<?php endif; ?>

  <tr>
   <td align="center" class="header nowrap">
    <input type="submit" name="import" class="horde-default" value="<?php echo _("Import") ?>" />
    <input type="submit" name="cancel" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
   </td>
  </tr>
 </table>

</form>
