<div class="prefsSmimeContainer">
 <div class="prefsSmimeHeader">
  <h3>
   <?php echo _("Your S/MIME Public/Private Certificate") ?>
   <?php echo $this->hordeHelp('imp', 'smime-overview-personalkey') ?>
  </h3>
 </div>

<?php if ($this->notsecure): ?>
 <div>
  <em class="prefsSmimeWarning"><?php echo _("S/MIME Personal Certificate support requires a secure web connection.") ?></em>
 </div>
<?php elseif ($this->has_key): ?>
 <div>
  <table>
   <tr>
    <td>
     <?php echo _("Your Public Certificate") ?>:
    </td>
    <td>
     [<?php echo $this->viewpublic ?><?php echo _("View") ?></a>]
     [<?php echo $this->infopublic ?><?php echo _("Details") ?></a>]
    </td>
   </tr>
   <tr>
    <td>
     <?php echo _("Your Private Certificate") ?>:
    </td>
    <td>
     [<?php echo $this->passphrase ?></a>]
     [<?php echo $this->viewprivate ?><?php echo _("View") ?></a>]
    </td>
   </tr>
  </table>
 </div>

 <p>
  <input type="submit" id="delete_smime_personal" name="delete_smime_personal" class="horde-delete" value="<?php echo _("Delete Personal Certificate") ?>" />
  <?php echo $this->hordeHelp('imp', 'smime-delete-personal-certs') ?>
 </p>
<?php elseif ($this->import): ?>
 <div>
  <p>
   <input type="submit" name="save" class="horde-default" id="import_smime_personal" value="<?php echo _("Import Personal Certificate") ?>" />
   <?php echo $this->hordeHelp('imp', 'smime-import-personal-certs') ?>
  </p>
 </div>
<?php endif; ?>
</div>
