<?php
/**
 * $Horde: trean/lib/Views/BookmarkList.php,v 1.2 2009/06/10 05:25:17 slusarz Exp $
 *
 * @package Trean
 */

/** Star rating helper */
require_once TREAN_TEMPLATES . '/star_rating_helper.php';

/**
 * $Horde: trean/lib/Views/BookmarkList.php,v 1.2 2009/06/10 05:25:17 slusarz Exp $
 */
class Trean_View_BookmarkList {

    var $showFolder = false;

    var $sortby;
    var $sortdir;
    var $sortdirclass;

    var $bookmarks = array();
    var $target;
    var $redirectUrl;

    function Trean_View_BookmarkList($bookmarks)
    {
        if (!is_a($bookmarks, 'PEAR_Error')) {
            $this->bookmarks = $bookmarks;
        }
        $this->target = $GLOBALS['prefs']->getValue('show_in_new_window') ? '_blank' : '';
        $this->redirectUrl = Horde::url('redirect.php');

        $this->sortby = $GLOBALS['prefs']->getValue('sortby');
        $this->sortdir = $GLOBALS['prefs']->getValue('sortdir');
        $this->sortdirclass = $this->sortdir ? 'sortup' : 'sortdown';
    }

    function folder($bookmark)
    {
        $folder = $GLOBALS['trean_shares']->getFolder($bookmark->folder);
        return Horde::link(Horde_Util::addParameter(Horde::url('browse.php'), 'f', $bookmark->folder)) . htmlspecialchars($folder->get('name')) . '</a>';
    }

    function render()
    {
        include TREAN_TEMPLATES . '/views/BookmarkList.php';
    }

}
