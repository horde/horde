<form action="entry.php" method="post">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="delete" />
<input type="hidden" name="entry" value="<?php echo $entry['object_id'] ?>" />
<input type="hidden" name="view" value="<?php echo $viewName ?>" />
<div class="headerbox" style="padding: 8px">
 <p><?php echo _("Permanently delete this entry?") ?></p>
 <input type="submit" class="button" name="delete" value="<?php echo _("Delete") ?>" />
</div>
</form>
