<div>
 <div>
  <?php echo $this->hordeLabel('templates', _("Compose Templates mailbox:")) ?>
 </div>
 <div>
  <select id="templates" name="templates">
   <option value="<?php echo $this->mbox_nomailbox ?>"><?php echo _("None") ?></option>
   <?php echo $this->mbox_flist ?>
  </select>
 </div>
</div>
