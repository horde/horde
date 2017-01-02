<?php
/**
 * Note list view.
 *
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license http://www.horde.org/licenses/bsdl.php BSD
 * @package Mnemo
 */
class Mnemo_View_List
{
    /**
     * The loaded notes.
     *
     * @var array
     */
    protected $_notes;

    /**
     * Page title
     *
     * @var string
     */
    protected $_title;

    /**
     * Flag to indicate whether or not to show the tag browser
     *
     * @var boolean
     */
    protected $_showTagBrowser = true;

    /**
     * Tag browser
     *
     * @var Mnemo_TagBrowser
     */
    protected $_browser;

    /**
     * Horde_Variables
     *
     * @var Horde_Variables
     */
    protected $_vars;

    /**
     * Flag to indicate if we have a search.
     *
     * @var boolean
     */
    protected $_haveSearch = false;

    /**
     * Baseurl
     *
     * @var Horde_Url
     */
    protected $_baseurl;

    /**
     * Const'r
     *
     * @param Horde_Variables $vars  Variables for the view.
     *
     * @return Nag_View_List
     */
    public function __construct($vars)
    {
        $this->_vars = $vars;
        $this->_title = _("My Notes");
        $this->_browser = $GLOBALS['injector']
            ->getInstance('Mnemo_Factory_TagBrowser')
            ->create();
        $this->_baseurl = Horde::url('list.php');
        $this->_checkSortValue();
        $this->_handleActions();
    }

    /**
     * Renders the view.
     *
     * @param Horde_PageOutput $output  The output object.
     *
     * @return string  The HTML needed to render the view.
     */
    public function render(Horde_PageOutput $output)
    {
        global $prefs, $injector, $registry, $mnemo_shares;

        $output->addScriptFile('tables.js', 'horde');
        $output->addScriptFile('quickfinder.js', 'horde');
        $output->addScriptFile('list.js');
        $output->header(array(
            'title' => $this->_title
        ));

        $view = $injector->createInstance('Horde_View');
        $view->count = count($this->_notes);
        $view->searchImg = Horde::img('search.png', _("Search"), '');
        $view->searchUrl = Horde::url('search.php');
        $view->title = $this->_title;
        $view->browser = $this->_showTagBrowser ? $this->_getRelatedTags() . $this->_getTagTrail() : '';

        if (count($this->_notes)) {
            $sortby = $prefs->getValue('sortby');
            $sortdir = $prefs->getValue('sortdir');
            $output->addInlineJsVars(array(
                'Mnemo_List.ajaxUrl' => $registry->getServiceLink('ajax', 'mnemo')->url . 'setPrefValue'
            ));
            $view->editImg = Horde::img('edit.png', _("Edit Note"), '');
            $view->showNotepad = $prefs->getValue('show_notepad');
            $view->sortdirclass = $sortdir ? 'sortup' : 'sortdown';
            $view->headers = array();
            if ($view->showNotepad) {
                $view->headers[] = array(
                    'id' => 's' . Mnemo::SORT_NOTEPAD,
                    'sorted' => $sortby == Mnemo::SORT_NOTEPAD,
                    'width' => '2%',
                    'label' => Horde::widget(array('url' => $this->_baseurl->add('sortby', Mnemo::SORT_NOTEPAD), 'class' => 'sortlink', 'title' => _("Notepad"))),
                );
            }
            $view->headers[] = array(
                'id' => 's' . MNEMO::SORT_DESC,
                'sorted' => $sortby == MNEMO::SORT_DESC,
                'width' => '80%',
                'label' => Horde::widget(array(
                    'url' => $this->_baseurl->add('sortby', Mnemo::SORT_DESC),
                    'class' => 'sortlink',
                    'title' => _("No_te")
                 )),
            );
            $view->headers[] = array(
                'id' => 's' . MNEMO::SORT_MOD_DATE,
                'sorted' => $sortby == Mnemo::SORT_MOD_DATE,
                'width' => '2%',
                'label' => Horde::widget(array(
                    'url' => $this->_baseurl->add('sortby', MNEMO::SORT_MOD_DATE),
                    'class' => 'sortlink',
                    'title' => _("Date")
                 )),
            );

            foreach ($this->_notes as $memo_id => &$memo) {
                try {
                    $share = $mnemo_shares->getShare($memo['memolist_id']);
                } catch (Horde_Share_Exception $e) {
                    $notification->push($e);
                    continue;
                }
                if ($view->showNotepad) {
                    $memo['notepad'] = Mnemo::getLabel($share);
                }
                if ($share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
                    $label = sprintf(_("Edit \"%s\""), $memo['desc']);
                    $memo['edit'] = Horde::url('memo.php')
                        ->add(array(
                            'memo' => $memo['memo_id'],
                            'memolist' => $memo['memolist_id'],
                            'actionID' => 'modify_memo'
                        ))
                        ->link(array('title' => $label))
                        . Horde::img('edit.png', $label, '') . '</a>';
                }

                $memo['link'] = Horde::linkTooltip(
                    Horde::url('view.php')->add(array(
                        'memo' => $memo['memo_id'],
                        'memolist' => $memo['memolist_id']
                    )),
                    '', '', '', '',
                    ($memo['body'] != $memo['desc']) ? Mnemo::getNotePreview($memo) : ''
                )
                    . (strlen($memo['desc']) ? htmlspecialchars($memo['desc']) : '<em>' . _("Empty Note") . '</em>')
                    . '</a>';

                // Get memo's most recent modification date or, if nonexistent,
                // the creation (add) date
                if (isset($memo['modified'])) {
                    $modified = $memo['modified'];
                } elseif (isset($memo['created'])) {
                    $modified = $memo['created'];
                } else {
                    $modified = null;
                }
                if ($modified) {
                    $memo['modifiedStamp'] = $modified->timestamp();
                    $memo['modifiedString'] = $modified->strftime($prefs->getValue('date_format'));
                } else {
                    $memo['modifiedStamp'] = $memo['modifiedString'] = '';
                }
            }
        }

        Horde::startBuffer();
        echo $view->render('list/header');
        if (count($this->_notes)) {
            echo $view->render('list/memo_headers');
            echo $view->renderPartial('list/summary', array('collection' => array_values($this->_notes)));
            echo $view->render('list/memo_footers');
        } else {
            echo $view->render('list/empty');
        }
        $output->footer();

        return Horde::endBuffer();
    }

