<?php if (!$prefs->isLocked('sent_mail_folder')): ?>
<script type="text/javascript">

function newChoice_sent_mail_folder(val)
{
    var field = document.prefs.sent_mail_folder;
    if (val == "") {
        field.selectedIndex = 0;
        return;
    }
    for (var i = 0, l = field.options.length; i < l; i++) {
        if (field.options[i].value == val) {
            field.selectedIndex = i;
            break;
        }
    }
}

function newFolderName()
{
    var smf = document.prefs.sent_mail_folder, smn = document.prefs.sent_mail_new;
    if (smf.selectedIndex == 2) {
        var folder = window.prompt('<?php echo addslashes(_("Enter the name for your new sent mail folder")) ?>', smn.value ? smn.value : '');
        if (folder != '' && folder != null) {
            smn.value = folder;
            smf[2].text = '<?php echo addslashes(_("Create a new sent mail folder")) ?> [' + smn.value + ']';
        }
    }

    if (smf.selectedIndex == 0) {
        document.prefs.save_sent_mail.checked = 0;
    }
}

</script>

<?php echo Horde::label('sent_mail_folder', _("Sent mail folder:")) ?><br />
<select name="sent_mail_folder" id="sent_mail_folder" onchange="newFolderName();">
  <option value=""><?php echo _("None") ?></option>
  <option value="-1" selected="selected"><?php echo _("Use Default Value") ?></option>
  <?php echo IMP::flistSelect(array('heading' => _("Create a new sent mail folder"), 'filter' => array('INBOX'))) ?>
</select><br />
<input type="hidden" name="sent_mail_new" value="" />
<?php endif; ?>
