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
     *
     * @return Nag_Search
     */
    public function __construct($search, $mask, array $options = array())
    {
        $options = array_merge(
            array('completed' => 0, 'due' => array(), 'tags' => array()),
            $options);

        $this->_search = $search;
        $this->_mask = $mask;
        $this->_completed = $options['completed'];
        $this->_due = $options['due'];
        $this->_tags = $options['tags'];
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

        $pattern = $this->_search;
        if (!empty($this->_due)) {
            $date = Horde_Date_Parser::parse($this->_due[1]);
            $date->mday += $this->_due[0];
            $date = $date->timestamp();
        } else {
            $date = false;
        }

        // Get the full, sorted task list.
        $tasks = Nag::listTasks(
            $prefs->getValue('sortby'),
            $prefs->getValue('sortdir'),
            $prefs->getValue('altsortby'),
            array_keys(Nag::listTasklists(false, Horde_Perms::READ, false)),
            $this->_completed
        );

        $pattern = '/' . preg_quote($pattern, '/') . '/i';
        $search_results = new Nag_Task();
        $tasks->reset();
        $results = array();
        if (!empty($this->_tags)) {
            $tagged_tasks = $GLOBALS['injector']->getInstance('Nag_Tagger')
                ->search($this->_tags, array('user' => $GLOBALS['registry']->getAuth()));
        }
        while ($task = $tasks->each()) {
            if (!empty($date)) {
                if ($task->due > $date) {
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

        return $search_results;
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