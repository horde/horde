<?php if ($this->list_count): ?>
<?php if ($this->perms_chmod): ?>
<div id="gollem-attributes" style="display:none;">
<table class="horde-table">
 <tr>
  <th width="25%">&nbsp;</th>
  <th width="25%" align="center"><?php echo _("Owner") ?></th>
  <th width="25%" align="center"><?php echo _("Group") ?></th>
  <th width="25%" align="center"><?php echo _("All") ?></th>
 </tr>
 <tr>
  <td><?php echo _("Read") ?></td>
  <td align="center"><input name="owner[]" type="checkbox" value="4" <?php if ($this->owner_read): ?>disabled="disabled" <?php endif ?>/></td>
  <td align="center"><input name="group[]" type="checkbox" value="4" <?php if ($this->group_read): ?>disabled="disabled" <?php endif ?>/></td>
  <td align="center"><input name="all[]" type="checkbox" value="4" <?php if ($this->all_read): ?>disabled="disabled" <?php endif ?>/></td>
 </tr>
 <tr>
  <td><?php echo _("Write") ?></td>
  <td align="center"><input name="owner[]" type="checkbox" value="2" <?php if ($this->owner_write): ?>disabled="disabled" <?php endif ?>/></td>
  <td align="center"><input name="group[]" type="checkbox" value="2" <?php if ($this->group_write): ?>disabled="disabled" <?php endif ?>/></td>
  <td align="center"><input name="all[]" type="checkbox" value="2" <?php if ($this->all_write): ?>disabled="disabled" <?php endif ?>/></td>
 </tr>
 <tr>
  <td><?php echo _("Execute") ?></td>
  <td align="center"><input name="owner[]" type="checkbox" value="1" <?php if ($this->owner_execute): ?>disabled="disabled" <?php endif ?>/></td>
  <td align="center"><input name="group[]" type="checkbox" value="1" <?php if ($this->group_execute): ?>disabled="disabled" <?php endif ?>/></td>
  <td align="center"><input name="all[]" type="checkbox" value="1" <?php if ($this->all_execute): ?>disabled="disabled" <?php endif ?>/></td>
 </tr>
</table>
</div>
<?php endif ?>
<?php endif ?>

<form method="post" id="manager" name="manager" enctype="multipart/form-data" action="<?php echo $this->action ?>">
<?php echo $this->forminput ?>
<input type="hidden" id="actionID" name="actionID" value="" />
<input type="hidden" id="new_folder" name="new_folder" value="" />
<input type="hidden" id="new_names" name="new_names" value="" />
<input type="hidden" id="old_names" name="old_names" value="" />
<input type="hidden" id="renamefrm_oldname" name="oldname" value="" />
<input type="hidden" id="chmod" name="chmod" value="" />
<input type="hidden" id="dir" name="dir" value="<?php echo $this->dir ?>" />
<input type="hidden" name="targetFolder" value="" />

<div class="header">
 <strong style="float:right"><?php echo $this->itemcount ?></strong>
 <?php echo $this->navlink ?>
</div>
<div class="horde-buttonbar">
 <ul>
  <li class="horde-icon"><?php echo $this->refresh ?></li>
<?php if ($this->share_folder): ?>
  <li class="horde-icon"><?php echo $this->share_folder ?></li>
<?php endif ?>
<?php if ($this->change_folder): ?>
  <li class="horde-icon"><?php echo $this->change_folder ?></li>
<?php endif ?>
<?php if ($this->list_count): ?>
<?php if ($this->perms_chmod): ?>
  <li><?php echo Horde::widget(array('url' => '#', 'title' => _("Change Permissions"), 'id' => 'gollem-chmod')) ?></li>
<?php endif ?>
<?php if ($this->hasclipboard): ?>
  <li><?php echo Horde::widget(array('url' => '#', 'title' => _("Copy"), 'id' => 'gollem-copy')) ?></li>
<?php if ($this->perms_delete): ?>
  <li><?php echo Horde::widget(array('url' => '#', 'title' => _("Cut"), 'id' => 'gollem-cut')) ?></li>
<?php endif ?>
<?php endif ?>
<?php if ($this->perms_delete): ?>
  <li><?php echo Horde::widget(array('url' => '#', 'title' => _("Delete"), 'id' => 'gollem-delete')) ?></li>
