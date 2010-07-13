<center>
<form id="folder" name="folder">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="cmd" value="new" />
<input type="hidden" name="create" value="1" />
<table>
<tr>
  <td colspan="2" align="center" class="header"><?php echo _("Create a new gallery") ?></td>
</tr>
<tr>
  <td class="light" align="right" valign="top">
    <?php echo _("New Gallery Title") ?>
  </td>
  <td align="left">
    <input id="gallery_name" type="text" name="gallery_name" value="<?php echo htmlspecialchars(Horde_Util::getFormData('gallery_name')) ?>" size="50" maxlength="100" />
  </td>
</tr>

<tr>
  <td class="light" align="right" valign="top">
    <?php echo _("New Gallery Description") ?>
  </td>
  <td align="left">
    <textarea name="gallery_desc" cols="33" rows="5"><?php echo htmlspecialchars($gallery_desc, ENT_COMPAT, $GLOBALS['registry']->getCharset()) ?></textarea>
  </td>
</tr>
</table>
</form>
</center>
