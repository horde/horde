<?php if (!$prefs->isLocked('drafts_folder')): ?>
<script type="text/javascript">

function newDraftsFolderName()
{
    var d = document.prefs.drafts, dn = document.prefs.drafts_new;
    d.onchange = null;
    if (d[d.selectedIndex].value == '') {
        var folder = window.prompt('<?php echo addslashes(_("Enter the name for your new drafts folder")) ?>', dn.value ? dn.value : '');
        if (folder != '') {
            dn.value = folder;
            d[1].text = '<?php echo addslashes(_("Create a new drafts folder")) ?> [' + dn.value + ']';
        }
    }
    d.onchange = newDraftsFolderName;
}

</script>

<?php echo Horde::label('drafts', _("Drafts folder:")) ?><br />
<input type="hidden" name="drafts_new" value="" />
<select id="drafts" name="drafts" onchange="newDraftsFolderName();">
  <option value="<?php echo IMP_PREF_NO_FOLDER ?>"><?php echo _("None") ?></option>
  <?php echo IMP::flistSelect(array('heading' => _("Create a new drafts folder"), 'filter' => array('INBOX'), 'selected' => IMP::folderPref($prefs->getValue('drafts_folder'), 'new_folder' => true))) ?>
</select>
<br />
<?php endif; ?>