    /**
     * Helper to check/update the sort prefs
     */
    protected function _checkSortValue()
    {
        global $prefs;

        // First check for any passed in sorting value changes.
        if ($this->_vars->exists('sortby')) {
            $prefs->setValue('sortby', $this->_vars->sortby);
        }
        if ($this->_vars->exists('sortdir')) {
            $prefs->setValue('sortdir', $this->_vars->sortdir);
        }
    }

    /**
     * Load the full, sorted task list.
     */
    protected function _loadNotes()
    {
        global $prefs;

        $this->_notes = Mnemo::listMemos(
            $prefs->getValue('sortby'),
            $prefs->getValue('sortdir')
        );
    }

    /**
     * Helper to handle any incoming actions.
     */
    protected function _handleActions($action = null)
    {
        if (is_null($action)) {
            $action = $this->_vars->actionID;
        }
        switch ($action) {
        case 'browse_add':
        case 'browse_remove':
        case 'browse':
            $tag = trim($this->_vars->get('tag'));
            if (!empty($tag)) {
                if ($this->_vars->actionID == 'browse_add') {
                    $this->_browser->addTag($tag);
                } else {
                    $this->_browser->removeTag($tag);
                }
                $this->_browser->save();
            }
            if ($this->_browser->tagCount() < 1) {
                $this->_browser->clearSearch();
                $this->_loadNotes();
            } else {
                $this->_notes = $this->_browser->getSlice();
            }
            break;
        case 'search_memos':
            $this->_loadNotes();
            $this->_showTagBrowser = false;
            $this->_doSearch();
            $this->_title = _("Search Results");
            break;
        default:
            // If we have an active tag browse, use it.
            if ($this->_browser->tagCount() >= 1) {
                $this->_handleActions('browse');
            } else {
                $this->_loadNotes();
            }
            $this->_showTagBrowser = true;
            break;
        }
    }

    /**
     * Perform search
     *
     * @throws Mnemo_Exception
     */
    protected function _doSearch()
    {
        $search_pattern = $this->_vars->get('search_pattern');
        $search_type = $this->_vars->get('search_type');
        $search_desc = ($search_type == 'desc');
        $search_body = ($search_type == 'body');
        if (!empty($search_pattern) && ($search_body || $search_desc)) {
            $search_pattern = '/' . preg_quote($search_pattern, '/') . '/i';
            $search_result = array();
            foreach ($this->_notes as $memo_id => $memo) {
                if (($search_desc && preg_match($search_pattern, $memo['desc'])) ||
                    ($search_body && preg_match($search_pattern, $memo['body']))) {
                    $search_result[$memo_id] = $memo;
                }
            }
            $this->_notes = $search_result;
        } elseif ($search_type == 'tags') {
            // Tag search, use the browser.
            $this->_browser->clearSearch();
            $tags = $GLOBALS['injector']->getInstance('Mnemo_Tagger')
                ->split($this->_vars->get('search_pattern'));
            foreach ($tags as $tag) {
                $this->_browser->addTag($tag);
            }
            $this->_notes = $this->_browser->getSlice();
            $this->_handleActions(false);
            return;
        }
        $this->_baseurl->add(array(
            'actionID' => 'search_memos',
            'search_pattern' => $search_pattern,
            'search_type' => $search_type)
        );
    }

    /**
     * Get HTML to display the related tags links.
     *
     * @return string
     */
    protected function _getRelatedTags()
    {
        $ids = array();
        foreach ($this->_notes as $t) {
            $ids[] = $t['uid'];
        }
        $rtags = $this->_browser->getRelatedTags($ids);
        if (count($rtags)) {
        $html = '<div class="nag-tags-related">'
                . Horde::img('tags.png')
                . ' <ul class="horde-tags">';
            foreach ($rtags as $id => $taginfo) {
                $html .= '<li>'
                    . $this->_linkAddTag($taginfo['tag_name'])->link()
                    . htmlspecialchars($taginfo['tag_name']) . '</a></li>';
            }
            return $html . '</ul></div>';
        }

        return '';
    }

    /**
     * Get HTML to represent the currently selected tags.
     *
     * @return string
     */
    protected function _getTagTrail()
    {
        if ($this->_browser->tagCount() >= 1) {
            $html = '<div class="nag-tags-browsing">' . Horde::img('filter.png') . '<ul class="horde-tags">';
            foreach ($this->_browser->getTags() as $tag => $id) {
                $html .= '<li>' . htmlspecialchars($tag)
                    . $this->_linkRemoveTag($tag)->link()
                    . Horde::img('delete-small.png', _("Remove from search"))
                    . '</a></li>';
            }
            return $html .= '</ul></div>';
        }

        return '';
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
        return Horde::url('list.php')
            ->add(array('actionID' => 'browse_remove', 'tag' => $tag));
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
        return Horde::url('list.php')->add(array('actionID' => 'browse_add', 'tag' => $tag));
    }

}