<?php endif ?>
<?php if ($this->perms_edit): ?>
  <li><?php echo Horde::widget(array('url' => '#', 'title' => _("Rename"), 'id' => 'gollem-rename')) ?></li>
<?php endif ?>
<?php endif ?>
 </ul>
</div>

<?php if ($this->empty_dir): ?>
<p class="text">
 <em><?php echo _("There are no files in this folder.") ?></em>
</p>
<?php else: ?>
<table class="horde-table">
<thead>
 <tr>
  <th style="text-align:center" width="1%"><input type="checkbox" class="checkbox" id="checkall" name="checkAll" <?php echo $this->checkall ?> /></th>
<?php foreach ($this->headers as $header): ?>
  <th<?php if (isset($header['id'])): ?> id="<?php echo $header['id'] ?>"<?php endif ?> class="horde-split-left<?php if ($header['class']) echo ' ' . $header['class'] ?>" style="text-align:<?php echo $header['align'] ?>" width="<?php echo $header['width'] ?>"><?php if (isset($header['sort'])) echo $header['sort'] ?><?php echo $header['label'] ?></th>
<?php endforeach ?>
 </tr>
</thead>
<tbody>
<?php foreach ($this->entries as $entry): ?>
 <tr>
  <td style="text-align:center"><?php if ($entry['on_clipboard']): ?>&nbsp;<?php else: ?><input type="checkbox" class="checkbox" name="items[]" value="<?php echo $entry['name'] ?>" /><?php endif ?></td>
<?php if ($this->columns_type): ?>
  <td class="rightAlign"><input type="hidden" name="itemTypes[]" value="<?php echo $entry['type'] ?>" /><?php echo $entry['graphic'] ?></td>
<?php endif ?>
<?php if ($this->columns_name): ?>
  <td><?php echo $entry['link'] ?></td>
<?php endif ?>
<?php if ($this->columns_share): ?>
  <td>
   <?php if (!empty($entry['share'])): ?><?php echo $entry['share'] ?><span class="iconImg <?php echo $entry['share_disabled'] ? 'gollem-sharefolder-disabled' : 'gollem-sharefolder' ?>"></span></a><?php endif ?>
  </td>
<?php endif ?>
<?php if ($this->columns_edit): ?>
  <td>
   <?php if ($entry['edit']): ?><?php echo $entry['edit'] ?><span class="iconImg gollem-edit"></span></a><?php endif ?>
  </td>
<?php endif ?>
<?php if ($this->columns_download): ?>
  <td>
   <?php if ($entry['dl']): ?><?php echo $entry['dl'] ?><span class="iconImg gollem-download"></span></a><?php endif ?>
  </td>
<?php endif ?>
<?php if ($this->columns_modified): ?>
  <td><?php echo $entry['date'] ?></td>
<?php endif ?>
<?php if ($this->columns_size): ?>
  <td class="rightAlign"><?php echo $entry['size'] ?></td>
<?php endif ?>
<?php if ($this->columns_permission): ?>
  <td class="rightAlign fixed">&nbsp;<?php echo $entry['perms'] ?>&nbsp;</td>
<?php endif ?>
<?php if ($this->columns_owner): ?>
  <td class="rightAlign"><?php echo $entry['owner'] ?></td>
<?php endif ?>
<?php if ($this->columns_group): ?>
  <td class="rightAlign"><?php echo $entry['group'] ?></td>
<?php endif ?>
 </tr>
<?php endforeach ?>
</tbody>
</table>
<?php endif ?>

<?php echo $this->page_caption ?>

<?php if ($this->perms_edit): ?>
<br />
<table id="filelist_upload">
 <tr>
  <td class="leftAlign">
   <div id="upload_row_1">
    <?php echo _("File") ?> 1:&nbsp;<input id="file_upload_1" name="file_upload_1" type="file" size="25" />
   </div>
  </td>
  <td class="leftAlign">
   <input type="button" class="button" id="uploadfile" value="<?php echo $this->upload_file ?>" /> <?php echo $this->upload_help ?>
  </td>
 </tr>
</table>
<?php endif ?>
</form>
