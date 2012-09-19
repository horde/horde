<div>
 <div>
  <?php echo $this->hordeLabel('initial_page', _("View or mailbox to display after login:")) ?>
 </div>
 <div>
  <select id="initial_page" name="initial_page">
   <?php echo $this->optionTag($this->folder_page, _("Folder Navigator"), $this->folder_sel) ?>
   <option value="" disabled="disabled">- - - - - - - -</option>
   <?php echo $this->flist ?>
  </select>
 </div>
</div>
