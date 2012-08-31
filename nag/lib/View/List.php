<?php
/**
 * Tag list view
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Nag
 */
class Nag_View_List
{
    /**
     * The loaded tasks.
     *
     * @var Nag_Task
     */
    protected $_tasks;

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
     * @var Nag_TagBrowser
     */
    protected $_browser;

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
        $this->_title = _("My Tasks");
        $this->_browser = $GLOBALS['injector']
            ->getInstance('Nag_Factory_TagBrowser')
            ->create();
        $this->_checkSortValue();
        $this->_handleActions();
    }

    /**
     * Reners the view.
     *
     * @param Horde_PageOutput $output  The output object.
     *
     * @return string  The HTML needed to render the view.
     */
    public function render($output)
    {
        // Remove $baseurl once this has been converted to Horde_View.
        global $prefs, $baseurl;
        $output->addScriptFile('tooltips.js', 'horde');
        $output->addScriptFile('scriptaculous/effects.js', 'horde');
        $output->addScriptFile('quickfinder.js', 'horde');
        $output->header(array(
            'title' => $this->_title
        ));
        $tasks = $this->_tasks;
        Horde::startBuffer();
        echo Nag::menu();
        Nag::status();
        echo '<div id="page">';
        $tabs = new Horde_Core_Ui_Tabs('show_completed', $this->_vars);
        if (!$GLOBALS['prefs']->isLocked('show_completed')) {
            $listurl = Horde::url('list.php');
            $tabs->addTab(_("_All tasks"), $listurl, Nag::VIEW_ALL);
            $tabs->addTab(_("Incom_plete tasks"), $listurl, Nag::VIEW_INCOMPLETE);
            $tabs->addTab(_("_Future tasks"), $listurl, Nag::VIEW_FUTURE);
            $tabs->addTab(_("_Completed tasks"), $listurl, Nag::VIEW_COMPLETE);
        }
        foreach (Nag::listTasklists() as $list) {
            if ($list->get('issmart')) {
                $tabs->addTab($list->get('name'), $listurl->add(array('actionID' => 'smart', 'list' => $list->getName())));
            }
        }
        echo $tabs->render($this->_vars->get('show_completed'));
        if ($this->_showTagBrowser) {
            echo $this->_getTagTrail() . $this->_getRelatedTags();
        }
        $title = $this->_title;
        require NAG_TEMPLATES . '/list.html.php';
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
        if ($this->_vars->exists('show_completed')) {
            $prefs->setValue('show_completed', $this->_vars->get('show_completed'));
        } else {
            $this->_vars->set('show_completed', $prefs->getValue('show_completed'));
        }
    }

    /**
     * Helper to handle any incoming actions.
     */
    protected function _handleActions($action = null)
    {
        $lists = null;
        if (is_null($action)) {
            $action = $this->_vars->actionID;
        }
        switch ($action) {
        case 'search_tasks':
            $this->_doSearch();
            break;
        case 'browse_add':
        case 'browse_remove':
        case 'browse':
            // The tag to add|remove from the browse search.
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
                $this->_loadTasks();
            } else {
                $this->_tasks = $this->_browser->getSlice();
            }
            break;
        case 'smart':
            $lists = array($this->_vars->get('list'));
            $list = $GLOBALS['nag_shares']->getShare($this->_vars->get('list'));
            $this->_title = $list->get('name');
            // Fall through
        default:
            // If we have an active tag browse, use it.
            if ($this->_browser->tagCount() >= 1) {
                $this->_handleActions('browse');
            } else {
                $this->_loadTasks($lists);
            }
            break;
        }
    }

    /**
     * Load the full, sorted task list.
     */
    protected function _loadTasks($lists = null)
    {
        try {
            $this->_tasks = Nag::listTasks(array(
                'tasklists' => $lists,
                'include_tags' => true)
            );
        } catch (Nag_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            $this->_tasks = new Nag_Task();
        }
    }

    /**
     * Performs a Task search. self::_tasks is populated with the results.
     */
    protected function _doSearch()
    {
        // Text filter
        $search_pattern = $this->_vars->search_pattern;
        $search_name = $this->_vars->search_name == 'on' ? Nag_Search::MASK_NAME : 0;
        $search_desc = $this->_vars->search_desc == 'on' ? Nag_Search::MASK_DESC : 0;
        $search_tags = !empty($this->_vars->search_tags) ? Nag_Search::MASK_TAGS : 0;
        $search_completed = $this->_vars->search_completed;
        $this->_vars->set('show_completed', $search_completed);
        $mask = $search_name | $search_desc | $search_tags;

        // Date filter
        if (!empty($this->_vars->due_within) &&
            is_numeric($this->_vars->due_within) &&
            !empty($this->_vars->due_of)) {
            $date = array($this->_vars->due_within, $this->_vars->due_of);
        } else {
            $date = array();
        }

        // Prepare the search
        $search = new Nag_Search(
            $search_pattern,
            $mask,
            array(
                'completed' => $search_completed,
                'due' => $date,
                'tags' => $this->_vars->search_tags)
        );
        try {
            $tasks = $search->getSlice();
        } catch (Nag_Exception $e) {
            $GLOBALS['notification']->push($tasks, 'horde.error');
            $tasks = new Nag_Task();
        }

        // Build a page title based on criteria.
        $this->_title = sprintf(_("Search: Results for \"%s\""), $search_pattern);
        if (!empty($date)) {
            $this->_title .= ' ' . sprintf(_("and due date within %d days of %s"), $date[0], $date[1]);
        }
        if (!empty($search_tags)) {
            $this->_title .= ' ' . sprintf(_("and tagged with %s"), $this->_vars->search_tags);
        }

        // Save as a smart list?
        if ($this->_vars->get('save_smartlist')) {
            Nag::addTasklist(
                array('name' => $this->_vars->get('smartlist_name'),
                      'search' => serialize($search)),
                false
            );
        }

        $this->_tasks = $tasks;
    }

    /**
     * Get HTML to display the related tags links.
     *
     * @return string
     */
    protected function _getRelatedTags()
    {
        $rtags = $this->_browser->getRelatedTags();
        if ($this->_browser->tagCount() >= 1) {
            $t = _("Related Tags:");
        } else {
            $t = _("Tags:");
        }
        $html = sprintf('<div class="nag-tags-related">%s<ul class="horde-tags">', $t);
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
        if ($this->_browser->tagCount() >= 1) {
            $html = '<div class="nag-tags-browsing">' . _("Browsing for tags:") . '<ul class="horde-tags">';
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