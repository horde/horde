<?php echo Horde::label('drafts', _("Drafts folder:")) ?><br />
<input type="hidden" name="drafts_new" id="drafts_new" value="" />
<select id="drafts" name="drafts">
 <option value="<?php echo IMP::PREF_NO_FOLDER ?>"><?php echo _("None") ?></option>
 <?php echo IMP::flistSelect(array('heading' => _("Create a new drafts folder"), 'filter' => array('INBOX'), 'selected' => IMP::folderPref($prefs->getValue('drafts_folder'), true), 'new_folder' => true)) ?>
</select>
<br />
