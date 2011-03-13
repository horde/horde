<form method="get" name="export_form" action="data.php">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="export" />
<h1 class="header"><?php echo _("Export Bookmarks") ?></h1>
<div class="headerbox leftAlign">
 <input type="submit" class="button" value="<?php echo _("Download Folder") ?>" />
</div>
</form>
