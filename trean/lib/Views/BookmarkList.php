<?php
/**
 * @package Trean
 */
class Trean_View_BookmarkList
{
    var $showFolder = false;

    var $sortby;
    var $sortdir;
    var $sortdirclass;

    var $bookmarks = array();
    var $target;
    var $redirectUrl;

    public function __construct($bookmarks)
    {
        $this->bookmarks = $bookmarks;
        $this->target = $GLOBALS['prefs']->getValue('show_in_new_window') ? '_blank' : '';
        $this->redirectUrl = Horde::url('redirect.php');

        $this->sortby = $GLOBALS['prefs']->getValue('sortby');
        $this->sortdir = $GLOBALS['prefs']->getValue('sortdir');
        $this->sortdirclass = $this->sortdir ? 'sortup' : 'sortdown';
    }

    public function folder($bookmark)
    {
        $folder = $GLOBALS['trean_shares']->getFolder($bookmark->folder);
        return Horde::link(Horde_Util::addParameter(Horde::url('browse.php'), 'f', $bookmark->folder)) . htmlspecialchars($folder->get('name')) . '</a>';
    }

    public function render()
    {
        include TREAN_TEMPLATES . '/views/BookmarkList.php';
    }
}
