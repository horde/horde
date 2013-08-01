<h1 class="header">
 <?php echo $this->pageName ?>: <a href="<?php echo $this->referrerLink ?>"><?php echo $this->referrer ?></a> <?php echo $this->refreshIcon ?>
</h1>

<br />
<table class="horde-table sortable" style="width:100%">
 <thead>
  <tr>
   <th width="25%"><?php echo _("Attachment Name") ?></th>
   <th width="1%">&nbsp;</th>
   <th width="4%"><?php echo _("Version") ?></th>
   <th width="10%"><?php echo _("Author") ?></th>
   <th width="20%"><?php echo _("Date") ?></th>
   <th width="35%"><?php echo _("Change Log") ?></th>
   <th width="5%" class="nowrap"><?php echo _("Downloads") ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($this->attachments as $attachment): ?>
  <tr>
   <td><a href="<?php echo $attachment['url'] ?>"><?php echo $attachment['attachment_name'] ?></a></td>
   <td>
<?php if ($attachment['delete_form']): ?>
     <form method="post" action="<?php echo $this->formAction ?>">
      <input type="hidden" name="cmd" value="delete" />
      <input type="hidden" name="actionID" value="special" />
      <input type="hidden" name="referrer" value="<?php echo $this->referrer ?>" />
      <input type="hidden" name="filename" value="<?php echo $attachment['attachment_name'] ?>" />
      <input type="hidden" name="version" value="<?php echo $attachment['attachment_version'] ?>" />
      <input type="image" class="img" src="<?php echo $this->deleteButton ?>" />
     </form>
<?php endif ?>
   </td>
   <td><?php echo $attachment['attachment_version'] ?></td>
   <td><?php echo $this->h($attachment['change_author']) ?></td>
   <td sortval="<?php echo $attachment['timestamp'] ?>"><?php echo $attachment['date'] ?></td>
   <td><?php echo $this->h($attachment['change_log']) ?></td>
   <td><?php echo $attachment['attachment_hits'] ?></td>
  </tr>
<?php endforeach ?>
 </tbody>
</table>

<?php if ($this->canAttach): ?>
<br />
<div class="horde-form">
<form method="post" action="<?php echo $this->formAction ?>" enctype="multipart/form-data" name="AttachedFiles">
<?php echo $this->formInput ?>
<input type="hidden" name="page" value="AttachedFiles" />
<input type="hidden" name="referrer" value="<?php echo $this->referrer ?>" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="is_update" value="0" />

<h1 class="header">
 <?php echo _("Attach a New File") ?>
</h1>
<table>
 <tr>
  <td class="horde-form-label" width="15%">
   <span class="form-error"><?php echo $this->requiredMarker ?></span>
   <?php echo _("File to attach") ?>
  </td>
  <td>
   <input type="file" size="30" name="attachment_file" />
  </td>
 </tr>
 <tr>
  <td class="horde-form-label">
   <span class="form-error"></span>
   <?php echo _("Name for this file") ?>
  </td>
  <td>
   <input type="text" size="30" name="filename" /><br />
   <?php echo _("If blank, will use the file's current name") ?>
  </td>
 </tr>
 <tr>
  <td class="horde-form-label">
   <span class="form-error">
<?php if ($this->requireChangelog): ?>
    <?php echo $this->requiredMarker ?>
<?php endif ?>
   </span>
   <?php echo _("Change log entry") ?>
  </td>
  <td>
   <input type="text" size="50" name="change_log" />
  </td>
 </tr>
 <tr>
  <td class="horde-form-buttons" colspan="2">
   <input class="horde-default" type="submit" name="submit" value="<?php echo _("Attach File") ?>" />
  </td>
 </tr>
</table>
</form>
</div>
<?php endif ?>

<?php if ($this->canUpdate): ?>
<br />
<div class="horde-form">
<form method="post" action="<?php echo $this->formAction ?>" enctype="multipart/form-data" name="AttachedFiles">
<?php echo $this->formInput ?>
<input type="hidden" name="page" value="AttachedFiles" />
<input type="hidden" name="referrer" value="<?php echo $this->referrer ?>" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="is_update" value="1" />
<h1 class="header">
 <?php echo _("Update an Attachment") ?>
</h1>
<table>
 <tr>
  <td class="horde-form-label" width="15%">
   <span class="form-error"><?php echo $this->requiredMarker ?></span>
   <?php echo _("File to attach") ?>
  </td>
  <td>
    <input type="file" size="30" name="attachment_file" />
  </td>
 </tr>
 <tr>
  <td class="horde-form-label">
   <span class="form-error"><?php echo $this->requiredMarker ?></span>
   <?php echo _("File to update") ?>
  </td>
  <td>
   <select name="filename">
    <option value=""><?php echo _("-- select --") ?></option>
<?php foreach ($this->files as $file): ?>
    <option value="<?php echo $this->h($file) ?>"><?php echo $this->h($file) ?></option>
<?php endforeach ?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="horde-form-label">
   <span class="form-error">
<?php if ($this->requireChangelog): ?>
   <?php echo $this->requiredMarker ?>
<?php endif ?>
   </span>
   <?php echo _("Change log entry") ?>
  </td>
  <td>
    <input type="text" size="50" name="change_log" />
  </td>
 </tr>
 <tr>
  <td class="horde-form-buttons" colspan="2">
   <input class="horde-default" type="submit" name="submit" value="<?php echo _("Update File") ?>" />
  </td>
 </tr>
</table>
</form>
</div>
<?php endif ?>
