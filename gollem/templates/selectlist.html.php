<form method="post" name="selectlist" id="selectlist" enctype="multipart/form-data" action="<?php echo $this->self_url ?>">
<?php echo $this->forminput ?>
<input type="hidden" name="actionID" id="actionID" value="" />
<input type="hidden" name="cacheid" id="cacheid" value="<?php echo $this->cacheid ?>" />
<input type="hidden" name="dir" value="<?php echo $this->currdir ?>" />
<input type="hidden" name="formid" id="formid" value="<?php echo $this->formid ?>" />

<h1 class="header">
 <strong><?php echo $this->navlink ?></strong>
 <?php if ($this->changeserver): ?><?php echo $this->changeserver ?><?php endif ?>
</h1>

<?php if (!$this->entries): ?>
<div class="text">
 <em><?php echo _("There are no files in this folder.") ?></em>
</div>
<?php else: ?>
<?php foreach ($this->entries as $entry): ?>
<div class="<?php echo $entry['selected'] ? 'selected' : $entry['item'] ?> nowrap">
 <label><?php if (!$entry['dir']): ?><input type="checkbox" name="items[]" value="<?php echo $entry['name'] ?>" /><?php endif ?>
 <input type="hidden" name="itemTypes[]" value="<?php echo $entry['type'] ?>" /><?php echo $entry['graphic'] ?>&nbsp;
 <?php echo $entry['link'] ?></label>
</div>
<?php endforeach ?>
<?php endif ?>

<div style="text-align: center">
 <input type="button" class="horde-default" id="addbutton" value="<?php echo _("Add") ?>" />
 <input type="button" id="donebutton" value="<?php echo _("Done") ?>" />
 <input type="button" class="horde-cancel" id="cancelbutton" value="<?php echo _("Cancel") ?>" />
</div>
</form>
