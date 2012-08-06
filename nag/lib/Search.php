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
     * Constructor
     *
     * @param string $search  The search string.
     * @param integer $mask   A bitmask to indicate the fields to search.
     * @param array $options  Additional options:
     *   - completed: (integer) Which tasks to include. A NAG::VIEW_* constant.
     *                DEFAULT: Nag::VIEW_INCOMPLETE
     *
     * @return Nag_Search
     */
    public function __construct($search, $mask, array $options = array())
    {
        $options = array_merge(
            array('completed' => 0),
            $options);

        $this->_search = $search;
        $this->_mask = $mask;
        $this->_completed = $options['completed'];
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

        // Get the full, sorted task list.
        $tasks = Nag::listTasks(
            $prefs->getValue('sortby'),
            $prefs->getValue('sortdir'),
            $prefs->getValue('altsortby'),
            array_keys(Nag::listTasklists(false, Horde_Perms::READ, false)),
            $this->_completed
        );

        if (!empty($pattern) && $this->_mask & self::MASK_ALL) {
            $pattern = '/' . preg_quote($pattern, '/') . '/i';
            $search_results = new Nag_Task();
            $tasks->reset();
            while ($task = $tasks->each()) {
                if (($this->_mask & self::MASK_NAME && preg_match($pattern, $task->name)) ||
                    ($this->_mask & self::MASK_DESC && preg_match($pattern, $task->desc))) {

                    $search_results->add($task);
                } else {
                    foreach (explode(',', $this->_search) as $tag) {
                        if (array_search($tag, $task->tags) !== false) {
                            $search_results->add($task);
                            break;
                        }
                    }
                }
            }

            return $search_results;
        }
    }

    public function serialize()
    {
        return serialize(array(
            'search' => $this->_search,
            'mask' => $this->_mask,
            'completed' => $this->_completed));
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->_search = $data['search'];
        $this->_mask = $data['mask'];
        $this->_completed = $data['completed'];
    }

}