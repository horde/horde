<form method="post" name="import_form" enctype="multipart/form-data" action="data.php">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="import" />
<h1 class="header"><?php echo _("Import Bookmarks") ?></h1>
<div class="headerbox leftAlign">
 <p><?php echo _("Mozilla/Firefox users will need to export their current Bookmarks by going into \"Bookmark Manager\" and selecting \"Export\" from the \"Tools\" menu.") ?></p>
 <p><?php echo _("Internet Explorer users will need to export their current Favorites by going to the \"File\" menu and selecting \"Import and Export\".") ?></p>

 <p><?php echo _("File to import:") ?> <input type="file" name="import_file" size="40" /></p>

 <p><?php echo _("Folder to import into:") ?>
 <select name="target">
  <?php echo Trean::folderSelect() ?>
 </select></p>

 <input type="submit" value="<?php echo _("Import") ?>" class="button" />
</div>
</form>

<br class="spacer" />
