<form name="browse" action="<?php echo Horde::url('add.php'); ?>" method="post">
<?php Horde_Util::pformInput() ?>
  <input type="hidden" name="actionID" value="" />
  <input type="hidden" name="name" value="" />
  <input type="hidden" name="f" value="<?php echo htmlspecialchars(Horde_Util::getFormData('f')) ?>" />
</form>

<form name="edit" action="<?php echo Horde::url('edit.php'); ?>" method="post">
<?php Horde_Util::pformInput() ?>
  <input type="hidden" name="actionID" value="" />
  <input type="hidden" name="name" value="" />
  <input type="hidden" name="f" value="<?php echo htmlspecialchars(Horde_Util::getFormData('f')) ?>" />
</form>

<div id="bookmarkList">
 <form name="trean_bookmarks" action="edit.php" method="post">
  <?php echo Horde_Util::formInput() ?>
  <input type="hidden" name="actionID" value="button" />
 <div id="bookmarkActions">
  <label for="ba_new">
   <input type="image" src="<?php echo Horde_Themes::img('add.png') ?>" name="new_bookmark" value="<?php echo _("New Bookmark") ?>" id="ba_new" />
   <?php echo Horde::highlightAccessKey(_("_New Bookmark"), Horde::getAccessKey(_("_New Bookmark"))) ?>
  </label>
  <label for="ba_edit">
   <input type="image" src="<?php echo Horde_Themes::img('edit.png') ?>" name="edit_bookmarks" value="<?php echo _("Edit Bookmarks") ?>" id="ba_edit" />
   <?php echo Horde::highlightAccessKey(_("_Edit Bookmarks"), Horde::getAccessKey(_("_Edit Bookmarks"))) ?>
  </label>
  <label for="ba_del">
   <input type="image" src="<?php echo Horde_Themes::img('delete.png') ?>" name="delete_bookmarks" value="<?php echo _("Delete Bookmark") ?>" id="ba_del" />
   <?php echo Horde::highlightAccessKey(_("_Delete Bookmarks"), Horde::getAccessKey(_("_Delete Bookmarks"))) ?>
  </label>
 </div>
<?php
if (count($bookmarks)) {
    $view = new Trean_View_BookmarkList($GLOBALS['bookmarks']);
    echo $view->render();
} else {
    echo '<p><em>' . _("No bookmarks. Drag the \"New Bookmark\" link to your browser's toolbar to add them easily!") . '</em></p>';
}
?>
<?php if ($option_edit): ?>
    </form>
<?php endif; ?>
</div>
