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
     * Share representing the current smarlist (if being viewed).
     *
     * @var Horde_Share_Object_Base
     */
    protected $_smartShare;

    /**
     * Horde_Variables
     *
     * @var Horde_Variables
     */
    protected $_vars;

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
        global $injector, $prefs;

        $output->addScriptFile('tooltips.js', 'horde');
        $output->addScriptFile('scriptaculous/effects.js', 'horde');
        $output->addScriptFile('quickfinder.js', 'horde');
        $output->header(array(
            'title' => $this->_title
        ));

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
                $tabs->addTab(
                    $list->get('name'),
                    $listurl->add(array('actionID' => 'smart', 'list' => $list->getName())),
                    array('img' => 'search.png'));
            }
        }

        // Set up the view
        $view = $GLOBALS['injector']->createInstance('Horde_View');
        $view->addHelper(new Nag_View_Helper_List($view));
        $view->tasks = $this->_tasks;
        $view->tasks->reset();
        $view->tabs = $tabs->render($this->_vars->get('show_completed'));
        $view->browser = empty($this->_smartShare) ? $this->_getRelatedTags() . $this->_getTagTrail() : '';
        $view->title = $this->_title;
        $view->sortby = $prefs->getValue('sortby');
        $view->sortdir = $prefs->getValue('sortdir');
        $view->sortdirclass = $view->sortdir ? 'sortup' : 'sortdown';
        $view->dateFormat = $prefs->getValue('date_format');
        $view->columns = @unserialize($prefs->getValue('tasklist_columns'));
        $view->smartShare = $this->_smartShare;
        if (empty($view->columns)) {
            $view->columns = array();
        }
        $view->dynamic_sort = true;

        $view->baseurl = Horde::url('list.php');
        if ($this->_vars->actionID == 'search_tasks') {
            $view->baseurl->add(
                array('actionID' => 'search_tasks',
                      'search_pattern' => $search_pattern,
                      'search_name' => $search_name ? 'on' : 'off',
                      'search_desc' => $search_desc ? 'on' : 'off')
            );
        }

        Horde::startBuffer();
        Nag::status();
        echo $view->render('list');
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
            if ($this->_vars->deletebutton) {
                $this->_doDeleteSmartList();
                $this->_handleActions(false);
            } else {
                $this->_doSearch();
            }
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
                $this->_browser->setFilter($this->_vars->show_completed);
                $this->_tasks = $this->_browser->getSlice();
            }
            break;
        case 'smart':
            $lists = array($this->_vars->get('list'));
            $list = $GLOBALS['nag_shares']->getShare($this->_vars->get('list'));
            $this->_title = $list->get('name');
            $this->_smartShare = $list;
            $this->_loadTasks($lists);
            break;
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
                'include_tags' => true,
                'completed' => $this->_vars->show_completed)
            );
        } catch (Nag_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            $this->_tasks = new Nag_Task();
        }
    }

    /**
     * Performs a Task search. self::_tasks is populated with the results.
     *
     * @throws Nag_Exception
     */
    protected function _doSearch()
    {

        $form = new Nag_Form_Search($this->_vars);
        if ($form->validate($this->_vars, true)) {
            $form->getInfo($this->_vars, $info);
        } else {
            throw new Nag_Exception(current($form->getErrors()));
        }

        // Text filter
        $search_pattern = $this->_vars->search_pattern;
        $search_in = $this->_vars->search_in;
        $search_name = in_array('search_name', $search_in) ? Nag_Search::MASK_NAME : 0;
        $search_desc = in_array('search_desc', $search_in) ? Nag_Search::MASK_DESC : 0;
        $search_tags = !empty($this->_vars->search_tags) ? Nag_Search::MASK_TAGS : 0;
        $search_completed = $this->_vars->search_completed;

        $this->_vars->set('show_completed', $search_completed);
        $mask = $search_name | $search_desc | $search_tags;

        // Date filter
        $date = $info['due_date'];
        if (empty($date)) {
            $date = array();
        }

        // Prepare the search
        $search = new Nag_Search(
            $search_pattern,
            $mask,
            array(
                'completed' => $search_completed,
                'due' => $date,
                'tags' => empty($this->_vars->search_tags) ? array() : Nag::getTagger()->split($this->_vars->search_tags))
        );
        try {
            $tasks = $search->getSlice();
        } catch (Nag_Exception $e) {
            $GLOBALS['notification']->push($tasks, 'horde.error');
            $tasks = new Nag_Task();
        }

        // Save as a smart list?
        if ($id = $this->_vars->get('smart_id')) {
            // Existing list.
            $smartlist = $GLOBALS['nag_shares']->getShare($id);
            Nag::updateTasklist(
                $smartlist,
                array(
                    'name' => $this->_vars->get('smartlist_name'),
                    'search' => serialize($search))
            );
            $this->_title = $smartlist->get('name');
            $this->_smartShare = $smartlist;
        } elseif ($this->_vars->get('save_smartlist')) {
            $this->_smartShare = Nag::addTasklist(
                array('name' => $this->_vars->get('smartlist_name'),
                      'search' => serialize($search)),
                false
            );
            $this->_title = $this->_vars->get('smartlist_name');
        } else {
            // Build a page title based on criteria.
            $this->_title = sprintf(_("Search: Results for"));
            $have_title = false;
            if (!empty($search_pattern)) {
                $have_title = true;
                $this->_title .= ' "' . $search_pattern . '" ';
            } else {
                $this->_title .= ' ' . _("tasks") . ' ';
            }
            if (!empty($date)) {
                if ($have_title) {
                    $this->_title .= _("and") . ' ';
                } else {
                    $this->_title .= _("with") . ' ';
                    $have_title = true;
                }
                $this->_title .= sprintf(_("due date within %d days of %s"), $date[0], $date[1]) . ' ';
            }
            if (!empty($search_tags)) {
                if ($have_title) {
                    $this->_title .= _("and") . ' ';
                } else {
                    $this->_title .= _("with") . ' ';
                }
                $this->_title .= sprintf(_("and tagged with %s"), $this->_vars->search_tags);
            }
        }

        $this->_tasks = $tasks;
    }

    /**
     * Delete a SmartList.
     *
     */
    protected function _doDeleteSmartList()
    {
        try {
            $sl = $GLOBALS['nag_shares']->getShare($this->_vars->smart_id);
            Nag::deleteTasklist($sl);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
            Horde::url('list.php')->redirect();
            exit;
        }
        $GLOBALS['notification']->push(_("SmartList deleted successfully"), 'horde.success');
    }

    /**
     * Get HTML to display the related tags links.
     *
     * @return string
     */
    protected function _getRelatedTags()
    {
        $rtags = $this->_browser->getRelatedTags();
        if (count($rtags)) {
            $t = Horde::img('tags.png');
            $html = sprintf('<div class="nag-tags-related">%s<ul class="horde-tags">', $t);
            foreach ($rtags as $id => $taginfo) {
                $html .= '<li>' . $this->_linkAddTag($taginfo['tag_name'])->link()
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
