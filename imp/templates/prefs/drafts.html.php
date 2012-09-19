<div>
 <div>
  <?php echo $this->hordeLabel('drafts', _("Drafts mailbox:")) ?>
 </div>
 <div>
  <select id="drafts" name="drafts">
   <option value="<?php echo $this->nombox ?>"><?php echo _("None") ?></option>
   <?php echo $this->special_use ?>
   <?php echo $this->flist ?>
  </select>
 </div>
</div>
