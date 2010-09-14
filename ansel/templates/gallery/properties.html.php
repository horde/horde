<?php
/**
 * Main template for rendering gallery property page
 * Expects the following properties to be set:
 * <pre>
 *   (string)action    The current actionID
 *   (int)parent       The parent id
 *   (int)id           The current gallery id, if not new.
 *   (Horde_Url)url    The return url
 *   (string)title     The form title
 *   (string)mode      The gallery mode
 *   (array)properties The gallery properties
 *
 *
 * </pre>
 */
?>
<form method="post" name="gallery" action="<?php echo Horde::selfUrl() ?>" >
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="save" />
<?php if ($this->action == 'addchild'): ?>
  <input type="hidden" name="parent" value="<?php echo $this->properties['parent'] ?>" />
<?php elseif ($this->action != 'add'): ?>
  <input type="hidden" name="gallery" value="<?php echo isset($this->properties['id']) ? $this->properties['id'] : '' ?>" />
<?php endif; ?>
<input type="hidden" name="url" value="<?php echo $this->url->setRaw(false)->toString() ?>" />
<h1 class="header">
 <?php echo $this->h($this->title); ?>
</h1>
<table cellspacing="0" width="100%" class="striped headerbox">
<tr>
 <td align="right" valign="top"><strong><?php echo _("Gallery Parent") ?></strong>&nbsp;</td>
 <td>
  <select name="gallery_parent" id="gallery_parent">
   <option value=""><?php echo _("Top Level Gallery") ?></option>
   <?php echo Ansel::selectGalleries(array('selected' => $this->properties['parent'],
                                           'perm' => Horde_Perms::EDIT,
                                           'ignore' => $this->properties['id']))?>
  </select>
 </td>
</tr>
<?php if ($GLOBALS['prefs']->isLocked('default_gallerystyle')): ?>
  <!--<input type="hidden" name="gallery_style" value="<?php //echo $GLOBALS['prefs']->getValue('default_gallerystyle') ?>" />';-->
<?php else: ?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Style for this gallery") ?></strong>&nbsp;</td>
  <td>
   <?php //echo Ansel::getStyleSelect('gallery_style', $this->style) ?>
  </td>
</tr>
<?php endif; ?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Display Mode") ?></strong></td>
  <td>
    <select name="view_mode">
     <option value="Normal" <?php echo ((empty($this->properties['mode']) || $this->properties['mode'] == 'Normal') ? 'selected="selected"' : '') ?>><?php echo _("Normal") ?></option>
     <option value="Date" <?php echo ($this->properties['mode'] == 'Date' ? 'selected="selected"' : '') ?>><?php echo _("Group By Date") ?></option>
    </select>
  </td>
</tr>
<tr>
  <td align="right" valign="top"><?php echo Horde::img('required.png') ?><strong><?php echo _("Gallery Display Name") ?></strong>&nbsp;</td>
  <td>
    <input name="gallery_name" id="gallery_name" type="text" value="<?php echo $this->h($this->properties['name']) ?>" size="50" maxlength="100" />
  </td>
</tr>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Description") ?></strong>&nbsp;</td>
  <td>
    <textarea name="gallery_desc" cols="50" rows="5"><?php echo $this->h($this->properties['desc']) ?></textarea>
  </td>
</tr>
<tr>
  <td align="right" valign="top"><strong id="slug_flag"><?php echo _("Gallery Slug") ?></strong>&nbsp;</td>
  <td>
   <input name="gallery_slug" id="gallery_slug" type="text" value="<?php echo $this->h($this->properties['slug']) ?>" size="50" /><br />
   <?php if ($GLOBALS['conf']['urls']['pretty'] == 'rewrite') echo _("Slugs allows direct access to this gallery by visiting:") . ': ' . Horde::url('gallery/slugname', true) ?><br />
   <?php echo _("Slug names may contain only letters, numbers, @, or _ (underscore).") ?>
  </td>
 </tr>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Tags") ?></strong>&nbsp;</td>
  <td><input name="gallery_tags" type="text" value="<?php echo $this->h($this->properties['tags']) ?>" size="50" /><br />
   <?php echo _("Separate tags with commas."); ?>
 </td>
</tr>
<?php if (!empty($conf['ages']['limits'])): ?>
<tr>
  <td align="right" valign="top"><strong><?php echo _("Gallery Ages") ?></strong>&nbsp;</td>
  <td><select name="gallery_age">
   <option value="0" <?php echo (empty($this->properties['age']) ? 'selected="selected"' : '') ?>><?php echo _("Allow all ages") ?></option>
<?php
    foreach ($GLOBALS['conf']['ages']['limits'] as $age) {
        echo '<option value="' . $age . '"'
                . ($this->properties['age'] == $age ? ' selected="selected"' : '' )
                . '>' . sprintf(_("User must be over %d"), $age) . '</option>';
    }
?>
</select>
 </td>
</tr>
<?php endif; ?>
<?php if ($GLOBALS['prefs']->isLocked('default_download')): ?>
  <input type="hidden" name="default_download" value="<?php echo $GLOABLS['prefs']->getValue('default_download') ?>" />';
<?php else: ?>
  <tr>
    <td align="right" valign="top"><strong><?php echo _("Who should be allowed to download original photos?") ?></strong>&nbsp;</td>
    <td>
      <select name="gallery_download">
        <option value="all" <?php if ($this->properties['download'] == 'all')  echo 'selected="selected"'; ?>><?php echo _("Anyone") ?></option>
        <option value="authenticated" <?php if ($this->properties['download'] == 'authenticated') echo 'selected="selected"'; ?>><?php echo _("Authenticated users") ?></option>
        <option value="edit" <?php if ($this->properties['download'] == 'edit') echo 'selected="selected"'; ?>><?php echo _("Users with edit permissions") ?></option>
      </select>
    </td>
  </tr>
<?php endif; ?>
<?php if ($GLOBALS['registry']->getAuth() && $GLOBALS['registry']->getAuth() == $this->properties['owner']): ?>
  <tr>
    <td align="right" valign="top"><strong><?php echo _("Gallery Password") ?></strong>&nbsp;</td>
    <td><input name="gallery_passwd" type="password" value="<?php echo $this->h($this->properties['passwd']) ?>" size="50" /></td>
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
