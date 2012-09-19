<div>
 <div>
  <?php echo $this->hordeLabel('trash', _("Trash mailbox:")) ?>
 </div>
 <div>
  <select id="trash" name="trash">
   <option value="<?php echo $this->nombox ?>"><?php echo _("None") ?></option>
<?php if ($this->vtrash): ?>
   <?php echo $this->optionTag($this->vtrash, _("Use Virtual Trash"), $this->vtrash_select) ?>
<?php endif ?>
   <?php echo $this->special_use ?>
   <?php echo $this->flist ?>
  </select>
 </div>
</div>
