<script type="text/javascript">
function addBookmark() {
    if (document.bookmark.f.value == '*new*') {
        var newFolder = window.prompt('<?php echo addslashes(_("Please enter a name for the new folder:")) ?>\n', '');
        if (newFolder != null && newFolder != '') {
            document.bookmark.newFolder.value = newFolder;
            document.bookmark.submit();
        }
    } else {
        if (document.bookmark.f.value == '') {
            window.alert('<?php echo addslashes(_("You must select a target folder first")) ?>');
        } else {
            document.bookmark.submit();
        }
    }
}
</script>

<form name="bookmark" action="add.php" method="post">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="newFolder" value="" />
<input type="hidden" name="actionID" value="add_bookmark" />
<input type="hidden" name="popup" value="<?php echo (int)Horde_Util::getFormData('popup') ?>" />
<input type="hidden" name="iframe" value="<?php echo (int)Horde_Util::getFormData('iframe') ?>" />

<h1 class="header">
 <?php echo _("New Bookmark") ?>
</h1>

<table cellspacing="0">
<tr>
  <td class="light rightAlign"><strong><?php echo _("URL") ?></strong></td>
  <td><input type="text" name="url" size="40" value="<?php echo htmlspecialchars(Horde_Util::getFormData('url', 'http://')) ?>" /></td>
</tr>

<tr>
  <td class="light rightAlign"><strong><?php echo _("Title") ?></strong></td>
  <td><input type="text" name="title" size="40" value="<?php echo htmlspecialchars(Horde_Util::getFormData('title')) ?>" /></td>
</tr>

<tr>
  <td class="light rightAlign"><strong><?php echo _("Description") ?></strong></td>
  <td><input type="text" name="description" size="40" value="<?php echo htmlspecialchars(Horde_Util::getFormData('description')) ?>" /></td>
</tr>

<tr>
  <td class="light rightAlign"><strong><?php echo _("Folder") ?></strong></td>
  <td>
   <select name="f">
    <?php echo Trean::folderSelect(Horde_Util::getFormData('f'), Horde_Perms::EDIT, true) ?>
   </select>
  </td>
</tr>

<tr>
  <td>
  </td>
  <td>
   <input type="submit" class="button" value="<?php echo _("Add") ?>" onclick="addBookmark(); return false;" />
   <input type="button" class="button" value="<?php echo _("Cancel") ?>" onclick="<?php echo !Horde_Util::getFormData('popup') ? 'window.history.go(-1);' : 'window.close();'; ?>" />
  </td>
</tr>

</table>
</form>

<?php if (!Horde_Util::getFormData('popup') && !Horde_Util::getFormData('iframe')): ?>
<div class="box leftAlign" id="browser-instructions">
 <h3><?php echo _("To be able to quickly add bookmarks from your web browser:") ?></h3>
 <h4><?php echo _("Firefox/Mozilla") ?></h4>
 <p><?php echo _("Drag the \"Add to Bookmarks\" link below onto your \"Personal Toolbar\".") ?></p>
 <h4><?php echo _("Internet Explorer") ?></h4>
 <p><?php echo _("Drag the \"Add to Bookmarks\" link below onto your \"Links\" Bar") ?></p>
 <p><?php echo _("While browsing you will be able to bookmark the current page by clicking your new \"Add to Bookmarks\" shortcut.") ?></p>
 <p>
    <strong><?php echo _("Note:") ?></strong>
    <?php printf(_("On newer versions of Internet Explorer, you may have to add %s://%s to your Trusted Zone for this to work."), !empty($_SERVER['HTTPS']) ? 'https' : 'http', $conf['server']['name']) ?>
 </p>
<?php
$addurl = Horde::url(Horde_Util::addParameter('add.php', 'popup', 1), true, -1);
$url = "javascript:d = new Date(); w = window.open('$addurl' + '&amp;title=' + encodeURIComponent(document.title) + '&amp;url=' + encodeURIComponent(location.href) + '&amp;d=' + d.getTime(), d.getTime(), 'height=200,width=400'); w.focus();";
echo '<p><a href="' . $url . '">' . Horde::img('add.png') . _("Add to Bookmarks") . '</a></p>';
?>
</div>
<?php endif; ?>
