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

<?php
$option_edit = (!empty($GLOBALS['folder']) ? $GLOBALS['folder']->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT) : false);
$option_delete = (!empty($GLOBALS['folder']) ? $GLOBALS['folder']->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE) : false);
$num_items = count(!empty($GLOBALS['bookmarks']) ? $GLOBALS['bookmarks'] : 0);
?>

<div id="bookmarkList">
 <?php if ($option_edit): ?>
 <form name="trean_bookmarks" action="edit.php" method="post">
  <?php echo Horde_Util::formInput() ?>
  <input type="hidden" name="actionID" value="button" />
  <input type="hidden" name="f" value="<?php echo $GLOBALS['folder']->getId() ?>" />
 <div id="bookmarkActions">
  <label for="ba_new">
   <input type="image" src="<?php echo Horde_Themes::img('add.png') ?>" name="new_bookmark" value="<?php echo _("New Bookmark") ?>" id="ba_new" />
   <?php echo Horde::highlightAccessKey(_("_New Bookmark"), Horde::getAccessKey(_("_New Bookmark"))) ?>
  </label>

  <label for="ba_edit">
   <input type="image" src="<?php echo Horde_Themes::img('edit.png') ?>" name="edit_bookmarks" value="<?php echo _("Edit Bookmarks") ?>" id="ba_edit" />
   <?php echo Horde::highlightAccessKey(_("_Edit Bookmarks"), Horde::getAccessKey(_("_Edit Bookmarks"))) ?>
  </label>
  <?php if ($option_delete): ?>
  <label for="ba_del">
   <input type="image" src="<?php echo Horde_Themes::img('delete.png') ?>" name="delete_bookmarks" value="<?php echo _("Delete Bookmark") ?>" id="ba_del" />
   <?php echo Horde::highlightAccessKey(_("_Delete Bookmarks"), Horde::getAccessKey(_("_Delete Bookmarks"))) ?>
  </label>
 <?php endif; ?>
 </div>
<?php endif; ?>
<?php
if ($num_items) {
    $view = new Trean_View_BookmarkList($GLOBALS['bookmarks']);
    echo $view->render();
} else {
    echo '<p><em>' . _("There are no bookmarks in this folder") . '</em></p>';
}
?>
<?php if ($option_edit): ?>
    </form>
<?php endif; ?>
</div>
