<?php
/**
 * Nag_Search:: Interface for performing task searches.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Nag
 */
class Nag_Search implements Serializable
{
    /**
     * Search bit masks
     */
    const MASK_NAME      = 1;
    const MASK_DESC      = 2;
    const MASK_TAGS      = 4;
    const MASK_ALL       = 7;

    /**
     * Search criteria
     *
     * @var array
     */
    protected $_search;

    /**
     * The search mask
     *
     * @var integer
     */
    protected $_mask;

    /**
     * The completed/view value.
     *
     * @var integer
     */
    protected $_completed;

    /**
     * Duedate criteria
     *
     * @var array
     */
    protected $_due;

    /**
     * Tag search criteria
     *
     * @var array
     */
    protected $_tags;

    /**
     * Constructor
     *
     * @param string $search  The search string.
     * @param integer $mask   A bitmask to indicate the fields to search.
     * @param array $options  Additional options:
     *   - completed: (integer) Which tasks to include. A NAG::VIEW_* constant.
     *                DEFAULT: Nag::VIEW_INCOMPLETE
     *
     *   - due: (array)  An array describing the due date portion of the search.
     *          EXAMPLE: array('5', 'tomorrow') would be all tasks due within 5
     *          days of tomorrow.
     *          DEFAULT: No date filters.
     *
     *   - tags: (array) An array of tags to filter on.
     *   - tasklists: (array) An arary of tasklist ids to filter on.
     *                DEFAULT: The current display_tasklists value is used.
     *
     * @return Nag_Search
     */
    public function __construct($search, $mask, array $options = array())
    {
        $options = array_merge(
            array(
                'completed' => 0,
                'due' => array(),
                'tags' => array(),
                'tasklists' => $GLOBALS['display_tasklists']),
            $options);

        $this->_search = $search;
        $this->_mask = $mask;
        $this->_completed = $options['completed'];
        $this->_due = $options['due'];
        $this->_tags = $options['tags'];
        $this->_tasklists = $options['tasklists'];
    }

    /**
     * Get a result slice.
     *
     * @param integer $page     The page number
     * @param integer $perpage  The number of results per page.
     *
     * @return Nag_Task  The result list.
     */
    public function getSlice($page = 0, $perpage = 0)
    {
        return $this->_search($page, $perpage);
    }

    /**
     * Perform the search
     *
     * @param integer $page     The page number
     * @param integer $perpage  The number of results per page.
     *
     * @return Nag_Task
     */
    protected function _search($page, $perpage)
    {
        global $prefs;

        if (!empty($this->_due)) {
            $parser = Horde_Date_Parser::factory(array('locale' => $GLOBALS['prefs']->getValue('language')));
            $date = $parser->parse($this->_due[1]);
            $date->mday += $this->_due[0];
            $date = $date->timestamp();
        } else {
            $date = false;
        }

        // Get the full, sorted task list.
        $tasks = Nag::listTasks(array(
            'tasklists' => $this->_tasklists,
            'completed' => $this->_completed)
        );
        if (!empty($this->_search)) {
            $pattern = '/' . preg_quote($this->_search, '/') . '/i';
        }
        $search_results = new Nag_Task();
        if (!empty($this->_tags)) {
            $tagged_tasks = Nag::getTagger()->search(
                $this->_tags,
                array('user' => $GLOBALS['registry']->getAuth())
            );
        }
        $tasks->reset();
        while ($task = $tasks->each()) {
            if (!empty($date)) {
                if (empty($task->due) || $task->due > $date) {
                    continue;
                }
            }

            // If we have a search string and it doesn't match name|desc continue
            if (!empty($this->_search) &&
                !($this->_mask & self::MASK_NAME && preg_match($pattern, $task->name)) &&
                !($this->_mask & self::MASK_DESC && preg_match($pattern, $task->desc))) {

                continue;
            }

            // No tags to search? Add it to results. Otherwise, make sure it
            // has the requested tags.
            if (empty($this->_tags) || in_array($task->uid, $tagged_tasks)) {
                $search_results->add($task);
            }
        }

        // Now that we have filtered results, load all tags at once.
        $search_results->loadTags();

        return $search_results;
    }

    /**
     * Populate a Horde_Variables instance with the search values for this
     * search.
     *
     * @param Horde_Variables $vars  The Horde_Variables object.
     */
    public function getVars(Horde_Variables &$vars)
    {
        $vars->set('search_pattern', $this->_search);
        $vars->set('search_tags', implode(',', $this->_tags));
        $vars->set('search_completed', $this->_completed);
        $vars->set('due_within', $this->_due[0]);
        $vars->set('due_of', $this->_due[1]);

        $mask = array();
        if ($this->_mask & self::MASK_NAME) {
            $mask[] = 'search_name';
        }
        if ($this->_mask & self::MASK_DESC) {
            $mask[] = 'search_desc';
        }
        if (!empty($mask)) {
            $vars->set('search_in', $mask);
        }
    }

    public function serialize()
    {
        return serialize(array(
            'search' => $this->_search,
            'mask' => $this->_mask,
            'completed' => $this->_completed,
            'due' => $this->_due,
            'tags' => $this->_tags));
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->_search = $data['search'];
        $this->_mask = $data['mask'];
        $this->_completed = $data['completed'];
        $this->_due = $data['due'];
        $this->_tags = $data['tags'];
    }

}