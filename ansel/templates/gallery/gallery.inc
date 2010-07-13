<form method="post" name="gallery" action="<?php echo Horde::selfUrl() ?>" onsubmit="return checkCategory();">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="save" />
<?php if ($actionID == 'addchild'): ?>
<input type="hidden" name="parent" value="<?php echo (int)$parentId ?>" />
<?php elseif ($actionID != 'add'): ?>
<input type="hidden" name="gallery" value="<?php echo isset($galleryId) ? (int)$galleryId : '' ?>" />
<?php endif; ?>
<input type="hidden" name="url" value="<?php echo htmlspecialchars(Horde_Util::getFormData('url')) ?>" />

<h1 class="header">
 <?php echo htmlspecialchars($title, ENT_COMPAT, $GLOBALS['registry']->getCharset()) ?>
</h1>

<table cellspacing="0" width="100%" class="striped headerbox">
<tr>
 <td align="right" valign="top"><strong><?php echo _("Gallery Parent") ?></strong>&nbsp;</td>
 <td>
  <select name="gallery_parent" id="gallery_parent">
   <option value=""><?php echo _("Top Level Gallery") ?></option>
   <?php echo Ansel::selectGalleries(array('selected' => $gallery_parent,
                                           'perm' => Horde_Perms::EDIT,
                                           'ignore' => $galleryId))?>
  </select>
 </td>
</tr>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Category") ?></strong>&nbsp;</td>
  <td>
    <input type="hidden" name="new_category" value="" />
    <?php echo Horde_Prefs_CategoryManager::getJavaScript('gallery', 'gallery_category') .
               Horde_Prefs_CategoryManager::getSelect("gallery_category", $gallery_category) ?>
  </td>
</tr>

<?php
if ($prefs->isLocked('default_gallerystyle')) {
    echo '<input type="hidden" name="gallery_style" value="' . $prefs->getValue('default_gallerystyle') . '" />';
} else {
?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Style for this gallery") ?></strong>&nbsp;</td>
  <td>
   <?php echo Ansel::getStyleSelect('gallery_style', $gallery_thumbstyle) ?>
  </td>
</tr>
<?php } ?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Display Mode") ?></strong></td>
  <td>
    <select name="view_mode">
     <option value="Normal" <?php echo ((empty($gallery_mode) || $gallery_mode == 'Normal') ? 'selected="selected"' : '') ?>><?php echo _("Normal") ?></option>
     <option value="Date" <?php echo ($gallery_mode == 'Date' ? 'selected="selected"' : '') ?>><?php echo _("Group By Date") ?></option>
    </select>
  </td>
</tr>
<tr>
  <td align="right" valign="top"><?php echo Horde::img('required.png') ?><strong><?php echo _("Gallery Display Name") ?></strong>&nbsp;</td>
  <td>
    <input name="gallery_name" id="gallery_name" type="text" value="<?php echo htmlspecialchars($gallery_name) ?>" size="50" maxlength="100" />
  </td>
</tr>

<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Description") ?></strong>&nbsp;</td>
  <td>
    <textarea name="gallery_desc" cols="50" rows="5"><?php echo htmlspecialchars($gallery_desc) ?></textarea>
  </td>
</tr>
<tr>
  <td align="right" valign="top"><strong id="slug_flag"><?php echo _("Gallery Slug") ?></strong>&nbsp;</td>
  <td>
   <input name="gallery_slug" id="gallery_slug" type="text" value="<?php echo htmlspecialchars($gallery_slug) ?>" size="50" /><br />
   <?php if ($GLOBALS['conf']['urls']['pretty'] == 'rewrite') echo _("Slugs allows direct access to this gallery by visiting:") . ': ' . Horde::applicationUrl('gallery/slugname', true) ?><br />
   <?php echo _("Slug names may contain only letters, numbers, @, or _ (underscore).") ?>
  </td>
 </tr>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Tags") ?></strong>&nbsp;</td>
  <td><input name="gallery_tags" type="text" value="<?php echo htmlspecialchars($gallery_tags) ?>" size="50" /><br />
   <?php echo _("Separate tags with commas."); ?>
 </td>
</tr>

<?php if (!empty($conf['ages']['limits'])) { ?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Ages") ?></strong>&nbsp;</td>
  <td><select name="gallery_age">
   <option value="0" <?php echo (empty($gallery_age) ? 'selected="selected"' : '') ?>><?php echo _("Allow all ages") ?></option>
<?php
    foreach ($conf['ages']['limits'] as $age) {
        echo '<option value="' . $age . '"'
                . ($gallery_age == $age ? ' selected="selected"' : '' )
                . '>' . sprintf(_("User must be over %d"), $age) . '</option>';
    }
?>
</select>
 </td>
</tr>
<?php } ?>

<?php
if ($prefs->isLocked('default_download')) {
    echo '<input type="hidden" name="default_download" value="' . $prefs->getValue('default_download') . '" />';
} else {
?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Who should be allowed to download original photos?") ?></strong>&nbsp;</td>
  <td>
    <select name="gallery_download">
        <option value="all" <?php if ($gallery_download == 'all')  echo 'selected="selected"'; ?>><?php echo _("Anyone") ?></option>
        <option value="authenticated" <?php if ($gallery_download == 'authenticated') echo 'selected="selected"'; ?>><?php echo _("Authenticated users") ?></option>
        <option value="edit" <?php if ($gallery_download == 'edit') echo 'selected="selected"'; ?>><?php echo _("Users with edit permissions") ?></option>
    </select>
  </td>
</tr>
<?php } ?>
<?php if ($GLOBALS['registry']->getAuth() && !empty($gallery) && $GLOBALS['registry']->getAuth() == $gallery->get('owner')): ?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Password") ?></strong>&nbsp;</td>
  <td><input name="gallery_passwd" type="password" value="<?php echo htmlspecialchars($gallery_passwd) ?>" size="50" /></td>
</tr>
<?php endif; ?>
<tr>
  <td></td>
  <td>
   <input type="submit" id="gallery_submit" name="gallery_submit" class="button" value="<?php echo _("Save Gallery") ?>" />&nbsp;
   <input type="reset" class="button" value="<?php echo _("Undo Changes") ?>"  />&nbsp;
  </td>
</tr>
</table>
</form>
