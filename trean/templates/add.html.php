<form name="bookmark" action="add.php" method="post">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="newFolder" value="" />
<input type="hidden" name="actionID" value="add_bookmark" />
<input type="hidden" name="popup" value="<?php echo (int)Horde_Util::getFormData('popup') ?>" />
<input type="hidden" name="iframe" value="<?php echo (int)Horde_Util::getFormData('iframe') ?>" />

<h1 class="header">
  <?php echo _("New Bookmark") ?>
</h1>

<div class="horde-content horde-form">
<table cellspacing="0">
<tr>
  <td class="horde-form-label"><?php echo _("URL") ?></td>
  <td><input type="text" name="url" size="40" value="<?php echo htmlspecialchars(new Trean_Url(Horde_Util::getFormData('url', 'http://'))) ?>" /></td>
</tr>

<tr>
  <td class="horde-form-label"><?php echo _("Title") ?></td>
  <td><input type="text" name="title" size="40" value="<?php echo htmlspecialchars(Horde_Util::getFormData('title')) ?>" /></td>
</tr>

<tr>
  <td class="horde-form-label"><?php echo _("Description") ?></td>
  <td><input type="text" name="description" size="40" value="<?php echo htmlspecialchars(Horde_Util::getFormData('description')) ?>" /></td>
</tr>

<tr>
  <td class="horde-form-label"><?php echo _("Tags") ?></td>
  <td>
    <input id="treanBookmarkTags" name="treanBookmarkTags">
  </td>
</tr>

 <tr>
  <td class="rightAlign">
  <span id="treanBookmarkTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  </td>
  <td>
  <a id="loadTags"><?php echo _("See previously used tags")?></a>
  <div id="treanTopTagsWrapper" style="display:none;">
    <label for="treanBookmarkTopTags"><strong><?php echo _("Previously used tags") ?></strong>:</label><br />
    <div class="treanTopTags" id="treanBookmarkTopTags"></div>
  </div>
  </td>
 </tr>
</table>
</div>

<div class="horde-form-buttons">
 <input type="submit" class="horde-default" value="<?php echo _("Add") ?>">
 <input type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" onclick="<?php echo Horde_Util::getFormData('popup') ? 'window.close();' : 'window.history.go(-1);'; ?>" />
</div>

</form>

<?php if (!Horde_Util::getFormData('popup') && !Horde_Util::getFormData('iframe')): ?>
<div class="box leftAlign" id="trean-browser-instructions">
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
