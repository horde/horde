<div id="bookmarkList">
<?php
if (count($bookmarks)) {
    $view = new Trean_View_BookmarkList($GLOBALS['bookmarks']);
    echo $view->render();
} else {
    echo '<p><em>' . _("No bookmarks. Drag the \"New Bookmark\" link to your browser's toolbar to add them easily!") . '</em></p>';
}
?>
</div>
