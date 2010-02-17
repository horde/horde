<div>
 <div>
  <?php echo Horde::label('sent_mail_folder', _("Sent mail folder:")) ?>
 </div>
 <div>
  <input type="hidden" name="sent_mail_new" id="sent_mail_new" value="" />
  <select name="sent_mail_folder" id="sent_mail_folder">
   <option value=""><?php echo _("None") ?></option>
   <option value="-1" selected="selected"><?php echo _("Use Default Value") ?></option>
   <?php echo IMP::flistSelect(array('heading' => _("Create a new sent mail folder"), 'filter' => array('INBOX'))) ?>
  </select>
 </div>
</div>
