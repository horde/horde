<div class="prefsSmimeContainer">
 <div class="prefsSmimeHeader">
  <h3>
   <?php echo _("S/MIME Public Keyring") ?>
   <?php echo $this->hordeHelp('imp', 'smime-manage-pubkey') ?>
  </h3>
 </div>

 <div>
  <div>
<?php if (empty($this->pubkey_list)): ?>
   <em><?php echo _("No Keys in Keyring") ?></em>
<?php else: ?>
   <table>
<?php foreach ($this->pubkey_list as $v): ?>
    <tr>
     <td>
      <?php echo $this->h($v['name']) ?>
      (<?php echo $this->h($v['email']) ?>)
     </td>
     <td>
      [<?php echo $v['view'] ?><?php echo _("View") ?></a>]
      [<?php echo $v['info'] ?><?php echo _("Details") ?></a>]
      [<?php echo $v['delete'] ?><?php echo _("Delete") ?></a>]
     </td>
    </tr>
<?php endforeach; ?>
   </table>
<?php endif; ?>
  </div>

<?php if ($this->can_import): ?>
<?php if ($this->no_source): ?>
  <div>
   <em><?php echo _("Key import is not available. You have no address book defined to add your contacts.") ?></em>
  </div>
<?php else: ?>
  <div>
   <p>
    <input type="submit" name="save" id="import_smime_public" class="horde-default" value="<?php echo _("Import Public Key") ?>" />
    <?php echo $this->hordeHelp('imp', 'smime-import-pubkey') ?>
   </p>
  </div>
<?php endif; ?>
<?php else: ?>
  <div>
   <div class="prefsSmimeWarning"><?php echo _("Key import is not available. File upload is not enabled on this server.") ?></div>
  </div>
<?php endif; ?>
 </div>
</div>
