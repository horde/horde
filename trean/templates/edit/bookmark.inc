<input type="hidden" name="bookmarks[]" value="<?php echo $bookmark->id ?>" />

<h1 class="header">
 <?php echo Horde::img(Trean::getFavicon($bookmark), '', 'class="favicon"', '') ?>
 <?php echo htmlspecialchars($bookmark->title) ?>
</h1>

<table width="100%" cellspacing="0">
 <tr>
  <td align="right" width="1"><strong><?php echo _("Title") ?>&nbsp;</strong></td>
  <td><input type="text" name="title[<?php echo $bookmark->id ?>]" size="40" value="<?php echo htmlspecialchars($bookmark->title) ?>" /></td>
 </tr>

 <tr>
  <td align="right"><strong><?php echo _("Description") ?>&nbsp;</strong></td>
  <td><input type="text" name="description[<?php echo $bookmark->id ?>]" size="40" value="<?php echo htmlspecialchars($bookmark->description) ?>" /></td>
 </tr>

 <tr>
  <td align="right"><strong><?php echo _("URL") ?>&nbsp;</strong></td>
  <td><input type="text" name="url[<?php echo $bookmark->id ?>]" size="40" value="<?php echo htmlspecialchars($bookmark->url) ?>" /></td>
 </tr>

 <tr>
  <td align="right"><strong><?php echo _("Folder") ?>&nbsp;</strong></td>
  <td>
  <select name="new_folder[<?php echo $bookmark->id ?>]">
    <?php echo Trean::folderSelect($bookmark->folder, Horde_Perms::EDIT) ?>
  </select>
  </td>
 </tr>
</table>
<br />
