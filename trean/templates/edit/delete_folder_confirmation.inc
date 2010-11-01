<div id="delete-folder-confirmation">

<p><?php printf(_("Really delete \"%s\" and all of its bookmarks?"), htmlspecialchars($GLOBALS['folder']->get('name'))) ?></p>

<form action="edit.php" method="post">
 <?php echo Horde_Util::formInput() ?>
 <input type="hidden" name="actionID" value="del_folder_confirmed" />
 <input type="hidden" name="f" value="<?php echo $GLOBALS['folder']->getId() ?>" />
 <input type="submit" class="button" value="<?php echo _("Yes") ?>" />
</form>

<form action="browse.php" method="get">
 <?php echo Horde_Util::formInput() ?>
 <input type="hidden" name="f" value="<?php echo $GLOBALS['folder']->getId() ?>" />
 <input id="delete-folder-confirmation-cancel" type="submit" class="button" value="<?php echo _("No") ?>" />
</form>

</div>
