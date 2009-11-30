<?php echo Horde::label('trash', _("Trash folder:")) ?><br />
<input type="hidden" name="trash_new" id="trash_new" value="" />
<select id="trash" name="trash">
<?php
    $use_vtrash = $prefs->getValue('use_vtrash');
    echo '<option value="' . IMP::PREF_NO_FOLDER . '">' . _("None") . '</option>' .
    '<option value="' . IMP::PREF_VTRASH . '"' . (($use_vtrash) ? ' selected="selected"' : '') . '>' . _("Use Virtual Trash") . '</option>' .
    IMP::flistSelect(array('heading' => _("Create a new trash folder"), 'filter' => array('INBOX'), 'selected' => ($use_vtrash ? null : IMP::folderPref($prefs->getValue('trash_folder'), true)), 'new_folder' => true));
?>
</select>
<br />
