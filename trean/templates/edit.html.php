<script type="text/javascript">
function cancelEdit()
{
<?php if (!Horde_Util::getFormData('popup')) { ?>
    window.history.back();
<?php } else { ?>
    return !window.close();
<?php } ?>
}
</script>

<form name="bookmark_edit_form" action="<?php echo Horde::url('b/save') ?>" method="post">
<input type="hidden" name="bookmark" value="<?php echo $bookmark->id ?>" />

<h1 class="header">
 <?php echo Horde::img(Trean::getFavicon($bookmark), '', 'class="favicon"', '') ?>
 <?php echo htmlspecialchars($bookmark->title) ?>
</h1>

<table cellspacing="0">
 <tr>
  <td align="right"><strong><?php echo _("URL") ?></strong></td>
  <td><input type="text" name="bookmark_url" size="40" value="<?php echo htmlspecialchars($bookmark->url) ?>" /></td>
 </tr>

 <tr>
  <td align="right" width="1"><strong><?php echo _("Title") ?></strong></td>
  <td><input type="text" name="bookmark_title" size="40" value="<?php echo htmlspecialchars($bookmark->title) ?>" /></td>
 </tr>

 <tr>
  <td align="right"><strong><?php echo _("Description") ?></strong></td>
  <td><input type="text" name="bookmark_description" size="40" value="<?php echo htmlspecialchars($bookmark->description) ?>" /></td>
 </tr>

 <tr>
  <td></td>
  <td>
  <div class="horde-DialogInfo"><?php echo _("Categorize your bookmark with comma separated tags.") ?></div>
  <input id="treanBookmarkTags" name="bookmark_tags">
  <label for="treanBookmarkTopTags"><?php echo _("Previously used tags") ?>:</label><br />
  <span id="treanBookmarkTags_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  <div class="treanTopTags" id="treanBookmarkTopTags"></div>
  </td>
 </tr>

</table>
<br />

<input type="submit" class="horde-default" name="submit" value="<?php echo _("Save") ?>">
<input type="submit" class="horde-cancel" name="cancel" value="<?php echo _("Cancel") ?>" onclick="return cancelEdit();">
</form>
