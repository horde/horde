<?php
/**
 * Tag browsing
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Trean
 */
class Trean_View_Browse
{
    /**
     * Tag Browser
     *
     * @var Trean_TagBrowse
     */
     protected $_browser;

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
      * Const'r
      *
      */
    public function __construct()
    {
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
     * Render the view.
     */
    public function render()
    {
        // @TODO: paging
        if ($this->_noSearch) {
            $results = $GLOBALS['trean_gateway']
                ->listBookmarks(
                    $GLOBALS['prefs']->getValue('sortby'),
                    $GLOBALS['prefs']->getValue('sortdir'),
                    $this->_page,
                    $this->_perPage);
        } else {
            $results = $this->_browser->getSlice($this->_page, $this->_perPage);
        }
        $total = $this->_browser->count();

        $html = $this->_getTagTrail();
        $html .= $this->_getRelatedTags();

        $html .= '<h1 class="header">' . _("Bookmarks") . '</h1>';
        $view = new Trean_View_BookmarkList($results);
        Horde::startBuffer();
        $view->render();
        $html .= Horde::endBuffer();

        echo $html;
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
        return Horde::url('tagbrowse.php')
            ->add(array(
                'actionID' => 'remove',
                'tag' => rawurlencode($tag)));
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
        return Horde::url('tagbrowse.php')
            ->add(array(
                'tag' => rawurlencode($tag)));
    }

}
