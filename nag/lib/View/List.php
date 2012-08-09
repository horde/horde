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
     * Const'r
     *
     * @param Horde_Variables $vars  Variables for the view.
     *
     * @return Nag_View_List
     */
    public function __construct($vars)
    {
        $this->_vars = $vars;
        $this->_checkSortValue();
        $this->_handleActions();
        $this->_title = _("My Tasks");
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
        global $prefs;
        $output->addScriptFile('tooltips.js', 'horde');
        $output->addScriptFile('scriptaculous/effects.js', 'horde');
        $output->addScriptFile('quickfinder.js', 'horde');
        $output->header(array(
            'body_class' => $GLOBALS['prefs']->getValue('show_panel') ? 'rightPanel' : null,
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
        foreach (Nag::listTaskLists(true) as $list) {
            if ($list->get('issmart')) {
                $tabs->addTab($list->get('name'), $listurl->add(array('actionID' => 'smart', 'list' => $list->getName())));
            }
        }
        echo $tabs->render($this->_vars->get('show_completed'));
        require NAG_TEMPLATES . '/list.html.php';
        require NAG_TEMPLATES . '/panel.inc';
        $page_output->footer();

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
    protected function _handleActions()
    {
        $lists = null;
        switch ($this->_vars->actionID) {
        case 'search_tasks':
            $this->_doSearch();
            break;
        case 'smart':
            $lists = array($this->_vars->get('list'));
            $list = $GLOBALS['nag_shares']->getShare($this->_vars->get('list'));
            $this->_title = $list->get('name');
            // Fall through
        default:
            try {
                $this->_tasks = Nag::listTasks(array(
                    'tasklists' => $lists,
                    'include_tags' => true)
                );
            } catch (Nag_Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                $this->_tasks = new Nag_Task();
            }
            break;
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

}