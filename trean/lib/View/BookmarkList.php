<?php
/**
 * Tag browsing
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Trean
 */
class Trean_View_BookmarkList
{
    /**
     * Tag Browser
     *
     * @var Trean_TagBrowse
     */
    protected $_browser;

    /**
     * The loaded bookmarks.
     *
     * @var array
     */
    protected $_bookmarks;

    /**
     * Current page
     *
     * @var int
     */
    protected $_page = 0;

    /**
     * Bookmarks to display per page
     *
     * @var int
     */
    protected $_perPage = 999;

    /**
     * Flag to indicate we have an empty search.
     *
     * @var boolean
     */
    protected $_noSearch = false;

    /**
     * Flag to indicate whether or not to show the tag browser
     * @var boolean
     */
    protected $_showTagBrowser = true;

    /**
     * Const'r
     *
     */
    public function __construct($bookmarks = null)
    {
        $this->_bookmarks = $bookmarks;
        $this->_browser = new Trean_TagBrowse(
            $GLOBALS['injector']->getInstance('Trean_Tagger'));

        $action = Horde_Util::getFormData('actionID', '');
        switch ($action) {
        case 'remove':
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $this->_browser->removeTag($tag);
                $this->_browser->save();
            }
            break;

        case 'add':
        default:
            // Add new tag to the stack, save to session.
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $this->_browser->addTag($tag);
                $this->_browser->save();
            }
        }

        // Check for empty tag search.. then do what?
        if ($this->_browser->tagCount() < 1) {
            $this->_noSearch = true;
        }
    }

    /**
     * Toggle showing of the tag browser
     */
    public function showTagBrowser($showTagBrowser)
    {
        $this->_showTagBrowser = $showTagBrowser;
    }

    /**
     * Returns whether bookmarks currently exist.
     *
     * @return boolean  True if there exist any bookmarks in the backend.
     */
    public function hasBookmarks()
    {
        $this->_getBookmarks();
        return (bool)count($this->_bookmarks) ||
            (bool)$this->_browser->tagCount();
    }

    /**
     * Renders the view.
     */
    public function render($title = null)
    {
        if (is_null($title)) {
            $title = _("Bookmarks");
        }

        $this->_getBookmarks();

        $html = '';
        if ($this->_showTagBrowser) {
            $html = $this->_getTagTrail() . $this->_getRelatedTags();
        }
        return $html . '<h1 class="header">' . $title . '</h1>' . $this->_getBookmarkList($this->_bookmarks);
    }

    /**
     * Loads the bookmarks from the backend.
     */
    protected function _getBookmarks()
    {
        if (!is_null($this->_bookmarks)) {
            return;
        }

        // @TODO: paging
        if ($this->_noSearch) {
            $this->_bookmarks = $GLOBALS['trean_gateway']
                ->listBookmarks(
                    $GLOBALS['prefs']->getValue('sortby'),
                    $GLOBALS['prefs']->getValue('sortdir'),
                    $this->_page,
                    $this->_perPage);
        } else {
            $this->_bookmarks = $this->_browser->getSlice($this->_page, $this->_perPage);
        }
    }

    /**
     * Returns the HTML to display a bookmark list.
     *
     * @param array $bookmarks  A list of bookmarks.
     *
     * @return string  Bookmark list HTML.
     */
    protected function _getBookmarkList($bookmarks)
    {
        $GLOBALS['page_output']->addScriptFile('tables.js', 'horde');
        $GLOBALS['page_output']->header();

        $view = $GLOBALS['injector']->getInstance('Horde_View');
        $view->bookmarks = $bookmarks;
        $view->target = $GLOBALS['prefs']->getValue('show_in_new_window') ? '_blank' : '';
        $view->redirectUrl = Horde::url('redirect.php');

        $view->sortby = $GLOBALS['prefs']->getValue('sortby');
        $view->sortdir = $GLOBALS['prefs']->getValue('sortdir');
        $view->sortdirclass = $view->sortdir ? 'sortup' : 'sortdown';
        return $view->render('list');
    }

    /**
     * Get HTML to display the related tags links.
     *
     * @return string
     */
    protected function _getRelatedTags()
    {
        $rtags = $this->_browser->getRelatedTags();
        $html = '<div class="trean-tags-related">' . _("Related Tags:") . '<ul class="horde-tags">';
        foreach ($rtags as $id => $taginfo) {
            $html .= '<li>' . $this->_linkAddTag($taginfo['tag_name'])->link()
                . htmlspecialchars($taginfo['tag_name']) . '</a></li>';
        }

        return $html . '</ul></div>';
    }

    /**
     * Get HTML to represent the currently selected tags.
     *
     * @return string
     */
    protected function _getTagTrail()
    {
        $html = '<div class="header">' . _("Browsing for tags:") . '<ul class="horde-tags">';
        foreach ($this->_browser->getTags() as $tag => $id) {
            $html .= '<li>' . htmlspecialchars($tag)
                . $this->_linkRemoveTag($tag)->link()
                . Horde::img('delete-small.png', _("Remove from search"))
                . '</a></li>';
        }
        return $html .= '</ul></div>';
    }


    /**
     * Get HTML for a link to remove a tag from the current search.
     *
     * @param  string $tag  The tag we want the link for.
     *
     * @return string
     */
    protected function _linkRemoveTag($tag)
    {
        return Horde::url('browse.php')
            ->add(array('actionID' => 'remove', 'tag' => $tag));
    }

    /**
     * Get HTML for a link to add a new tag to the current search.
     *
     * @param string $tag  The tag we want to add.
     *
     * @return string
     */
    protected function _linkAddTag($tag)
    {
        return Horde::url('browse.php')->add(array('tag' => $tag));
    }

}
