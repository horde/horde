<script type="text/javascript">
var loading;

function selectAllBookmarks()
{
    for (i = 0; i < document.bookmarks.elements.length; i++) {
        document.bookmarks.elements[i].checked = true;
    }
}

function selectNoBookmarks()
{
    for (i = 0; i < document.bookmarks.elements.length; i++) {
        document.bookmarks.elements[i].checked = false;
    }
}

function editSelectedBookmarks()
{
    if (loading == null) {
        loading = true;
        document.bookmarks.actionID.value = 'edit';
        document.bookmarks.submit();
    }
}

function deleteSelectedBookmarks()
{
    if (loading == null && confirm('<?php echo addslashes(_("Are you sure you want to delete the selected bookmarks?")) ?>')) {
        loading = true;
        document.bookmarks.actionID.value = 'delete';
        document.bookmarks.submit();
    }
}

function copySelectedBookmarks()
{
    if (loading == null) {
        loading = true;
        document.bookmarks.actionID.value = 'copy';
        document.bookmarks.submit();
    }
}

function moveSelectedBookmarks()
{
    if (loading == null) {
        loading = true;
        document.bookmarks.actionID.value = 'move';
        document.bookmarks.submit();
    }
}
</script>

<form name="bookmarks" action="edit.php" method="post">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="" />

<h1 class="header"><?php echo htmlspecialchars($search_title) ?></h1>

<?php if (count($bookmarks)): ?>
<table class="control" cellspacing="0"><tr>
<td>
<?php printf(_("Select: %s, %s"),
             Horde::link('#', _("Select All"), 'widget', '', 'selectAllBookmarks(); return false;') . _("All") . '</a>',
             Horde::link('#', _("Select None"), 'widget', '', 'selectNoBookmarks(); return false;') . _("None") . '</a>') ?>
</td><td class="rightAlign">
<?php
echo Horde::link('#', '', 'widget', '', 'editSelectedBookmarks(); return false;') . _("Edit") . '</a>';
echo ' | ' . Horde::link('#', '', 'widget', '', 'deleteSelectedBookmarks(); return false;') . _("Delete") . '</a>';
echo ' | ' . Horde::link('#', '', 'widget', '', 'moveSelectedBookmarks(); return false;') . _("Move") . '</a>';
echo ' | ' . Horde::link('#', '', 'widget', '', 'copySelectedBookmarks(); return false;') . _("Copy") . '</a>';
?>
<select name="new_folder"><?php echo Trean::folderSelect(null, Horde_Perms::EDIT) ?></select>
</td></tr></table>
<?php else: echo '<p><em>' . _("No Bookmarks found") . '</em></p>'; endif;

$view = new Trean_View_BookmarkList($bookmarks);
echo $view->render();
?>
</form>
