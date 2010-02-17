<div>
 <div>
  <?php echo Horde::label('spam', _("Spam folder:")) ?>
 </div>
 <div>
  <input type="hidden" name="spam_new" id="spam_new" value="" />
  <select id="spam" name="spam">
   <option value="<?php echo IMP::PREF_NO_FOLDER ?>"><?php echo _("None") ?></option>
   <?php echo IMP::flistSelect(array('heading' => _("Create a new Spam folder"), 'filter' => array('INBOX'), 'selected' => IMP::folderPref($prefs->getValue('spam_folder'), true), 'new_folder' => true)) ?>
  </select>
 </div>
</div>
