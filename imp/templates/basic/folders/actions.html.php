<div class="horde-buttonbar">
 <ul class="rightFloat">
  <li class="horde-nobutton">
   <?php echo $this->hordeHelp('imp', 'folder-options') ?>
  </li>
 </ul>
 <ul>
  <li>
   <?php echo $this->refresh ?>
  </li>
  <li>
   <span>
    <?php echo $this->checkBoxTag('checkAll', 1, false, array_merge(array('class' => 'checkbox', 'id' => 'checkAll' . $this->id), $this->hordeAccessKeyAndTitle(_("Check _All/None"), false, true))) ?>
    <label for="checkAll<?php echo $this->id ?>"><?php echo _("Check All/None") ?></label>
   </span>
  </li>
  <li>
   <label for="action_choose<?php echo $this->id ?>" class="hidden"><?php echo _("Choose Action") ?></label>
   <select id="action_choose<?php echo $this->id ?>">
    <option selected="selected"><?php echo _("Choose Action") ?></option>
    <option value="" disabled="disabled">--------------------</option>
<?php if ($this->create_mbox): ?>
    <option value="create_mbox"><?php echo _("Create") ?></option>
<?php endif; ?>
    <option value="rename_mbox"><?php echo _("Rename") ?></option>
    <option value="delete_mbox_confirm"><?php echo _("Delete") ?></option>
    <option value="empty_mbox_confirm"><?php echo _("Empty") ?></option>
<?php if ($this->notrash): ?>
    <option value="expunge_mbox"><?php echo _("Expunge") ?></option>
<?php endif; ?>
<?php if ($this->subscribe): ?>
    <option value="subscribe_mbox"><?php echo _("Subscribe") ?></option>
    <option value="unsubscribe_mbox"><?php echo _("Unsubscribe") ?></option>
<?php endif; ?>
<?php if ($this->nav_poll): ?>
    <option value="poll_mbox"><?php echo _("Check for New Mail") ?></option>
    <option value="nopoll_mbox"><?php echo _("Do Not Check for New Mail") ?></option>
<?php endif; ?>
    <option value="mark_mbox_seen"><?php echo _("Mark All Messages as Seen") ?></option>
    <option value="mark_mbox_unseen"><?php echo _("Mark All Messages as Unseen") ?></option>
    <option value="download_mbox"><?php echo _("Download") ?></option>
    <option value="download_mbox_zip"><?php echo _("Download (.zip format)") ?></option>
<?php if ($this->file_upload): ?>
    <option value="import_mbox"><?php echo _("Import Messages") ?></option>
<?php endif; ?>
    <option value="mbox_size"><?php echo _("Show Size") ?></option>
    <option value="search"><?php echo _("Search") ?></option>
    <option value="" disabled="disabled">--------------------</option>
    <option value="rebuild_tree"><?php echo _("Rebuild Folder Tree") ?></option>
   </select>
  </li>
  <li>
<?php if ($this->subscribe): ?>
   <?php echo $this->toggle_subscribe ?>
  </li>
<?php endif; ?>
  <li>
   <?php echo $this->expand_all ?>
  </li>
  <li>
   <?php echo $this->collapse_all ?>
  </li>
 </ul>
</div>
