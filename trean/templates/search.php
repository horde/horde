<?php
if (!count($bookmarks)) {
    echo '<p><em>' . _("No Bookmarks found") . '</em></p>';
} else {
    $view = new Trean_View_BookmarkList($bookmarks);
    $view->showTagBrowser(false);
    echo $view->render($search_title);
}
