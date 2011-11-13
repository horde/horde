<div id="bookmarkList">
<?php
if (count($bookmarks)) {
    $view = new Trean_View_BookmarkList($GLOBALS['bookmarks']);
    echo $view->render();
} else {
    require TREAN_TEMPLATES . '/bookmarklet.php';
    echo '<p><em>' . sprintf(_("No bookmarks. Drag the %s link to your browser's toolbar to add them easily!"), $bookmarklet_link) . '</em></p>';
}
?>
</div>
