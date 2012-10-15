<div>
 <div>
  <?php echo $this->hordeLabel('spam', _("Spam mailbox:")) ?>
 </div>
 <div>
  <select id="spam" name="spam">
   <option value="<?php echo $this->nombox ?>"><?php echo _("None") ?></option>
   <?php echo $this->special_use ?>
   <?php echo $this->flist ?>
  </select>
 </div>
</div>
