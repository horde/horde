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

     protected $_owner;
     protected $_page = 0;
     protected $_perPage = 999;


    public function __construct()
    {
        $this->_owner = Horde_Util::getFormData('owner', '');
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
            // ??
        }
    }

    public function render()
    {
        $results = $this->_browser->getSlice($this->_page, $this->_perPage);
        $total = $this->_browser->count();
        $rtags = $this->_browser->getRelatedTags();

        $html = 'Current tags in search:<ul>';
        foreach ($this->_browser->getTags() as $tag => $id) {
            $html .= '<li>' . htmlspecialchars($tag) . $this->_linkRemoveTag($tag)->link()
                . Horde::img('delete-small.png', _("Remove from search")) . '</a></li>';
        }
        // $html .= '<br /><br />Matching Bookmarks<br /><ul>';
        // foreach ($results as $bm) {
        //     $html .= '<li>' . $bm->url . '</li>';
        // }
        $view = new Trean_View_BookmarkList($results);
        Horde::startBuffer();
        $view->render();
        $html .= Horde::endBuffer();
        $html .= '<br /><br/>Related Tags<br /><ul>';
        foreach ($rtags as $id => $taginfo) {
            $html .= '<li>' . $this->_linkAddTag($taginfo['tag_name'])->link()
                . htmlspecialchars($taginfo['tag_name']) . '</a></li>';
        }

        echo $html;
    }

    protected function _linkRemoveTag($tag)
    {
        return Horde::url('tagbrowse.php')
            ->add(array(
                'actionID' => 'remove',
                'tag' => rawurlencode($tag)));
    }

    protected function _linkAddTag($tag)
    {
        return Horde::url('tagbrowse.php')
            ->add(array(
                'tag' => rawurlencode($tag)));
    }

}
