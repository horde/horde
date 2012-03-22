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
  <td></td>
  <td>
    <div class="horde-DialogInfo"><?php echo _("Categorize your bookmark with comma separated tags.") ?></div>
    <input id="treanBookmarkTags" name="tags" />
    <label for="treanBookmarkTopTags"><?php echo _("Previously used tags") ?>:</label><br />
    <span id="treanBookmarkTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
    <div class="treanTopTags" id="treanBookmarkTopTags"></div>
  </td>
</tr>

<tr>
  <td>
  </td>
  <td>
    <input type="submit" class="button" value="<?php echo _("Add") ?>">
    <input type="button" class="button" value="<?php echo _("Cancel") ?>" onclick="<?php echo Horde_Util::getFormData('popup') ? 'window.close();' : 'window.history.go(-1);'; ?>" />
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
  <p>
    <?php echo Trean::bookmarkletLink() ?>
  </p>
</div>
<?php endif; ?>
