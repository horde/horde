<div class="prefsPgpContainer">
 <div class="prefsPgpHeader">
  <h3>
   <?php echo _("Your PGP Public/Private Keys") ?>
   <?php echo $this->hordeHelp('imp', 'pgp-overview-personalkey') ?>
  </h3>
 </div>

<?php if ($this->notsecure): ?>
 <div>
  <em class="prefsPgpWarning"><?php echo _("PGP Personal Keypair support requires a secure web connection.") ?></em>
 </div>
<?php elseif ($this->has_key): ?>
 <div>
  <table>
   <tr>
    <td>
     <?php echo _("Your Public Key") ?>:
    </td>
    <td>
     [<?php echo $this->viewpublic ?><?php echo _("View") ?></a>]
     [<?php echo $this->infopublic ?><?php echo _("Details") ?></a>]
     [<?php echo $this->sendkey ?><?php echo _("Send Key to Public Keyserver") ?></a>]
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-public') ?>
    </td>
   </tr>
   <tr>
    <td>
     <?php echo _("Your Private Key") ?>:
    </td>
    <td>
     [<?php echo $this->passphrase ?></a>]
     [<?php echo $this->viewprivate ?><?php echo _("View") ?></a>]
     [<?php echo $this->infoprivate ?><?php echo _("Details") ?></a>]
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-private') ?>
    </td>
   </tr>
  </table>
 </div>

 <p>
  <input type="submit" id="delete_pgp_privkey" name="delete_pgp_privkey" class="horde-delete" value="<?php echo _("Delete Current Keys") ?>" />
  <?php echo $this->hordeHelp('imp', 'pgp-personalkey-delete') ?>
 </p>
<?php else: ?>
 <div>
  <table>
   <tr>
    <td>
     <label for="generate_realname"><?php echo _("Your Name") ?>:</label>
    </td>
    <td>
     <input type="text" id="generate_realname" name="generate_realname" size="30" maxlength="60" value="<?php echo $this->fullname ?>"/>
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-create-name') ?>
    </td>
   </tr>
   <tr>
    <td>
     <label for="generate_comment"><?php echo _("Comment") ?>:</label>
    </td>
    <td>
     <input type="text" id="generate_comment" name="generate_comment" size="30" maxlength="60" />
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-create-comment') ?>
    </td>
   </tr>
   <tr>
    <td>
     <label for="generate_email"><?php echo _("E-mail Address") ?>:</label>
    </td>
    <td>
     <input type="text" id="generate_email" name="generate_email" size="30" maxlength="60" value="<?php echo $this->fromaddr ?>"/>
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-create-email') ?>
    </td>
   </tr>
   <tr>
    <td>
     <label for="generate_keylength"><?php echo _("Key Length") ?>:</label>
    </td>
    <td>
     <select id="generate_keylength" name="generate_keylength">
      <option value="1024">1024</option>
      <option value="2048">2048</option>
     </select>
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-create-keylength') ?>
    </td>
   </tr>
   <tr>
    <td>
     <label for="generate_passphrase1"><?php echo _("Passphrase") ?>:</label>
    </td>
    <td>
     <input type="password" id="generate_passphrase1" name="generate_passphrase1" size="30" maxlength="60" />
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-create-passphrase') ?>
    </td>
   </tr>
   <tr>
    <td>
     <label for="generate_passphrase2"><?php echo _("Passphrase (Again)") ?>:</label>
    </td>
    <td>
     <input type="password" id="generate_passphrase2" name="generate_passphrase2" size="30" maxlength="60" />
    </td>
    <td></td>
   </tr>
   <tr>
    <td>
     <label for="generate_expire"><?php echo _("Expiration") ?>:</label>
    </td>
    <td>
     <input type="checkbox" id="generate_expire" name="generate_expire" checked="checked" />
     <?php echo _("No Expiration") ?>
     <div style="display:none">
      <input type="hidden" id="generate_expire_date" name="generate_expire_date" display="hidden" />
      <span></span>
      <a href="#" class="calendarPopup"><span class="iconImg calendarImg"></span></a>
     </div>
    </td>
    <td>
     <?php echo $this->hordeHelp('imp', 'pgp-personalkey-create-expire') ?>
    </td>
   </tr>
  </table>

  <p>
   <input type="submit" id="create_pgp_key" name="create_pgp_key" class="horde-create" value="<?php echo _("Create Keys") ?>" />
<?php if ($this->import_pgp_private): ?>
   <input type="submit" name="save" class="horde-default" id="import_pgp_personal" value="<?php echo _("Import Keypair") ?>" />
<?php endif; ?>
   <?php echo $this->hordeHelp('imp', 'pgp-personalkey-create-actions') ?>
  </p>
 </div>
<?php endif; ?>
</div>
