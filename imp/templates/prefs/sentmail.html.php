<div>
 <div>
  <?php echo $this->hordeLabel('sent_mail', _("Sent mail mailbox:")) ?>
 </div>
 <div>
  <select name="sent_mail" id="sent_mail">
   <option value=""><?php echo _("None") ?></option>
   <option value="<?php echo $this->default ?>" selected="selected"><?php echo _("Use Default Value") ?></option>
   <?php echo $this->special_use ?>
   <?php echo $this->flist ?>
  </select>
 </div>
</div>
