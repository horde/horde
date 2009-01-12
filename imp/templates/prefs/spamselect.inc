<?php if (!$prefs->isLocked('spam_folder')): ?>
<script type="text/javascript">

function newSpamFolderName()
{
    var s = document.prefs.spam, sn = document.prefs.spam_new;
    s.onchange = null;
    if (s[s.selectedIndex].value == '') {
        var folder = window.prompt('<?php echo addslashes(_("Enter the name for your new Spam folder")) ?>', sn.value ? sn.value : '');
        if (folder != '') {
            sn.value = folder;
            s[1].text = '<?php echo addslashes(_("Create a new Spam folder")) ?> [' + sn.value + ']';
        }
    }
    s.onchange = newSpamFolderName;
}

</script>

<?php echo Horde::label('spam', _("Spam folder:")) ?><br />
<select id="spam" name="spam" onchange="newSpamFolderName();"><option value="<?php echo IMP_PREF_NO_FOLDER ?>"><?php echo _("None") ?></option><?php echo IMP::flistSelect(array('heading' => _("Create a new Spam folder"), 'filter' => array('INBOX'), 'selected' => IMP::folderPref($prefs->getValue('spam_folder')), 'new_folder' => true)) ?></select>
<input type="hidden" name="spam_new" value="" />
<?php endif; ?>
