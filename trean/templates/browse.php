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

<script type="text/javascript">
function faToggle()
{
    var pref_value;
    if (Element.hasClassName(document.body, 'folderActions')) {
        pref_value = 0;
        Element.removeClassName(document.body, 'folderActions');
    } else {
        pref_value = 1;
        Element.addClassName(document.body, 'folderActions');
    }

    new Ajax.Request('<?php echo Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/prefs/', true) ?>', { parameters: { app: 'trean', pref: 'show_folder_actions', value: pref_value } });
}

var delete_folder_confirmation_template;
function confirm_delete()
{
    if (!delete_folder_confirmation_template) {
        var tpl = $('delete-folder-confirmation-template');
        delete_folder_confirmation_template = tpl.innerHTML;
        tpl.parentNode.removeChild(tpl);
    }

    RedBox.overlay = true;
    RedBox.showHtml('<div id="RB_confirm">' + delete_folder_confirmation_template + '<\/div>');
    $('delete-folder-confirmation-cancel').observe('click', function(event) {
        RedBox.close();
        Event.stop(event);
    });
}
</script>
<?php
$option_move = Trean::countFolders(Horde_Perms::EDIT);
$option_copy = $option_move
    && (Trean::hasPermission('max_bookmarks') === true
        || Trean::hasPermission('max_bookmarks') > $GLOBALS['trean_shares']->countBookmarks());

$option_edit = (!empty($GLOBALS['folder']) ? $GLOBALS['folder']->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT) : false);
$option_delete = (!empty($GLOBALS['folder']) ? $GLOBALS['folder']->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE) : false);
$num_items = count(!empty($GLOBALS['bookmarks']) ? $GLOBALS['bookmarks'] : 0);
if (!empty($folder)):
?>
<div id="delete-folder-confirmation-template">
 <?php include TREAN_TEMPLATES . '/edit/delete_folder_confirmation.inc' ?>
</div>
<?php endif; ?>
<!-- Start grid -->
<div id="doc3" class="yui-t7">
 <?php if (!empty($GLOBALS['folder'])): ?>
 <div id="hd"><h1 class="header"><?php echo htmlspecialchars($GLOBALS['folder']->get('name')) ?></h1></div>
 <?php endif; ?>
 <div id="bd">
  <div class="yui-gf">
   <div class="yui-u first" id="folderList">
<?php if (!empty($GLOBALS['folder']) && $GLOBALS['registry']->getAuth()): ?>
 <div id="folderActions">
  <a id="faShow" href="#" onclick="faToggle(); return false;"><?php echo _("Folder Actions") ?></a>
  <a id="faHide" href="#" onclick="faToggle(); return false;"><?php echo _("Folder Actions") ?></a>
  <div id="folderActionsInner">
 <?php if (Trean::hasPermission('max_folders') === true ||
           Trean::hasPermission('max_folders') > Trean::countFolders()): ?>
  <form name="trean_new_subfolder" action="add.php" method="post">
   <?php echo Horde_Util::formInput() ?>
   <input type="hidden" name="actionID" value="add_folder" />
   <input type="hidden" name="f" value="<?php echo $GLOBALS['folder']->getId() ?>" />
   <?php echo Horde::img('folders/folder_create.png', _("New folder")) . Horde::label('sf_name', _("New folder")) ?>
   <input type="text" name="name" id="sf_name" />
  </form>
 <?php endif; ?>

 <?php if ($GLOBALS['folder']->getParent() && $option_delete): ?>
  <form name="trean_del_folder" action="edit.php" method="get">
   <?php echo Horde_Util::formInput() ?>
   <input type="hidden" name="actionID" value="del_folder" />
   <input type="hidden" name="f" value="<?php echo $GLOBALS['folder']->getId() ?>" />
   <input type="image" src="<?php echo Horde_Themes::img('folders/folder_delete.png') ?>" value="<?php echo _("Delete") ?>" id="sf_del" onclick="confirm_delete(); return false;" />
   <?php echo Horde::label('sf_del', _("Delete this folder")) ?>
  </form>
 <?php endif; ?>
 <?php if ($GLOBALS['folder']->getParent() && $option_edit): ?>
  <form name="trean_rename_folder" action="edit.php" method="post">
   <?php echo Horde_Util::formInput() ?>
   <input type="hidden" name="actionID" value="rename" />
   <input type="hidden" name="f" value="<?php echo $GLOBALS['folder']->getId() ?>" />
   <?php echo Horde::img('folders/folder_edit.png', _("Rename this folder")) . Horde::label('sf_rename', _("Rename this folder")) ?>
   <input type="text" name="name" id="sf_rename" value="<?php echo htmlspecialchars($GLOBALS['folder']->get('name')) ?>" />
  </form>
 <?php endif; ?>

 <?php if ($GLOBALS['folder']->get('owner') == $GLOBALS['registry']->getAuth()): ?>
  <p>
   <?php echo Horde::link(Horde::url('perms.php?cid=' . $GLOBALS['folder']->getId()), _("Control access to this folder"), '', '_blank', 'popup(this.href); return false;')
       . Horde::img('perms.png') . _("Control access to this folder") . '</a>' ?>
  </p>
 <?php endif; ?>
  </div>
 </div>
<?php
endif;
$folders = Trean::listFolders(Horde_Perms::READ);
if (!is_a($folders, 'PEAR_Error')) {
    $params = array('icon' => Horde_Themes::img('tree/folder.png', 'horde'), 'iconopen' => Horde_Themes::img('tree/folderopen.png', 'horde'));
    $tree = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Tree')->create('folder_tree', 'Javascript');
    $tree->setOption(array('alternate' => true));
    $expand = $prefs->getValue('expand_tree');
    if ($expand == 'none') {
        $expand = false;
    } elseif ($expand == 'all') {
        $expand = true;
    }

    foreach ($folders as $tfn => $tf) {
        if (!empty($folder)) {
            $f_id = $folder->getId();
        } else {
            $f_id = null;
        }
        $params['class'] = ($tf->getId() == $f_id) ? 'selected' : null;
        $params['url'] = Horde::url('browse.php?f=' . $tf->getId());
        $level = substr_count($tfn, ':');

        if ($expand == 'first') {
            $expand_node = ($level == 0);
        } else {
            $expand_node = $expand;
        }
        $tree->addNode($tf->getId(), $tf->getParent(), $tf->get('name'), $level, $expand_node, $params);
    }

    echo $tree->renderTree();
}
?>
 </div>
 <div class="yui-u" id="bookmarkList">
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
  </div>
 </div>
</div>
<!-- End grid -->
