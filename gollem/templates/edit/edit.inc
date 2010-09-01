<form name="edit_form" action="<?php echo Horde::url('edit.php') ?>" method="post">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="save_file" />
<input type="hidden" name="driver" value="<?php echo $driver ?>" />
<input type="hidden" name="dir" value="<?php echo $filedir ?>" />
<input type="hidden" name="file" value="<?php echo $filename ?>" />

<h1 class="header"><?php echo sprintf(_("Edit %s"), $filename) ?></h1>

<textarea name="content" id="content" rows="40" style="width:99%;"><?php echo htmlspecialchars($data) ?></textarea>
<input type="submit" class="button" value="<?php echo _("Save") ?>" />
<input type="reset" class="button" value="<?php echo _("Reset") ?>" />
<input type="button" class="button" value="<?php echo _("Cancel") ?>" onclick="window.close()" />
</form>

<script type="text/javascript">
document.edit_form.content.focus();
</script>
