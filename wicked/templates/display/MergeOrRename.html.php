<div class="horde-form">
<form method="post" action="<?php echo $this->formAction ?>" name="TitleSearch">
<?php echo $this->formInput ?>
<input type="hidden" name="page" value="MergeOrRename" />
<input type="hidden" name="referrer" value="<?php echo $this->h($this->referrer) ?>" />
<input type="hidden" name="actionID" value="special" />

<h1 class="header">
 <?php echo $this->pageName ?>: <a href="<?php echo $this->referrerLink ?>"><?php echo $this->referrer ?></a>
</h1>

<table>
 <tr valign="top">
  <td class="horde-form-label" width="15%">
   <span class="form-error"><?php echo $this->requiredMarker ?></span>&nbsp;
   <strong><?php echo _("New name") ?></strong>
   <span class="form-error"><?php echo $this->errors['new_name'] ?></span>
  </td>
  <td>
   <input type="text" name="new_name" size="40" value="<?php echo $this->new_name ?>" />
  </td>
 </tr>

 <tr valign="top">
  <td class="horde-form-label">
   <span class="form-error"><?php echo $this->requiredMarker ?></span>&nbsp;
   <strong><?php echo _("If a page with the new name already exists") ?></strong>
   <span class="form-error"><?php echo $this->errors['collision'] ?></span>
  </td>
  <td>
   <input type="radio" name="collision" value="merge" checked="checked" />
   <?php echo _("Add the text from this page") ?><br />
   <input type="radio" name="collision" value="fail" />
   <?php echo _("Stop and don't do anything") ?><br />
  </td>
 </tr>

 <tr>
  <td class="control" colspan="2">
   <strong><?php echo _("Change references to this page from:") ?></strong>
  </td>
 </tr>
 <tr>
  <td colspan="2">&nbsp;<em><?php echo $this->referenceCount ?></em></td>
 </tr>

<?php foreach ($this->references as $reference): ?>
 <tr>
  <td class="horde-form-label">
   <input type="checkbox" name="ref[<?php echo $reference['checkbox'] ?>]" checked="checked" />
  </td>
  <td>
   <strong><a href="<?php echo $this->h($reference['page_url']) ?>"><?php echo $this->h($reference['page_name']) ?></a></strong>
  </td>
 </tr>
<?php endforeach ?>

 <tr>
  <td class="horde-form-buttons" colspan="2">
   <input type="submit" class="horde-default" name="submit" value="<?php echo _("Submit") ?>" />
   <input type="submit" class="horde-cancel" name="submit" value="<?php echo _("Cancel") ?>" />
  </td>
 </tr>
</table>
</form>
</div>
