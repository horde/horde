<script type="text/javascript">
function cancelEdit()
{
<?php if (!Horde_Util::getFormData('popup')) { ?>
    window.history.back();
    return false;
<?php } else { ?>
    return !window.close();
<?php } ?>
}
</script>

<form name="bookmark_edit_form" action="<?php echo Horde::url('b/save') ?>" method="post">
<input type="hidden" name="bookmark" value="<?php echo $bookmark->id ?>" />

<h1 class="header">
 <?php echo Horde::img(Trean::getFavicon($bookmark), '', 'class="trean-favicon"', '') ?>
 <?php echo htmlspecialchars($bookmark->title) ?>
</h1>

<div class="horde-content horde-form">
<table cellspacing="0">
 <tr>
  <td class="horde-form-label"><?php echo _("URL") ?></td>
  <td><input type="text" name="bookmark_url" size="40" value="<?php echo htmlspecialchars($bookmark->url) ?>" /></td>
 </tr>

 <tr>
  <td class="horde-form-label" width="1"><?php echo _("Title") ?></td>
  <td><input type="text" name="bookmark_title" size="40" value="<?php echo htmlspecialchars($bookmark->title) ?>" /></td>
 </tr>

 <tr>
  <td class="horde-form-label"><?php echo _("Description") ?></td>
  <td><input type="text" name="bookmark_description" size="40" value="<?php echo htmlspecialchars($bookmark->description) ?>" /></td>
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
 <input type="submit" class="horde-default" name="submit" value="<?php echo _("Save") ?>">
 <input type="submit" class="horde-cancel" name="cancel" value="<?php echo _("Cancel") ?>" onclick="return cancelEdit();">
</div>
</form>
